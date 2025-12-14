<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Payment Gateway API",
 *     version="1.0.0",
 *     description="API untuk payment gateway simple dengan integrasi Midtrans.
 *
 *     Features:
 *     - JWT Authentication
 *     - Shopping Cart Management
 *     - Order Processing
 *     - Payment via Midtrans
 *     - Order Reminder (Scheduled)",
 *
 *     @OA\Contact(
 *         name="API Support",
 *         email="support@example.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Development Server"
 * )
 * @OA\Server(
 *     url="https://api.example.com",
 *     description="Production Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter JWT token in format: Bearer {token}"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Endpoints untuk registrasi, login, dan logout user"
 * )
 * @OA\Tag(
 *     name="Cart",
 *     description="Endpoints untuk manage shopping cart (add, update, remove)"
 * )
 * @OA\Tag(
 *     name="Orders",
 *     description="Endpoints untuk checkout dan history order"
 * )
 * @OA\Tag(
 *     name="Webhooks",
 *     description="Payment notification webhook dari Midtrans"
 * )
 * @OA\Tag(
 *     name="Products",
 *     description="Public endpoints untuk lihat produk (tanpa auth)"
 * )
 * @OA\Tag(
 *     name="Admin - Products",
 *     description="Admin endpoints untuk CRUD products (Admin only)"
 * )
 */
class SwaggerController extends Controller
{
    /**
     * Controller ini HANYA untuk menyimpan Swagger annotations.
     *
     * Tidak ada method yang aktual, hanya documentation metadata.
     * Swagger akan scan annotations ini untuk generate API docs.
     *
     * WHY separate file?
     * - Clean separation (docs vs logic)
     * - Easy to find & update
     * - Tidak mengotori controller logic
     */
}
