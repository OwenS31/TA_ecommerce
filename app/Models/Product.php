<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'price_per_m2',
        'description',
        'image',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_m2' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function rolls(): HasMany
    {
        return $this->hasMany(ProductRoll::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getTotalStockAttribute(): float
    {
        return $this->rolls()->sum('area');
    }
}
