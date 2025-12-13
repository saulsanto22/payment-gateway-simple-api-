<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Services\CartService;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index()
    {
        return ApiResponse::success($this->cartService->listCart(auth()->user()));
    }

    public function add(AddCartRequest $request)
    {
        return ApiResponse::success(
            $this->cartService->addItem(auth()->user(), $request->product_id, $request->quantity),
            'Item added to cart');
    }

    public function update(UpdateCartRequest $request, $productId)
    {
        return ApiResponse::success(
            $this->cartService->updateItem(auth()->user(), $productId, $request->quantity),
            'Item updated');
    }

    public function remove($productId)
    {
        $deleted = $this->cartService->removeItem(auth()->user(), $productId);

        if (! $deleted) {
            return ApiResponse::error('Item not found', 404);
        }

        return ApiResponse::success(null, 'Item removed from cart');
    }

    public function clear()
    {
        $this->cartService->clearCart(auth()->user());

        return ApiResponse::success(null, 'Cart cleared');
    }
}
