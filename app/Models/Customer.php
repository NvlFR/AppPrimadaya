<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'notes',
    ];

    /**
     * Mendapatkan semua transaksi milik pelanggan ini.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Mendapatkan harga kustom untuk layanan pelanggan ini.
     */
    public function customServicePrices(): HasMany
    {
        return $this->hasMany(CustomerServicePrice::class);
    }

    /**
     * Mendapatkan harga kustom untuk layanan tertentu.
     */
    public function getCustomPriceForService(int $serviceId, float $defaultPrice): float
    {
        return $this->customServicePrices()
            ->where('service_id', $serviceId)
            ->value('price') ?? $defaultPrice;
    }

    /**
     * Menghitung total nilai transaksi pelanggan ini.
     */
    public function getTotalTransactionsAmountAttribute(): float
    {
        return $this->transactions()->sum('total');
    }
}
