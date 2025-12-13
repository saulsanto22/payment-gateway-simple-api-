<?php

namespace App\Repositories;

use App\Models\ProductImage;

class ProductImageRepository
{
    /**
     * Membuat record gambar produk baru.
     */
    public function create(array $data): ProductImage
    {
        return ProductImage::create($data);
    }
}
