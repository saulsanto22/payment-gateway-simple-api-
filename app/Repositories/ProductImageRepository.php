<?php

namespace App\Repositories;

use App\Models\ProductImage;

class ProductImageRepository
{
    /**
     * Membuat record gambar produk baru.
     *
     * @param array $data
     * @return ProductImage
     */
    public function create(array $data): ProductImage
    {
        return ProductImage::create($data);
    }
}
