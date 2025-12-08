<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository
{
    public function all($perPage = 15)
    {
        return Product::query()->paginate($perPage);
    }
}
