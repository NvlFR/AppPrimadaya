<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'category',
        'base_price',
        'unit',
        'has_matrix_pricing',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'has_matrix_pricing' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Mendapatkan semua pricing matrix untuk layanan ini.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ServicePrice::class);
    }

    /**
     * Mendapatkan semua item transaksi yang menggunakan layanan ini.
     */
    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    /**
     * Mendapatkan harga berdasarkan ukuran kertas dan jenis cetak.
     */
    public function getPriceFor(?int $paperSizeId, string $printType = 'na'): float
    {
        if ($this->has_matrix_pricing) {
            $price = $this->prices()
                ->where('paper_size_id', $paperSizeId)
                ->where('print_type', $printType)
                ->first();

            return $price ? (float) $price->price : (float) $this->base_price;
        }

        return (float) $this->base_price;
    }
}
