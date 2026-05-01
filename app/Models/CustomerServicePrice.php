<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerServicePrice extends Model
{
    protected $fillable = [
        'customer_id',
        'service_id',
        'custom_price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'custom_price' => 'decimal:2',
        ];
    }

    /**
     * Pelanggan yang memiliki harga khusus ini.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Layanan yang dikenakan harga khusus ini.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
