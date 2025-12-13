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
