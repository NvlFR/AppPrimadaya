<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use SoftDeletes;

    /**
     * Daftarkan event model.
     */
    protected static function booted(): void
    {
        // Hapus file fisik saat transaksi dihapus (soft delete maupun permanent)
        static::deleting(function (Transaction $transaction) {
            if ($transaction->isForceDeleting() || ! method_exists($transaction, 'isForceDeleting')) {
                Storage::disk('public')->deleteDirectory("orders/{$transaction->id}");
            }
        });
    }

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
        'payment_status',
        'dp_amount',
        'remaining_amount',
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
            'dp_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
        ];
    }

    /**
     * Label tampilan untuk setiap status pesanan (produksi).
     */
    public const STATUS_LABELS = [
        'pending' => 'Pending',
        'diproses' => 'Diproses',
        'selesai' => 'Selesai',
        'diambil' => 'Diambil',
    ];

    /**
     * Warna badge untuk setiap status pesanan.
     */
    public const STATUS_COLORS = [
        'pending' => 'warning',
        'diproses' => 'info',
        'selesai' => 'success',
        'diambil' => 'default',
    ];

    /**
     * Label tampilan untuk setiap status pembayaran.
     */
    public const PAYMENT_STATUS_LABELS = [
        'belum_bayar' => 'Belum Bayar',
        'dp' => 'DP (Uang Muka)',
        'lunas' => 'Lunas',
    ];

    /**
     * Warna badge untuk setiap status pembayaran.
     */
    public const PAYMENT_STATUS_COLORS = [
        'belum_bayar' => 'destructive',
        'dp' => 'warning',
        'lunas' => 'success',
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
     * Mendapatkan label tampilan untuk status pesanan saat ini.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Mendapatkan warna badge untuk status pesanan saat ini.
     */
    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'default';
    }

    /**
     * Mendapatkan label tampilan untuk status pembayaran saat ini.
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return self::PAYMENT_STATUS_LABELS[$this->payment_status] ?? $this->payment_status;
    }

    /**
     * Mendapatkan warna badge untuk status pembayaran saat ini.
     */
    public function getPaymentStatusColorAttribute(): string
    {
        return self::PAYMENT_STATUS_COLORS[$this->payment_status] ?? 'default';
    }

    /**
     * Cek apakah transaksi sudah lunas.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'lunas';
    }

    /**
     * Cek apakah transaksi belum ada pembayaran sama sekali.
     */
    public function isUnpaid(): bool
    {
        return $this->payment_status === 'belum_bayar';
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
