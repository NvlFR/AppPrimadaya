<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'transaction_number',
        'customer_id',
        'user_id',
        'subtotal',
        'discount_percent',
        'discount_amount',
        'total',
        'payment_method',
        'amount_paid',
        'change_amount',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'change_amount' => 'decimal:2',
        ];
    }

    /**
     * Label tampilan untuk setiap status pesanan.
     */
    public const STATUS_LABELS = [
        'pending'   => 'Pending',
        'diproses'  => 'Diproses',
        'selesai'   => 'Selesai',
        'diambil'   => 'Diambil',
    ];

    /**
     * Warna badge untuk setiap status.
     */
    public const STATUS_COLORS = [
        'pending'   => 'warning',
        'diproses'  => 'info',
        'selesai'   => 'success',
        'diambil'   => 'default',
    ];

    /**
     * Mendapatkan pelanggan yang terkait dengan transaksi ini.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Mendapatkan kasir yang membuat transaksi ini.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mendapatkan semua item dalam transaksi ini.
     */
    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    /**
     * Mendapatkan label tampilan untuk status saat ini.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Mendapatkan warna badge untuk status saat ini.
     */
    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'default';
    }
}
