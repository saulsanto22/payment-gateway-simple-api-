<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Menampilkan daftar semua produk untuk admin, beserta gambarnya.
     */
    public function index()
    {
        $products = Product::with('images')->latest()->get();

        return ApiResponse::success($products, 'Products fetched successfully for admin.');
    }

    /**
     * Menyimpan produk baru yang dibuat oleh admin menggunakan ProductService.
     */
    public function store(StoreProductRequest $request)
    {
        $validatedData = $request->validated();
        $productData = collect($validatedData)->except('images')->toArray();
        $images = $request->file('images');

        try {
            $product = $this->productService->createProductWithImages($productData, $images);

            return ApiResponse::success($product, 'Product created successfully.', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create product: '.$e->getMessage(), 500);
        }
    }

    /**
     * Menampilkan detail satu produk, beserta gambarnya.
     */
    public function show(Product $product)
    {
        $product->load('images');

        return ApiResponse::success($product, 'Product fetched successfully.');
    }

    /**
     * Mengupdate produk menggunakan ProductService.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $validatedData = $request->validated();

        $productData = collect($validatedData)->except(['images', 'images_to_delete'])->toArray();
        $newImages = $request->file('images');
        $imagesToDeleteIds = $request->input('images_to_delete');

        try {
            $updatedProduct = $this->productService->updateProduct(
                $product,
                $productData,
                $newImages,
                $imagesToDeleteIds
            );

            return ApiResponse::success($updatedProduct, 'Product updated successfully.');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update product: '.$e->getMessage(), 500);
        }
    }

    /**
     * Menghapus produk menggunakan ProductService.
     */
    public function destroy(Product $product)
    {
        try {
            $this->productService->deleteProduct($product);

            return ApiResponse::success(null, 'Product deleted successfully.');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete product: '.$e->getMessage(), 500);
        }
    }
}
