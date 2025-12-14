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

    /**
     * Checkout cart to create order
     *
     * @OA\Post(
     *     path="/api/orders/checkout",
     *     tags={"Orders"},
     *     summary="Checkout - Buat order dari cart",
     *     description="Proses checkout: validasi stok, buat order, generate payment token Midtrans, kurangi stok produk",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Checkout berhasil - Order created dan payment token generated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Checkout successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="order_number", type="string", example="ORD-1234567890"),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=250000),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="snap_token", type="string", example="abc123def456", description="Token untuk Midtrans Snap payment"),
     *                 @OA\Property(
     *                     property="items",
     *                     type="array",
     *
     *                     @OA\Items(
     *                         type="object",
     *
     *                         @OA\Property(property="product_id", type="integer", example=5),
     *                         @OA\Property(property="product_name", type="string", example="Product Name"),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="price", type="number", format="float", example=100000)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Cart kosong atau stok tidak cukup",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cart is empty.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
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

    /**
     * Get order history
     *
     * @OA\Get(
     *     path="/api/orders/history",
     *     tags={"Orders"},
     *     summary="Lihat riwayat order",
     *     description="Get semua order user dengan pagination",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Jumlah item per page (default 15, max 100)",
     *
     *         @OA\Schema(type="integer", example=15, minimum=1, maximum=100)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success - Return paginated order history",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="History successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *
     *                     @OA\Items(
     *                         type="object",
     *
     *                         @OA\Property(property="id", type="integer", example=10),
     *                         @OA\Property(property="order_number", type="string", example="ORD-1234567890"),
     *                         @OA\Property(property="total_amount", type="number", format="float", example=250000),
     *                         @OA\Property(property="status", type="string", example="pending", description="pending, paid, failed, expired"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2024-03-20T10:30:00Z")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function history(Request $request)
    {
        $perPage = (int) ($request->get('per_page', 15));
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 15;

        $order = $this->orderRepository->getOrderHistory($request->user(), $perPage);

        return ApiResponse::success($order, 'History successfully');
    }

    /**
     * Midtrans payment webhook
     *
     * @OA\Post(
     *     path="/api/midtrans/webhook",
     *     tags={"Webhooks"},
     *     summary="Webhook dari Midtrans",
     *     description="Receive payment notification dari Midtrans, diproses async via queue 'webhooks' (high priority)",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload dari Midtrans payment notification",
     *
     *         @OA\JsonContent(
     *             required={"order_id", "status_code", "gross_amount", "signature_key", "transaction_status"},
     *
     *             @OA\Property(property="order_id", type="string", example="ORD-1234567890", description="Order number"),
     *             @OA\Property(property="status_code", type="string", example="200", description="Status code dari Midtrans"),
     *             @OA\Property(property="gross_amount", type="string", example="250000.00", description="Total amount"),
     *             @OA\Property(property="signature_key", type="string", example="abc123...", description="Signature untuk validasi"),
     *             @OA\Property(property="transaction_status", type="string", example="settlement", description="capture, settlement, pending, deny, expire, cancel"),
     *             @OA\Property(property="fraud_status", type="string", example="accept", description="Optional: accept, challenge, deny")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Webhook diterima dan queued for processing",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Webhook received and queued for processing")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Payload tidak lengkap"
     *     )
     * )
     */
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
