<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\OutOfStockException;
use App\Repositories\CartRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected MidtransService $midtransService;

    protected OrderRepository $orderRepository;

    protected CartRepository $cartRepository;

    protected ProductRepository $productRepository;

    public function __construct(
        MidtransService $midtransService,
        OrderRepository $orderRepository,
        CartRepository $cartRepository,
        ProductRepository $productRepository
    ) {
        $this->midtransService = $midtransService;
        $this->orderRepository = $orderRepository;
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
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
            // 1. Periksa stok & kunci produk dalam transaksi
            foreach ($cartItems as $item) {
                $product = $this->productRepository->findWithLock($item->product_id);
                if ($product->stock < $item->quantity) {
                    throw new OutOfStockException('Product '.$product->name.' is out of stock.');
                }
            }

            // 2. Buat pesanan
            $order = $this->orderRepository->createOrder($user->id, $total);

            // 3. Tambahkan item pesanan
            foreach ($cartItems as $item) {
                $this->orderRepository->addOrderItem(
                    $order->id,
                    $item->product_id,
                    $item->quantity,
                    $item->product->price
                );
            }

            // 4. Bersihkan keranjang (stok TIDAK dikurangi di sini)
            $this->cartRepository->clear($user->id);
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
            // Gunakan findWithLock lagi untuk keamanan saat update
            $product = $this->productRepository->findWithLock($item->product_id);
            $product->decrement('stock', $item->quantity);
        }
    }
}
