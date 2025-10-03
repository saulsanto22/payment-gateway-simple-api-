<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
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
        $order = $this->orderRepository->getOrderHistory($request->user());

        return ApiResponse::success($order, 'History successfully');
    }

    public function webhook(Request $request)
    {
        \Log::info('midtrans webhook', $request->all());

        $serverKey = config('midtrans.server_key');

        $orderId = $request->input('order_id');
        $statusCode = $request->input('status_code');
        $grossAmount = $request->input('gross_amount');
        $signatureKey = $request->input('signature_key');
        $transactionStatus = $request->input('transaction_status');
        $paymentType = $request->input('payment_type');
        $fraudStatus = $request->input('fraud_status');

        $mySignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        if ($signatureKey != $mySignature) {
            \Log::info('invalid signature key');

            return ApiResponse::error('Invalid signature key', 404);
        }

        $order = $this->orderRepository->findOrder($orderId);

        if (! $order) {
            return ApiResponse::error('Order not found', 404);
        }

        $updatedOrder = $this->orderService->handleCallback($order, $transactionStatus);

        return ApiResponse::success($updatedOrder, 'Callback successfully');
    }
}
