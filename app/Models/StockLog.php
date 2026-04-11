<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockLog extends Model
{
    protected $fillable = [
        'stock_id',
        'user_id',
        'type',
        'qty',
        'qty_before',
        'qty_after',
        'reference_type',
        'reference_id',
        'reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'qty_before' => 'decimal:2',
            'qty_after' => 'decimal:2',
        ];
    }

    /**
     * Alasan perubahan stok keluar.
     */
    public const REASONS = [
        'rusak' => 'Rusak',
        'kadaluarsa' => 'Kadaluarsa',
        'salah_input' => 'Salah Input',
        'koreksi' => 'Koreksi',
        'lainnya' => 'Lainnya',
    ];

    /**
     * Mendapatkan item stok yang terkait dengan log ini.
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    /**
     * Mendapatkan pengguna yang melakukan perubahan stok.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Referensi polimorfik yang menjelaskan log stok ini.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Label tampilan untuk alasan perubahan stok.
     */
    public function getReasonLabelAttribute(): ?string
    {
        if ($this->reason === null) {
            return null;
        }

        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
