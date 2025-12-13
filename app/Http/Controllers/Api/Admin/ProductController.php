<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Repositories\ProductRepository;

class ProductController extends Controller
{
    protected $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Menampilkan daftar semua produk untuk admin.
     */
    public function index()
    {
        $products = $this->productRepository->getAll();
        return ApiResponse::success($products, 'Products fetched successfully for admin.');
    }

    /**
     * Menyimpan produk baru yang dibuat oleh admin.
     */
    public function store(StoreProductRequest $request)
    {
        // Data yang masuk dijamin sudah valid karena melewati StoreProductRequest
        $validatedData = $request->validated();
        $product = $this->productRepository->create($validatedData);

        return ApiResponse::success($product, 'Product created successfully.', 201);
    }

    /**
     * Menampilkan detail satu produk.
     */
    public function show(Product $product)
    {
        return ApiResponse::success($product, 'Product fetched successfully.');
    }

    /**
     * Mengupdate data produk yang ada.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        // Data yang masuk dijamin sudah valid karena melewati UpdateProductRequest
        $validatedData = $request->validated();
        $updatedProduct = $this->productRepository->update($product, $validatedData);

        return ApiResponse::success($updatedProduct, 'Product updated successfully.');
    }

    /**
     * Menghapus produk dari database.
     */
    public function destroy(Product $product)
    {
        // Kita bisa tambahkan otorisasi di sini juga jika diperlukan, 
        // tapi untuk sekarang kita akan letakkan di route.
        $this->productRepository->delete($product);

        return ApiResponse::success(null, 'Product deleted successfully.');
    }
}
