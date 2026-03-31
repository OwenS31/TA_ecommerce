<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUS_MENUNGGU_PEMBAYARAN = 'menunggu_pembayaran';
    public const STATUS_DIBAYAR = 'dibayar';
    public const STATUS_DIPROSES = 'diproses';
    public const STATUS_DIKIRIM = 'dikirim';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    public const PAYMENT_MENUNGGU = 'menunggu_pembayaran';
    public const PAYMENT_DIBAYAR = 'dibayar';

    protected $fillable = [
        'order_code',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'shipping_city',
        'shipping_province',
        'postal_code',
        'subtotal',
        'shipping_cost',
        'total_amount',
        'payment_status',
        'order_status',
        'payment_confirmed_at',
        'paid_at',
        'order_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'payment_confirmed_at' => 'datetime',
            'paid_at' => 'datetime',
            'order_date' => 'datetime',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_MENUNGGU_PEMBAYARAN,
            self::STATUS_DIBAYAR,
            self::STATUS_DIPROSES,
            self::STATUS_DIKIRIM,
            self::STATUS_SELESAI,
            self::STATUS_DIBATALKAN,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
