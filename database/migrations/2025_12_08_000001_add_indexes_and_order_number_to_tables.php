<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tujuan migration ini:
     * - Menambahkan kolom order_number unik pada tabel orders untuk digunakan sebagai order_id Midtrans.
     * - Menambahkan unique constraint dan index pada tabel carts, orders, order_items, dan products
     *   untuk meningkatkan performa query (history, cart lookup, listing produk) dan mencegah duplikasi.
     *
     * Catatan:
     * - Perubahan tipe kolom (misalnya mengubah orders.total_price menjadi decimal) biasanya
     *   membutuhkan paket doctrine/dbal. Agar aman dan bertahap, perubahan tipe tidak dilakukan di sini.
     */
    public function up(): void
    {
        // Tabel carts: cegah duplikasi product per user dan percepat lookup
        Schema::table('carts', function (Blueprint $table) {
            // UNIQUE (user_id, product_id)
            $table->unique(['user_id', 'product_id'], 'carts_user_product_unique');
        });

        // Tabel orders: tambahkan order_number unik dan index yang umum dipakai
        Schema::table('orders', function (Blueprint $table) {
            // Kolom untuk order_id eksternal (Midtrans), unik agar tidak bentrok
            $table->string('order_number')->nullable()->unique('orders_order_number_unique')->after('id');

            // Index untuk history/filter
            $table->index('user_id', 'orders_user_id_index');
            $table->index('status', 'orders_status_index');
            $table->index('created_at', 'orders_created_at_index');
        });

        // Tabel order_items: index join dan cegah duplikasi item per product dalam satu order
        Schema::table('order_items', function (Blueprint $table) {
            $table->index('order_id', 'order_items_order_id_index');
            $table->index('product_id', 'order_items_product_id_index');
            $table->unique(['order_id', 'product_id'], 'order_items_order_product_unique');
        });

        // Tabel products: index untuk search/sort umum
        Schema::table('products', function (Blueprint $table) {
            $table->index('name', 'products_name_index');
            $table->index('price', 'products_price_index');
            $table->index('created_at', 'products_created_at_index');
        });
    }

    /**
     * Rollback migration.
     */
    public function down(): void
    {
        // Revert products indexes
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_name_index');
            $table->dropIndex('products_price_index');
            $table->dropIndex('products_created_at_index');
        });

        // Revert order_items indexes & unique
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropUnique('order_items_order_product_unique');
            $table->dropIndex('order_items_order_id_index');
            $table->dropIndex('order_items_product_id_index');
        });

        // Revert orders: drop indexes & order_number
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_user_id_index');
            $table->dropIndex('orders_status_index');
            $table->dropIndex('orders_created_at_index');
            $table->dropUnique('orders_order_number_unique');
            $table->dropColumn('order_number');
        });

        // Revert carts unique
        Schema::table('carts', function (Blueprint $table) {
            $table->dropUnique('carts_user_product_unique');
        });
    }
};
