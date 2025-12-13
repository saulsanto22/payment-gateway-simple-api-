<?php

namespace App\Repositories;

use App\Models\Cart;

class CartRepository
{
    public function getUserCart($userId)
    {
        return Cart::with('product')->where('user_id', $userId)->get();
    }

    public function addOrUpdate($userId, $productId, $quantity)
    {
        return Cart::updateOrCreate(
            ['user_id' => $userId, 'product_id' => $productId],
            ['quantity' => $quantity]
        );
    }

    public function remove($userId, $productId)
    {
        $cart = Cart::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if (! $cart) {
            return false;
        }

        return (bool) $cart->delete();
    }

    public function clear($userId)
    {
        return Cart::where('user_id', $userId)->delete();
    }
}
