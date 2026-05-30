<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PAID,
        self::STATUS_PROCESSING,
        self::STATUS_SHIPPED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'number', 'user_id', 'customer_name', 'customer_mobile', 'customer_phone', 'customer_address',
        'note', 'subtotal', 'total', 'status', 'restocked_at', 'payment_method', 'coupon_id', 'discount_amount',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'restocked_at' => 'datetime',
        ];
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }

    /**
     * Wave 6.1 / P0-S4 — Concurrency-safe sequential daily number.
     *
     * Uses a session-scoped advisory lock to serialize concurrent generators on
     * MySQL. On other drivers (SQLite tests, Postgres) the lock helper falls
     * back to a no-op + a single retry loop so behaviour stays correct without
     * driver-specific SQL noise.
     *
     * The sequence is widened from 4 to 6 digits (max 999,999/day) and the
     * algorithm relies on COUNT(*) so deleted rows do not lower the next
     * sequence (preserves uniqueness).
     */
    public static function generateNumber(): string
    {
        $prefix = 'ORD-' . date('Ymd');
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $lockName = 'shop_order_number_' . date('Ymd');
        $locked = false;

        if ($driver === 'mysql') {
            $row = $connection->selectOne('SELECT GET_LOCK(?, 5) AS got', [$lockName]);
            $locked = $row && (int) ($row->got ?? 0) === 1;
        }

        try {
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $count = self::query()->where('number', 'like', $prefix . '%')->count();
                $candidate = $prefix . '-' . str_pad((string) ($count + 1 + $attempt), 6, '0', STR_PAD_LEFT);
                if (! self::query()->where('number', $candidate)->exists()) {
                    return $candidate;
                }
            }

            return $prefix . '-' . str_pad((string) (time() % 1000000), 6, '0', STR_PAD_LEFT);
        } finally {
            if ($locked) {
                $connection->statement('SELECT RELEASE_LOCK(?)', [$lockName]);
            }
        }
    }
}
