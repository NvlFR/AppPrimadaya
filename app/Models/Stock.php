<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    protected $fillable = [
        'name',
        'category',
        'unit',
        'current_qty',
        'min_qty',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'current_qty' => 'decimal:2',
            'min_qty' => 'decimal:2',
        ];
    }

    /**
     * Mendapatkan semua riwayat log stok ini.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(StockLog::class);
    }

    /**
     * Memeriksa apakah stok ini berada di bawah batas minimum.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->current_qty <= $this->min_qty;
    }
}
