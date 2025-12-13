<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Repositories\ProductRepository;
use App\Repositories\ProductImageRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ProductService
{
    protected $productRepository;
    protected $productImageRepository;

    public function __construct(ProductRepository $productRepository, ProductImageRepository $productImageRepository)
    {
        $this->productRepository = $productRepository;
        $this->productImageRepository = $productImageRepository;
    }

    /**
     * Membuat produk baru beserta gambarnya.
     *
     * @param array $data Data produk dari request.
     * @param array|null $images Array dari file gambar.
     * @return Product
     * @throws \Exception
     */
    public function createProductWithImages(array $data, ?array $images): Product
    {
        return DB::transaction(function () use ($data, $images) {
            $product = $this->productRepository->create($data);

            if (!empty($images)) {
                foreach ($images as $imageFile) {
                    if ($imageFile instanceof UploadedFile) {
                        $path = $imageFile->store('public/products');
                        $this->productImageRepository->create([
                            'product_id' => $product->id,
                            'image_path' => Storage::url($path),
                        ]);
                    }
                }
            }
            
            $product->load('images');
            return $product;
        });
    }

    /**
     * Mengupdate produk, termasuk menambah/menghapus gambar.
     *
     * @param Product $product
     * @param array $productData
     * @param array|null $newImages
     * @param array|null $imagesToDeleteIds
     * @return Product
     * @throws \Exception
     */
    public function updateProduct(Product $product, array $productData, ?array $newImages, ?array $imagesToDeleteIds): Product
    {
        return DB::transaction(function () use ($product, $productData, $newImages, $imagesToDeleteIds) {
            if (!empty($productData)) {
                $this->productRepository->update($product, $productData);
            }

            if (!empty($imagesToDeleteIds)) {
                $imagesToDelete = ProductImage::whereIn('id', $imagesToDeleteIds)->get();
                foreach ($imagesToDelete as $image) {
                    $storagePath = str_replace('/storage', 'public', $image->image_path);
                    Storage::delete($storagePath);
                    $image->delete();
                }
            }

            if (!empty($newImages)) {
                foreach ($newImages as $imageFile) {
                    if ($imageFile instanceof UploadedFile) {
                        $path = $imageFile->store('public/products');
                        $this->productImageRepository->create([
                            'product_id' => $product->id,
                            'image_path' => Storage::url($path),
                        ]);
                    }
                }
            }

            $product->refresh()->load('images');
            return $product;
        });
    }

    /**
     * Menghapus produk beserta semua file gambarnya.
     *
     * @param Product $product
     * @return void
     * @throws \Exception
     */
    public function deleteProduct(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $product->load('images');

            foreach ($product->images as $image) {
                $storagePath = str_replace('/storage', 'public', $image->image_path);
                Storage::delete($storagePath);
            }

            $this->productRepository->delete($product);
        });
    }
}
