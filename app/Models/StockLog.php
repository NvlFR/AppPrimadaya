<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLog extends Model
{
    protected $fillable = [
        'stock_id',
        'user_id',
        'type',
        'qty',
        'qty_before',
        'qty_after',
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
}
