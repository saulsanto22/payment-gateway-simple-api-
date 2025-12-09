<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Enums\OrderStatus;
use App\Repositories\OrderRepository;
use App\Services\OrderService;
use App\Jobs\ProcessMidtransWebhook;
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

        if (!$order) {
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
        \Log::info('Midtrans webhook received', $request->all());
        ProcessMidtransWebhook::dispatch($request->all());
        return ApiResponse::success(null, 'Webhook received and queued for processing');
    }
}
