<?php

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;

class OrderRepository
{
    /**
     * Membuat order baru sekaligus menghasilkan order_number yang unik.
     * order_number digunakan sebagai order_id untuk Midtrans.
     */
    public function createOrder($userId, $total)
    {
        // Format sederhana: ORD-YYYYMMDDHHMMSS-5charRandom
        $orderNumber = 'ORD-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(5));

        return Order::create([
            'user_id' => $userId,
            'order_number' => $orderNumber,
            'total_price' => $total,
            'status' => OrderStatus::PENDING,
        ]);
    }

    public function addOrderItem($orderId, $productId, $quantity, $price)
    {
        return OrderItem::create([
            'order_id' => $orderId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'price' => $price,
        ]);
    }

    public function updateStatus($order, $status)
    {
        $order->status = $status;
        $order->save();

        return $order;
    }

    public function getOrderWithItems($orderId)
    {
        return Order::with('items.product')->find($orderId);
    }

    /**
     * Cari order berdasarkan order_number (untuk webhook Midtrans yang menggunakan order_id = order_number)
     */
    public function findOrderByNumber($orderNumber)
    {
        return Order::where('order_number', $orderNumber)->first();
    }

    public function getOrderHistory($user, $perPage = 15)
    {
        return Order::with('items.product')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    //ambil data order yang pending lebih dari 24 jam
    public function GetPendingOrder(): \Illuminate\Database\Eloquent\Collection
    {
        return Order::where('status', OrderStatus::PENDING)
            ->where('created_at', '<=', now()->subHours(24))
            ->get();

    }
}
