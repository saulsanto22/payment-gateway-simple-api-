<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Enums\OrderStatus;
use App\Repositories\OrderRepository;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $orderService;

    protected $orderRepository;

    public function __construct(OrderService $orderService, OrderRepository $orderRepository)
    {
        $this->orderService = $orderService;
        $this->orderRepository = $orderRepository;
    }

    public function checkout(CheckoutRequest $request)
    {
        $order = $this->orderService->checkout($request->user());

        if (! $order) {
            return ApiResponse::error('Cart Kosong!!!!', 404);
        }

        return ApiResponse::success($order, 'Checkout successfully');
    }

    public function history(Request $request)
    {
        $perPage = (int) ($request->get('per_page', 15));
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 15;

        $order = $this->orderRepository->getOrderHistory($request->user(), $perPage);

        return ApiResponse::success($order, 'History successfully');
    }

    public function webhook(Request $request)
    {
        \Log::info('midtrans webhook', $request->all());

        $serverKey = config('midtrans.server_key');

        $orderId = $request->input('order_id'); // order_number dari Midtrans
        $statusCode = $request->input('status_code');
        $grossAmount = $request->input('gross_amount');
        $signatureKey = $request->input('signature_key');
        $transactionStatus = $request->input('transaction_status');
        $paymentType = $request->input('payment_type');
        $fraudStatus = $request->input('fraud_status');

        // Validasi signature sesuai dokumentasi Midtrans
        $mySignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        if ($signatureKey != $mySignature) {
            \Log::info('invalid signature key');

            return ApiResponse::error('Invalid signature key', 401);
        }

        // Cari order menggunakan order_number
        $order = $this->orderRepository->findOrderByNumber($orderId);

        if (! $order) {
            return ApiResponse::error('Order not found', 404);
        }

        // Validasi tambahan: gross_amount harus sama dengan total_price di DB
        $orderAmountString = number_format((float) $order->total_price, 2, '.', '');
        if ((string) $grossAmount !== $orderAmountString) {
            \Log::warning('gross_amount mismatch', [
                'order_number' => $order->order_number,
                'midtrans_gross_amount' => $grossAmount,
                'order_total_price' => $orderAmountString,
            ]);

            // Mengembalikan 400 agar mismatch jelas terdeteksi (sesuai persetujuan)
            return ApiResponse::error('Invalid amount', 400);
        }

        // Idempotensi sederhana: jika status sudah final, jangan timpa lagi
        if (in_array($order->status, [OrderStatus::PAID, OrderStatus::CANCELLED, OrderStatus::EXPIRED], true)) {
            return ApiResponse::success($order, 'Already final state');
        }

        // Log notifikasi untuk idempotensi yang lebih kuat
        $payload = $request->getContent();
        $payloadHash = hash('sha256', $payload);

        // Simpan log; jika payload sama sudah ada, anggap duplikat dan kembalikan sukses
        \DB::table('payment_notifications')->updateOrInsert(
            ['payload_hash' => $payloadHash],
            [
                'order_number' => $order->order_number,
                'transaction_status' => $transactionStatus,
                'signature_key' => $signatureKey,
                'payload' => $payload,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $updatedOrder = $this->orderService->handleCallback($order, $transactionStatus, $fraudStatus);

        return ApiResponse::success($updatedOrder, 'Callback successfully');
    }
}
