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
     * Admin: List all products
     *
     * @OA\Get(
     *     path="/api/admin/products",
     *     tags={"Admin - Products"},
     *     summary="Admin - Lihat semua produk",
     *     description="Get semua produk untuk admin (termasuk yang out of stock)",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success - Return all products",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Products fetched successfully for admin."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *                     type="object",
     *
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="name", type="string", example="Gaming Laptop"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="price", type="number", format="float", example=15000000),
     *                     @OA\Property(property="stock", type="integer", example=10),
     *                     @OA\Property(
     *                         property="images",
     *                         type="array",
     *
     *                         @OA\Items(
     *                             type="object",
     *
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="image_url", type="string")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Hanya admin yang bisa akses"
     *     )
     * )
     */
    public function index()
    {
        $products = Product::with('images')->latest()->get();

        return ApiResponse::success($products, 'Products fetched successfully for admin.');
    }

    /**
     * Admin: Create new product
     *
     * @OA\Post(
     *     path="/api/admin/products",
     *     tags={"Admin - Products"},
     *     summary="Admin - Buat produk baru",
     *     description="Create produk baru dengan upload gambar (multipart/form-data)",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 required={"name", "price", "stock"},
     *
     *                 @OA\Property(property="name", type="string", example="Gaming Laptop"),
     *                 @OA\Property(property="description", type="string", example="High-end gaming laptop with RTX 4090"),
     *                 @OA\Property(property="price", type="number", format="float", example=15000000),
     *                 @OA\Property(property="stock", type="integer", example=10),
     *                 @OA\Property(
     *                     property="images[]",
     *                     type="array",
     *                     description="Upload multiple images (optional)",
     *
     *                     @OA\Items(type="string", format="binary")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product created successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="name", type="string", example="Gaming Laptop"),
     *                 @OA\Property(property="price", type="number", format="float", example=15000000),
     *                 @OA\Property(property="stock", type="integer", example=10)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin only"
     *     )
     * )
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
     * Admin: Get single product detail
     *
     * @OA\Get(
     *     path="/api/admin/products/{product}",
     *     tags={"Admin - Products"},
     *     summary="Admin - Detail 1 produk",
     *     description="Get detail produk termasuk semua gambar",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         required=true,
     *         description="Product ID",
     *
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product fetched successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="name", type="string", example="Gaming Laptop"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="price", type="number", format="float", example=15000000),
     *                 @OA\Property(property="stock", type="integer", example=10),
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *
     *                     @OA\Items(
     *                         type="object",
     *
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="image_url", type="string")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin only"
     *     )
     * )
     */
    public function show(Product $product)
    {
        $product->load('images');

        return ApiResponse::success($product, 'Product fetched successfully.');
    }

    /**
     * Admin: Update product
     *
     * @OA\Post(
     *     path="/api/admin/products/{product}",
     *     tags={"Admin - Products"},
     *     summary="Admin - Update produk",
     *     description="Update product data, upload new images, atau delete existing images. NOTE: Gunakan POST dengan _method=PUT untuk support file upload",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         required=true,
     *         description="Product ID",
     *
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="_method", type="string", example="PUT", description="Laravel method spoofing"),
     *                 @OA\Property(property="name", type="string", example="Gaming Laptop Updated"),
     *                 @OA\Property(property="description", type="string", example="Updated description"),
     *                 @OA\Property(property="price", type="number", format="float", example=14000000),
     *                 @OA\Property(property="stock", type="integer", example=5),
     *                 @OA\Property(
     *                     property="images[]",
     *                     type="array",
     *                     description="Upload new images (optional)",
     *
     *                     @OA\Items(type="string", format="binary")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="images_to_delete[]",
     *                     type="array",
     *                     description="Array of image IDs to delete (optional)",
     *
     *                     @OA\Items(type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product updated successfully.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin only"
     *     )
     * )
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
     * Admin: Delete product
     *
     * @OA\Delete(
     *     path="/api/admin/products/{product}",
     *     tags={"Admin - Products"},
     *     summary="Admin - Hapus produk",
     *     description="Delete produk beserta semua gambarnya",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         required=true,
     *         description="Product ID",
     *
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product deleted successfully.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin only"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to delete product"
     *     )
     * )
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
