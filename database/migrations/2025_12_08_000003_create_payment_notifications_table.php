<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel untuk menyimpan log notifikasi Midtrans guna idempotensi dan audit.
     */
    public function up(): void
    {
        Schema::create('payment_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->index();
            $table->string('transaction_status');
            $table->string('signature_key');
            $table->string('payload_hash')->unique(); // deteksi notifikasi identik
            $table->json('payload'); // simpan payload mentah untuk audit
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_notifications');
    }
};
