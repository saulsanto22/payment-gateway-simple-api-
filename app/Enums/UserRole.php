<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case CUSTOMER = 'customer';
    case MERCHANT = 'merchant';

    /**
     * Get all role values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get role label (for display)
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::CUSTOMER => 'Customer',
            self::MERCHANT => 'Merchant',
        };
    }

    /**
     * Check if role is admin
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Check if role is customer
     */
    public function isCustomer(): bool
    {
        return $this === self::CUSTOMER;
    }

    /**
     * Check if role is merchant
     */
    public function isMerchant(): bool
    {
        return $this === self::MERCHANT;
    }
}
