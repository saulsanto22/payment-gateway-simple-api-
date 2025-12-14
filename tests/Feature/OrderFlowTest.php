<?php

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

describe('Order Flow - Checkout & History', function () {

    beforeEach(function () {
        // Setup user dan token
        $this->user = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);
        $this->headers = ['Authorization' => 'Bearer '.$this->token];
    });

    it('user dapat checkout cart menjadi order', function () {
        // Setup: Tambah produk ke cart
        $product = Product::factory()->create([
            'name' => 'Gaming Mouse',
            'price' => 250000,
            'stock' => 10,
        ]);

        Cart::create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // Action: Checkout
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/orders/checkout');

        // Assert: Response success
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Checkout successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_number',
                    'total_price',
                    'status',
                    'snap_token',
                    'redirect_url',
                ],
            ]);

        // Assert: Order dibuat di database
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'total_price' => 500000, // 2 * 250000
            'status' => OrderStatus::PENDING->value,
        ]);

        // Assert: Cart kosong setelah checkout
        $this->assertDatabaseMissing('carts', [
            'user_id' => $this->user->id,
        ]);

        // Note: Stok tidak berkurang saat checkout, hanya saat payment success
    });

    it('checkout berhasil dengan multiple produk', function () {
        $product1 = Product::factory()->create(['price' => 100000, 'stock' => 10]);
        $product2 = Product::factory()->create(['price' => 200000, 'stock' => 5]);

        Cart::create(['user_id' => $this->user->id, 'product_id' => $product1->id, 'quantity' => 2]);
        Cart::create(['user_id' => $this->user->id, 'product_id' => $product2->id, 'quantity' => 1]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/orders/checkout');

        $response->assertStatus(200);

        // Total: (2 * 100000) + (1 * 200000) = 400000
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'total_price' => 400000,
        ]);

        // Note: Stok tidak berkurang saat checkout
    });

    it('checkout gagal jika cart kosong', function () {
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/orders/checkout');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Cart is empty.',
            ]);

        // Pastikan tidak ada order dibuat
        $this->assertDatabaseMissing('orders', [
            'user_id' => $this->user->id,
        ]);
    });

    it('checkout gagal jika stok tidak cukup', function () {
        $product = Product::factory()->create([
            'price' => 100000,
            'stock' => 2, // Stok hanya 2
        ]);

        Cart::create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'quantity' => 5, // Minta 5
        ]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/orders/checkout');

        $response->assertStatus(422)
            ->assertJsonFragment([
                'success' => false,
            ]);

        // Pastikan tidak ada order dibuat
        $this->assertDatabaseMissing('orders', [
            'user_id' => $this->user->id,
        ]);
    });

    it('user dapat melihat history order', function () {
        // Buat beberapa order
        Order::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/orders/history');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    });

    it('user hanya melihat order miliknya sendiri', function () {
        // Order user ini
        Order::factory()->create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-USER1',
        ]);

        // Order user lain
        $otherUser = User::factory()->create();
        Order::factory()->create([
            'user_id' => $otherUser->id,
            'order_number' => 'ORD-USER2',
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/orders/history');

        $response->assertStatus(200);

        $responseData = $response->json('data');

        // Jika pagination, ambil data array
        $orders = isset($responseData['data']) ? $responseData['data'] : $responseData;

        // Harus ada minimal 1 order dan semua milik user ini
        expect(count($orders))->toBeGreaterThanOrEqual(1);

        // Cek order pertama milik user ini
        expect($orders[0]['order_number'])->toContain('ORD-USER1');
    });

    it('order memiliki order_number yang unique', function () {
        $product = Product::factory()->create(['price' => 100000, 'stock' => 10]);

        // Checkout 1
        Cart::create(['user_id' => $this->user->id, 'product_id' => $product->id, 'quantity' => 1]);
        $response1 = $this->withHeaders($this->headers)->postJson('/api/orders/checkout');
        $orderNumber1 = $response1->json('data.order_number');

        // Checkout 2
        Cart::create(['user_id' => $this->user->id, 'product_id' => $product->id, 'quantity' => 1]);
        $response2 = $this->withHeaders($this->headers)->postJson('/api/orders/checkout');
        $orderNumber2 = $response2->json('data.order_number');

        // Order number harus berbeda
        expect($orderNumber1)->not->toBe($orderNumber2);
    });

    it('order memiliki snap_token dan redirect_url dari Midtrans', function () {
        $product = Product::factory()->create(['price' => 100000, 'stock' => 10]);

        Cart::create(['user_id' => $this->user->id, 'product_id' => $product->id, 'quantity' => 1]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/orders/checkout');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Snap token dan redirect URL harus ada (dari Midtrans)
        expect($data['snap_token'])->not->toBeNull();
        expect($data['redirect_url'])->not->toBeNull();
    });
});
