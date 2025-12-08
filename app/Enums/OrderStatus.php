<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case CANCELLED = 'cancel';
    case EXPIRED = 'expired';

}
