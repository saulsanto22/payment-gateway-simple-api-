<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{

    use HasFactory;
    protected $table = 'orders';

    protected $fillable = [
        'user_id', 
        'order_number', // digunakan sebagai order_id di Midtrans
        'total_price',
        'status',
        'snap_token',
        'redirect_url',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class, // cast ke enum agar aman dan konsisten
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
