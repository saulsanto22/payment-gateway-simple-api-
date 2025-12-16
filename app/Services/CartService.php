<?php

namespace App\Services;

use App\Exceptions\InvalidQuantityException;
use App\Exceptions\OutOfStockException;
use App\Exceptions\ProductNotFoundException;
use App\Repositories\CartRepository;
use App\Repositories\ProductRepository;

class CartService
{
    protected $cartRepository;

    protected $productRepository;

    public function __construct(CartRepository $cartRepository, ProductRepository $productRepository)
    {
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
    }

    public function listCart($user)
    {
        return $this->cartRepository->getUserCart($user->id);
    }

    /**
     * Add item to cart with validation
     *
     * @throws ProductNotFoundException
     * @throws InvalidQuantityException
     * @throws OutOfStockException
     */
    public function addItem($user, $productId, $quantity)
    {
        // Validasi 1: Produk harus exist
        $product = $this->productRepository->find($productId);
        if (! $product) {
            throw new ProductNotFoundException("Product with ID {$productId} not found.");
        }

        // Validasi 2: Quantity harus positive
        if ($quantity <= 0) {
            throw new InvalidQuantityException('Quantity must be greater than 0.');
        }

        // Validasi 3: Stock harus mencukupi
        if ($quantity > $product->stock) {
            throw new OutOfStockException("Only {$product->stock} items available for {$product->name}.");
        }

        return $this->cartRepository->addOrUpdate($user->id, $productId, $quantity);
    }

    /**
     * Update item quantity in cart with validation
     *
     * @throws ProductNotFoundException
     * @throws InvalidQuantityException
     * @throws OutOfStockException
     */
    public function updateItem($user, $productId, $quantity)
    {
        // Validasi sama seperti addItem
        $product = $this->productRepository->find($productId);
        if (! $product) {
            throw new ProductNotFoundException("Product with ID {$productId} not found.");
        }

        if ($quantity <= 0) {
            throw new InvalidQuantityException('Quantity must be greater than 0.');
        }

        if ($quantity > $product->stock) {
            throw new OutOfStockException("Only {$product->stock} items available for {$product->name}.");
        }

        return $this->cartRepository->addOrUpdate($user->id, $productId, $quantity);
    }

    public function removeItem($user, $productId)
    {
        return $this->cartRepository->remove($user->id, $productId);
    }

    public function clearCart($user)
    {
        return $this->cartRepository->clear($user->id);
    }
}
