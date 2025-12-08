<?php

namespace App\Services;

use App\Models\Order;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    /**
     * Membuat Snap token dengan informasi yang lebih lengkap:
     * - order_id menggunakan order_number (bukan id increment)
     * - item_details diisi agar breakdown tampak di Midtrans
     * - customer_details sederhana (bisa ditambah phone/alamat jika ada)
     */
    public function createSnapToken(Order $order)
    {
        // Siapkan rincian item untuk transparansi pembayaran
        $itemDetails = [];
        foreach ($order->items as $item) {
            $itemDetails[] = [
                'id' => (string) $item->product_id,
                'price' => (int) round($item->price * 100) / 100, // pastikan numeric
                'quantity' => (int) $item->quantity,
                'name' => $item->product->name,
            ];
        }

        $params = [
            'transaction_details' => [
                'order_id' => $order->order_number, // gunakan order_number
                'gross_amount' => (int) round($order->total_price * 100) / 100,
            ],
            'item_details' => $itemDetails,
            'customer_details' => [
                'first_name' => $order->user->name,
                'email' => $order->user->email,
            ],
        ];

        $snapToken = Snap::getSnapToken($params);

        return $snapToken;
    }
}
