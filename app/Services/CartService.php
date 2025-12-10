<?php

namespace App\Services;

use App\Repositories\CartRepository;

class CartService
{
    protected $cartRepository;

    public function __construct(CartRepository $cartRepository)
    {
        $this->cartRepository = $cartRepository;
    }

    public function listCart($user)
    {
        return $this->cartRepository->getUserCart($user->id);
    }

    public function addItem($user, $productId, $quantity)
    {
        return $this->cartRepository->addOrUpdate($user->id, $productId, $quantity);
    }

    public function removeItem($user, $productId)
    {
        return $this->cartRepository->remove($user->id, $productId);
    }
}
