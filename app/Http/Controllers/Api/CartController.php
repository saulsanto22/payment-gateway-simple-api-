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

    /**
     * View cart
     *
     * @OA\Get(
     *     path="/api/cart",
     *     tags={"Cart"},
     *     summary="Lihat isi shopping cart",
     *     description="Get semua item di cart user yang sedang login",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success - Return list cart items",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *                     type="object",
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="product_id", type="integer", example=5),
     *                     @OA\Property(property="quantity", type="integer", example=2),
     *                     @OA\Property(
     *                         property="product",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="name", type="string", example="Product Name"),
     *                         @OA\Property(property="price", type="number", format="float", example=100000),
     *                         @OA\Property(property="stock", type="integer", example=50)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated - Token tidak valid"
     *     )
     * )
     */
    public function index()
    {
        return ApiResponse::success($this->cartService->listCart(auth()->user()));
    }

    /**
     * Add item to cart
     *
     * @OA\Post(
     *     path="/api/cart",
     *     tags={"Cart"},
     *     summary="Tambah produk ke cart",
     *     description="Add produk ke cart atau update quantity jika produk sudah ada di cart",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"product_id", "quantity"},
     *
     *             @OA\Property(property="product_id", type="integer", example=5, description="ID produk yang mau ditambahkan"),
     *             @OA\Property(property="quantity", type="integer", example=2, minimum=1, description="Jumlah quantity (minimal 1)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Item berhasil ditambahkan ke cart",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Item added to cart"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="product_id", type="integer", example=5),
     *                 @OA\Property(property="quantity", type="integer", example=2)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Product tidak ada atau stok tidak cukup"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function add(AddCartRequest $request)
    {
        return ApiResponse::success(
            $this->cartService->addItem(auth()->user(), $request->product_id, $request->quantity),
            'Item added to cart');
    }

    /**
     * Update cart item quantity
     *
     * @OA\Put(
     *     path="/api/cart/{product}",
     *     tags={"Cart"},
     *     summary="Update quantity item di cart",
     *     description="Update jumlah quantity untuk produk tertentu di cart",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         required=true,
     *         description="Product ID yang mau diupdate",
     *
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"quantity"},
     *
     *             @OA\Property(property="quantity", type="integer", example=5, minimum=1, description="Quantity baru")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Quantity berhasil diupdate",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Item updated")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Item tidak ditemukan di cart"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Quantity melebihi stok"
     *     )
     * )
     */
    public function update(UpdateCartRequest $request, $productId)
    {
        return ApiResponse::success(
            $this->cartService->updateItem(auth()->user(), $productId, $request->quantity),
            'Item updated');
    }

    /**
     * Remove item from cart
     *
     * @OA\Delete(
     *     path="/api/cart/{product}",
     *     tags={"Cart"},
     *     summary="Hapus item dari cart",
     *     description="Hapus produk tertentu dari cart",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         required=true,
     *         description="Product ID yang mau dihapus",
     *
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Item berhasil dihapus dari cart",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Item removed from cart")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Item tidak ditemukan di cart"
     *     )
     * )
     */
    public function remove($productId)
    {
        $deleted = $this->cartService->removeItem(auth()->user(), $productId);

        if (! $deleted) {
            return ApiResponse::error('Item not found', 404);
        }

        return ApiResponse::success(null, 'Item removed from cart');
    }

    /**
     * Clear all cart items
     *
     * @OA\Delete(
     *     path="/api/cart/clear",
     *     tags={"Cart"},
     *     summary="Kosongkan semua isi cart",
     *     description="Hapus semua item dari cart user",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Cart berhasil dikosongkan",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cart cleared")
     *         )
     *     )
     * )
     */
    public function clear()
    {
        $this->cartService->clearCart(auth()->user());

        return ApiResponse::success(null, 'Cart cleared');
    }
}
