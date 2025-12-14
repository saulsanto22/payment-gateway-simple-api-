<?php

use App\Models\Cart;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Cart Model', function () {
    
    it('dapat membuat cart dengan atribut yang benar', function () {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        
        $cart = Cart::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        expect($cart)->toBeInstanceOf(Cart::class)
            ->and($cart->user_id)->toBe($user->id)
            ->and($cart->product_id)->toBe($product->id)
            ->and($cart->quantity)->toBe(3);
    });

    it('memiliki relasi belongsTo dengan User', function () {
        $user = User::factory()->create(['name' => 'John Doe']);
        $cart = Cart::factory()->create(['user_id' => $user->id]);

        expect($cart->user)->toBeInstanceOf(User::class)
            ->and($cart->user->name)->toBe('John Doe');
    });

    it('memiliki relasi belongsTo dengan Product', function () {
        $product = Product::factory()->create(['name' => 'Laptop']);
        $cart = Cart::factory()->create(['product_id' => $product->id]);

        expect($cart->product)->toBeInstanceOf(Product::class)
            ->and($cart->product->name)->toBe('Laptop');
    });

    it('dapat mengupdate quantity', function () {
        $cart = Cart::factory()->create(['quantity' => 1]);
        
        $cart->quantity = 5;
        $cart->save();

        expect($cart->fresh()->quantity)->toBe(5);
    });
});
