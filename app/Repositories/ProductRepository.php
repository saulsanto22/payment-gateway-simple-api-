<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository
{
    /**
     * Listing produk dengan filter dan sort yang aman.
     * Param:
     * - q: cari nama (contains)
     * - min_price, max_price: filter rentang harga
     * - sort_by: name|price|created_at (whitelist)
     * - sort_dir: asc|desc
     * - perPage: 1..100
     */
    public function all($perPage = 15, ?string $q = null, ?float $minPrice = null, ?float $maxPrice = null, ?string $sortBy = null, ?string $sortDir = null)
    {
        $query = Product::query();

        if ($q !== null && $q !== '') {
            // contains; gunakan index name (LIKE '%q%') tidak selalu gunakan index, tapi cukup untuk portfolio
            $query->where('name', 'like', '%'.str_replace('%', '\\%', $q).'%');
        }

        if ($minPrice !== null) {
            $query->where('price', '>=', $minPrice);
        }

        if ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }

        $allowedSortBy = ['name', 'price', 'created_at'];
        $allowedSortDir = ['asc', 'desc'];

        $sortBy = in_array($sortBy, $allowedSortBy, true) ? $sortBy : 'created_at';
        $sortDir = in_array(strtolower((string) $sortDir), $allowedSortDir, true) ? strtolower($sortDir) : 'desc';

        $query->orderBy($sortBy, $sortDir);

        $perPage = (int) $perPage;
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 15;

        return $query->paginate($perPage);
    }
}
