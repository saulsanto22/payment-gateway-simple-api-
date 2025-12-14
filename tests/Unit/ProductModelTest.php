<?php

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Product Model', function () {

    it('dapat membuat produk dengan atribut yang benar', function () {
        $product = Product::factory()->create([
            'name' => 'Laptop Gaming',
            'description' => 'Laptop untuk gaming',
            'price' => 150000,
            'stock' => 5,
        ]);

        expect($product)->toBeInstanceOf(Product::class)
            ->and($product->name)->toBe('Laptop Gaming')
            ->and($product->price)->toBe(150000)
            ->and($product->stock)->toBe(5);
    });

    it('memiliki relasi hasMany dengan ProductImage', function () {
        $product = Product::factory()->create();

        // Buat beberapa gambar untuk produk
        ProductImage::factory()->count(3)->create([
            'product_id' => $product->id,
        ]);

        expect($product->images)->toHaveCount(3)
            ->and($product->images->first())->toBeInstanceOf(ProductImage::class);
    });

    it('dapat mengurangi stok produk', function () {
        $product = Product::factory()->create(['stock' => 10]);

        // Simulasi pengurangan stok
        $product->stock = $product->stock - 2;
        $product->save();

        expect($product->fresh()->stock)->toBe(8);
    });
});
