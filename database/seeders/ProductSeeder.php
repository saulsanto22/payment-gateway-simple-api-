<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed produk untuk demo:
     * - 15 produk statis (kopi/teh/snack) untuk uji filter/sort dengan harga beragam
     * - 200 produk acak via factory untuk uji performa/pagination
     */
    public function run(): void
    {
        // Produk statis rapi untuk demo/filter
        $products = [
            ['name' => 'Coffee Arabica 250g', 'description' => 'Kopi arabika premium, kemasan 250 gram', 'price' => 65000.00, 'stock' => 100],
            ['name' => 'Coffee Robusta 500g', 'description' => 'Kopi robusta strong, kemasan 500 gram', 'price' => 55000.00, 'stock' => 120],
            ['name' => 'Cold Brew Concentrate 1L', 'description' => 'Konsentrat cold brew siap campur', 'price' => 120000.00, 'stock' => 40],
            ['name' => 'Matcha Latte Powder 200g', 'description' => 'Bubuk matcha latte import', 'price' => 95000.00, 'stock' => 60],
            ['name' => 'Black Tea Premium 50s', 'description' => 'Teh hitam premium 50 sachet', 'price' => 45000.00, 'stock' => 200],
            ['name' => 'Green Tea Sencha 100g', 'description' => 'Teh hijau sencha, kemasan 100 gram', 'price' => 70000.00, 'stock' => 80],
            ['name' => 'Oolong Tea 100g', 'description' => 'Teh oolong aroma floral', 'price' => 85000.00, 'stock' => 70],
            ['name' => 'Chocolate Powder 250g', 'description' => 'Bubuk cokelat untuk minuman', 'price' => 50000.00, 'stock' => 150],
            ['name' => 'Caramel Syrup 750ml', 'description' => 'Sirup karamel untuk kopi', 'price' => 65000.00, 'stock' => 90],
            ['name' => 'Vanilla Syrup 750ml', 'description' => 'Sirup vanilla untuk minuman', 'price' => 65000.00, 'stock' => 90],
            ['name' => 'Hazelnut Syrup 750ml', 'description' => 'Sirup hazelnut aromatik', 'price' => 68000.00, 'stock' => 80],
            ['name' => 'Almond Cookies 12pcs', 'description' => 'Kue almond renyah', 'price' => 40000.00, 'stock' => 110],
            ['name' => 'Butter Croissant 6pcs', 'description' => 'Croissant butter premium', 'price' => 55000.00, 'stock' => 60],
            ['name' => 'Espresso Blend Beans 1kg', 'description' => 'Biji kopi blend untuk espresso', 'price' => 180000.00, 'stock' => 35],
            ['name' => 'Drip Bag Coffee 10s', 'description' => 'Kopi drip bag praktis', 'price' => 75000.00, 'stock' => 100],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(
                ['name' => $p['name']],
                [
                    'description' => $p['description'],
                    'price' => $p['price'],
                    'stock' => $p['stock'],
                ]
            );
        }

        // Tambahan data acak untuk uji performa/pagination
        Product::factory()->count(200)->create();
    }
}
