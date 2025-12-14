<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OutOfStockException;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Jobs\ProcessMidtransWebhook;
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
        try {
            $order = $this->orderService->checkout($request->user());

            if (! $order) {
                return ApiResponse::error('Cart is empty.', 422); // 422 Unprocessable Entity lebih cocok
            }

            return ApiResponse::success($order, 'Checkout successfully');
        } catch (OutOfStockException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
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
        \Log::info('Midtrans webhook received', $request->all());

        // validasi dulu

        $payload = $request->validate([
            'order_id' => 'required|string',
            'status_code' => 'required|string',
            'gross_amount' => 'required|string',
            'signature_key' => 'required|string',
            'transaction_status' => 'required|string',
            'fraud_status' => 'sometimes|string',
        ]);

        // BEST PRACTICE: Dispatch ke queue 'webhooks' (high priority)
        ProcessMidtransWebhook::dispatch($payload)
            ->onQueue('webhooks');

        return ApiResponse::success(null, 'Webhook received and queued for processing');
    }
}
