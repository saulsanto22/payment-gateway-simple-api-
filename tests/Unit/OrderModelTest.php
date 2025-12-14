<?php

use App\Models\Order;
use App\Models\User;
use App\Models\OrderItem;
use App\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Order Model', function () {
    
    it('dapat membuat order dengan atribut yang benar', function () {
        // Buat user dulu
        $user = User::factory()->create();
        
        // Buat order
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total_price' => 100000,
            'status' => OrderStatus::PENDING,
        ]);

        // Verifikasi order dibuat dengan benar
        expect($order)->toBeInstanceOf(Order::class)
            ->and($order->user_id)->toBe($user->id)
            ->and($order->total_price)->toBe(100000)
            ->and($order->status)->toBe(OrderStatus::PENDING)
            ->and($order->order_number)->not->toBeNull();
    });

    it('memiliki relasi belongsTo dengan User', function () {
        $user = User::factory()->create(['name' => 'John Doe']);
        $order = Order::factory()->create(['user_id' => $user->id]);

        // Cek relasi user
        expect($order->user)->toBeInstanceOf(User::class)
            ->and($order->user->id)->toBe($user->id)
            ->and($order->user->name)->toBe('John Doe');
    });

    it('memiliki relasi hasMany dengan OrderItem', function () {
        $order = Order::factory()->create();
        $product1 = \App\Models\Product::factory()->create();
        $product2 = \App\Models\Product::factory()->create();
        
        // Buat order items dengan produk berbeda
        $order->items()->create([
            'product_id' => $product1->id,
            'quantity' => 2,
            'price' => 50000,
        ]);
        
        $order->items()->create([
            'product_id' => $product2->id,
            'quantity' => 1,
            'price' => 30000,
        ]);

        // Cek relasi items
        expect($order->items)->toHaveCount(2)
            ->and($order->items->first())->toBeInstanceOf(OrderItem::class);
    });

    it('casting status ke OrderStatus enum', function () {
        $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

        // Cek bahwa status adalah enum, bukan string
        expect($order->status)->toBeInstanceOf(OrderStatus::class)
            ->and($order->status)->toBe(OrderStatus::PENDING)
            ->and($order->status->value)->toBe('pending');
    });
});
