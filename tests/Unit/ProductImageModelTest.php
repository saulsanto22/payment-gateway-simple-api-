<?php

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ProductImage Model', function () {

    it('dapat membuat product image dengan atribut yang benar', function () {
        $product = Product::factory()->create();

        $productImage = ProductImage::create([
            'product_id' => $product->id,
            'image_path' => '/storage/products/laptop.jpg',
        ]);

        expect($productImage)->toBeInstanceOf(ProductImage::class)
            ->and($productImage->product_id)->toBe($product->id)
            ->and($productImage->image_path)->toBe('/storage/products/laptop.jpg');
    });

    it('memiliki relasi belongsTo dengan Product', function () {
        $product = Product::factory()->create(['name' => 'Gaming Mouse']);
        $productImage = ProductImage::factory()->create(['product_id' => $product->id]);

        expect($productImage->product)->toBeInstanceOf(Product::class)
            ->and($productImage->product->id)->toBe($product->id)
            ->and($productImage->product->name)->toBe('Gaming Mouse');
    });

    it('dapat membuat multiple images untuk satu produk', function () {
        $product = Product::factory()->create();

        ProductImage::factory()->count(3)->create([
            'product_id' => $product->id,
        ]);

        expect($product->images)->toHaveCount(3);
    });

    it('dapat menghapus image', function () {
        $productImage = ProductImage::factory()->create();
        $imageId = $productImage->id;

        $productImage->delete();

        $this->assertDatabaseMissing('product_images', [
            'id' => $imageId,
        ]);
    });
});
