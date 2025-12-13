<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService; // <-- Menggunakan ProductService
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    protected $productService;

    // Menyuntikkan ProductService ke dalam controller
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Menampilkan daftar semua produk untuk admin, beserta gambarnya.
     */
    public function index()
    {
        // Untuk saat ini, kita bisa tetap menggunakan query sederhana atau memindahkannya ke service/repository jika lebih kompleks
        $products = Product::with('images')->latest()->get();
        return ApiResponse::success($products, 'Products fetched successfully for admin.');
    }

    /**
     * Menyimpan produk baru yang dibuat oleh admin menggunakan ProductService.
     */
    public function store(StoreProductRequest $request)
    {
        $validatedData = $request->validated();
        
        // Pisahkan data produk dan file gambar
        $productData = collect($validatedData)->except('images')->toArray();
        $images = $request->file('images');

        try {
            // Panggil service untuk menangani logika pembuatan produk dan gambar
            $product = $this->productService->createProductWithImages($productData, $images);
            
            return ApiResponse::success($product, 'Product created successfully.', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create product: ' . $e->getMessage(), 500);
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
     * Mengupdate data produk yang ada.
     * (Update logic akan memerlukan metode service-nya sendiri nanti)
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        // Untuk saat ini, kita biarkan sederhana. Nanti bisa dipindah ke service.
        $product->update($request->validated());
        $product->load('images');
        return ApiResponse::success($product, 'Product updated successfully.');
    }

    /**
     * Menghapus produk dari database.
     * (Delete logic juga bisa dipindahkan ke ProductService)
     */
    public function destroy(Product $product)
    {
        DB::transaction(function () use ($product) {
            foreach ($product->images as $image) {
                $storagePath = str_replace('/storage', 'public', $image->image_path);
                Storage::delete($storagePath);
                $image->delete();
            }
            $product->delete();
        });

        return ApiResponse::success(null, 'Product deleted successfully.');
    }
}
