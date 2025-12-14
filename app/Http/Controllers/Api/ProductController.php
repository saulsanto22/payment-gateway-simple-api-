<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Get product list with search & filter
     * 
     * @OA\Get(
     *     path="/api/products",
     *     tags={"Products"},
     *     summary="Lihat daftar produk (Public)",
     *     description="Get semua produk dengan filter, search, dan sort. Endpoint ini PUBLIC (tanpa auth)",
     *     
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Items per page (default 15, max 100)",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=false,
     *         description="Search by product name",
     *         @OA\Schema(type="string", example="laptop")
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         required=false,
     *         description="Filter minimum price",
     *         @OA\Schema(type="number", format="float", example=100000)
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         required=false,
     *         description="Filter maximum price",
     *         @OA\Schema(type="number", format="float", example=500000)
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         required=false,
     *         description="Sort by field (name, price, created_at)",
     *         @OA\Schema(type="string", example="price")
     *     ),
     *     @OA\Parameter(
     *         name="sort_dir",
     *         in="query",
     *         required=false,
     *         description="Sort direction (asc or desc)",
     *         @OA\Schema(type="string", enum={"asc", "desc"}, example="asc")
     *     ),
     *     
     *     @OA\Response(
     *         response=200,
     *         description="Success - Return paginated products",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="name", type="string", example="Gaming Laptop"),
     *                         @OA\Property(property="description", type="string", example="High-end gaming laptop"),
     *                         @OA\Property(property="price", type="number", format="float", example=15000000),
     *                         @OA\Property(property="stock", type="integer", example=10),
     *                         @OA\Property(
     *                             property="images",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="image_url", type="string", example="http://example.com/image.jpg")
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        // Ambil parameter filter & sort dari query string
        $perPage = (int) ($request->get('per_page', 15));
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 15;

        $q = $request->get('q');
        $minPrice = $request->has('min_price') ? (float) $request->get('min_price') : null;
        $maxPrice = $request->has('max_price') ? (float) $request->get('max_price') : null;
        $sortBy = $request->get('sort_by');
        $sortDir = $request->get('sort_dir');

        $products = $this->productRepository->search($perPage, $q, $minPrice, $maxPrice, $sortBy, $sortDir);

        return ApiResponse::success($products);
    }
}
