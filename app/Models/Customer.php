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
     * Menghitung total nilai transaksi pelanggan ini.
     */
    public function getTotalTransactionsAmountAttribute(): float
    {
        return $this->transactions()->sum('total');
    }
}
