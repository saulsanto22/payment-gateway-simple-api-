<?php

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

describe('Cart Flow - Add, Update, Remove', function () {

    beforeEach(function () {
        // Setup user dan token untuk semua test
        $this->user = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);
        $this->headers = ['Authorization' => 'Bearer '.$this->token];
    });

    it('user dapat menambah produk ke cart', function () {
        $product = Product::factory()->create([
            'name' => 'Laptop',
            'price' => 500000,
            'stock' => 10,
        ]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/cart', [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Item added to cart',
            ]);

        // Cek database
        $this->assertDatabaseHas('carts', [
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    });

    it('user dapat melihat isi cart', function () {
        $product1 = Product::factory()->create(['name' => 'Laptop']);
        $product2 = Product::factory()->create(['name' => 'Mouse']);

        Cart::create([
            'user_id' => $this->user->id,
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);

        Cart::create([
            'user_id' => $this->user->id,
            'product_id' => $product2->id,
            'quantity' => 1,
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/cart');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(2, 'data');
    });

    it('user dapat mengupdate quantity produk di cart', function () {
        $product = Product::factory()->create(['stock' => 20]);

        Cart::create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->withHeaders($this->headers)
            ->putJson("/api/cart/{$product->id}", [
                'quantity' => 5,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Item updated',
            ]);

        // Cek database
        $this->assertDatabaseHas('carts', [
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    });

    it('user dapat menghapus produk dari cart', function () {
        $product = Product::factory()->create();

        Cart::create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->withHeaders($this->headers)
            ->deleteJson("/api/cart/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Item removed from cart',
            ]);

        // Cek database
        $this->assertDatabaseMissing('carts', [
            'user_id' => $this->user->id,
            'product_id' => $product->id,
        ]);
    });

    it('user dapat mengosongkan seluruh cart', function () {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        Cart::create(['user_id' => $this->user->id, 'product_id' => $product1->id, 'quantity' => 2]);
        Cart::create(['user_id' => $this->user->id, 'product_id' => $product2->id, 'quantity' => 1]);

        $response = $this->withHeaders($this->headers)
            ->deleteJson('/api/cart/clear');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Cek cart kosong
        $this->assertDatabaseMissing('carts', [
            'user_id' => $this->user->id,
        ]);
    });

    it('gagal menambah produk dengan quantity 0', function () {
        $product = Product::factory()->create();

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/cart', [
                'product_id' => $product->id,
                'quantity' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    });

    it('gagal menambah produk yang tidak ada', function () {
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/cart', [
                'product_id' => 99999,
                'quantity' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    });
});
