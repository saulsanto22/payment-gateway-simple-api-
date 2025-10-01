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
        $data = $this->orderService->checkout($request->user());

        if (! $order) {
            return ApiResponse::error('Cart Kosong!!!!', 404);
        }

        return ApiResponse::success($data, 'Checkout successfully');
    }

    public function callback(Request $request)
    {
        $order = $this->orderRepository->findOrder($request->order_id);

        if (! $order) {
            return ApiResponse::error('Order not found', 404);
        }

        $updatedOrder = $this->orderService->callback($order);

        return ApiResponse::success($updatedOrder, 'Callback successfully');
    }
}
