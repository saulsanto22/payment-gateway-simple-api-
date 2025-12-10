<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mengubah tipe kolom orders.total_price dari integer ke decimal(12,2)
     * tanpa membutuhkan doctrine/dbal, dengan langkah aman:
     * 1) Tambah kolom sementara temp_total_price (decimal)
     * 2) Salin nilai dari total_price lama ke temp_total_price
     * 3) Hapus kolom total_price lama (integer)
     * 4) Buat kolom total_price baru (decimal)
     * 5) Salin balik dari temp_total_price ke total_price baru
     * 6) Hapus kolom sementara
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('temp_total_price', 12, 2)->nullable()->after('user_id');
        });

        // Salin nilai lama ke kolom sementara (integer -> decimal)
        DB::statement('UPDATE orders SET temp_total_price = total_price');

        // Hapus kolom lama dan buat kolom baru dengan tipe decimal
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('total_price');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total_price', 12, 2)->default(0)->after('user_id');
        });

        // Salin nilai dari kolom sementara ke kolom baru
        DB::statement('UPDATE orders SET total_price = temp_total_price');

        // Bersihkan kolom sementara
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('temp_total_price');
        });
    }

    /**
     * Rollback: kembalikan ke integer dengan teknik serupa (tanpa rename/change)
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('temp_total_price_int')->nullable()->after('user_id');
        });

        // Casting decimal ke integer (floor)
        DB::statement('UPDATE orders SET temp_total_price_int = FLOOR(total_price)');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('total_price');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->integer('total_price')->default(0)->after('user_id');
        });

        DB::statement('UPDATE orders SET total_price = temp_total_price_int');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('temp_total_price_int');
        });
    }
};
