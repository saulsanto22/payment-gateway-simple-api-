<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use App\Repositories\ProductImageRepository; // Akan dibuat selanjutnya
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
     * @param array $data Data produk dari request yang sudah divalidasi.
     * @param array|null $images Array dari file gambar yang di-upload.
     * @return \App\Models\Product
     * @throws \Exception
     */
    public function createProductWithImages(array $data, ?array $images):
    {
        return DB::transaction(function () use ($data, $images) {
            // 1. Buat produk utama
            $product = $this->productRepository->create($data);

            // 2. Jika ada gambar, proses dan simpan
            if (!empty($images)) {
                foreach ($images as $imageFile) {
                    if ($imageFile instanceof UploadedFile) {
                        // Simpan file ke storage/app/public/products
                        $path = $imageFile->store('public/products');
                        
                        // Buat entri gambar di database melalui repository
                        $this->productImageRepository->create([
                            'product_id' => $product->id,
                            'image_path' => Storage::url($path), // Simpan URL yang bisa diakses publik
                        ]);
                    }
                }
            }
            
            // Muat relasi gambar agar termuat di model produk yang dikembalikan
            $product->load('images');

            return $product;
        });
    }
}
