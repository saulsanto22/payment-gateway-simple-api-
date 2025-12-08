<?php

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;

class OrderRepository
{
    public function createOrder($userId, $total)
    {
        return Order::create([
            'user_id' => $userId,
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

    public function findOrder($orderId)
    {
        return Order::find($orderId);
    }

    public function getOrderHistory($user, $perPage = 15)
    {
        return Order::with('items.product')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
