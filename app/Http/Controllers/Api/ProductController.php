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
        $perPage = (int) ($request->get('per_page', 15));
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 15;

        return ApiResponse::success($this->productRepository->all($perPage));
    }
}
