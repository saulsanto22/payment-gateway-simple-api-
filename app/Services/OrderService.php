<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Repositories\CartRepository;
use App\Repositories\OrderRepository;

class OrderService
{
    protected MidtransService $midtransService;

    protected MidtransService $orderRepository;

    protected MidtransService $cartRepository;

    public function __construct(MidtransService $midtransService, OrderRepository $orderRepository, CartRepository $cartRepository)
    {
        $this->midtransService = $midtransService;
        $this->orderRepository = $orderRepository;
        $this->cartRepository = $cartRepository;
    }

    public function checkout($user)
    {
        $cartItems = $this->cartRepository->getCart($user->id);

        if ($cartItems->isEmpty()) {
            return null;
        }

        $total = 0;

        foreach ($cartItems as $item) {
            $total += $item->product->price * $item->quantity;
        }

        $order = $this->orderRepository->createOrder($user->id, $total);

        foreach ($cartItems as $item) {
            $this->orderRepository->addOrderItem($order->id, $item->product_id, $item->quantity, $item->product->price);
        }

        $snapToken = $this->midtransService->createSnapToken($order);
        $order->update([
            'snap_token' => $snapToken,
        ]);

        foreach ($cartItems as $item) {
            $item->delete();
        }

        return $this->orderRepository->getOrderWithItems($order->id);

    }

    public function handleCallback($order, $status)
    {
        $orderStatus = match ($status) {
            'capture', 'settlement' => OrderStatus::PAID,
            'cancel', 'deny' => OrderStatus::CANCELLED,
            'expire' => OrderStatus::EXPIRED,
            default => OrderStatus::PENDING
        };

        return $this->orderRepository->updateStatus($order, $orderStatus);
    }
}
