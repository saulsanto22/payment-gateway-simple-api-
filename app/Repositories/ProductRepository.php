<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository
{
    /**
     * Mengambil semua produk.
     * Metode ini lebih cocok untuk backend admin.
     */
    public function getAll(): Collection
    {
        return Product::all();
    }

    /**
     * Membuat produk baru.
     */
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * Mengupdate produk yang ada.
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product;
    }

    /**
     * Menghapus produk.
     */
    public function delete(Product $product): void
    {
        $product->delete();
    }

    /**
     * Listing produk dengan filter dan sort yang aman untuk sisi pengguna.
     */
    public function search($perPage = 15, ?string $q = null, ?float $minPrice = null, ?float $maxPrice = null, ?string $sortBy = null, ?string $sortDir = null)
    {
        $query = Product::query();

        if ($q !== null && $q !== '') {
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

    /**
     * Mencari produk berdasarkan ID dengan lock untuk mencegah race condition.
     */
    public function findWithLock($productId): Product
    {
        return Product::where('id', $productId)->lockForUpdate()->firstOrFail();
    }

    /**
     * Mencari produk berdasarkan ID tanpa lock (untuk read-only operations).
     */
    public function find($productId): ?Product
    {
        return Product::find($productId);
    }
}
