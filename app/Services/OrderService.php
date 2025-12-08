<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Repositories\CartRepository;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected MidtransService $midtransService;

    protected OrderRepository $orderRepository;

    protected CartRepository $cartRepository;

    public function __construct(MidtransService $midtransService, OrderRepository $orderRepository, CartRepository $cartRepository)
    {
        $this->midtransService = $midtransService;
        $this->orderRepository = $orderRepository;
        $this->cartRepository = $cartRepository;
    }

    public function checkout($user)
    {
        $cartItems = $this->cartRepository->getUserCart($user->id);

        if ($cartItems->isEmpty()) {
            return null;
        }

        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item->product->price * $item->quantity;
        }

        $order = null;

        DB::transaction(function () use ($user, $cartItems, $total, &$order) {
            $order = $this->orderRepository->createOrder($user->id, $total);

            foreach ($cartItems as $item) {
                $this->orderRepository->addOrderItem(
                    $order->id,
                    $item->product_id,
                    $item->quantity,
                    $item->product->price
                );
            }

            // bersihkan cart dalam transaksi agar konsisten
            foreach ($cartItems as $item) {
                $item->delete();
            }
        });

        // refresh relasi items & user untuk Midtrans payload
        $order = $this->orderRepository->getOrderWithItems($order->id);

        // generate snap token di luar transaksi DB
        $snapToken = $this->midtransService->createSnapToken($order);
        
        $order->update([
            'snap_token' => $snapToken->token,
            'redirect_url' => $snapToken->redirect_url,
        ]);

        return $order;
    }

    public function handleCallback($order, $transactionStatus, $fraudStatus = null)
    {
        $orderStatus = match ($transactionStatus) {
            'settlement' => OrderStatus::PAID,
            'capture' => $fraudStatus === 'challenge' ? OrderStatus::PENDING : OrderStatus::PAID,
            'cancel', 'deny' => OrderStatus::CANCELLED,
            'expire' => OrderStatus::EXPIRED,
            default => OrderStatus::PENDING,
        };

        // jika status saat ini sudah final dan target bukan status yang lebih kuat, abaikan
        $finalStates = [OrderStatus::PAID, OrderStatus::CANCELLED, OrderStatus::EXPIRED];
        if (in_array($order->status, $finalStates, true)) {
            return $order;
        }

        return $this->orderRepository->updateStatus($order, $orderStatus);
    }

    public function updateProductStock($order)
    {
        foreach ($order->items as $item) {
            $product = $item->product;
            $product->decrement('stock', $item->quantity);
        }
    }

}
