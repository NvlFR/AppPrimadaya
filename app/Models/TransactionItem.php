<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    protected $fillable = [
        'transaction_id',
        'service_id',
        'service_name',
        'paper_size_id',
        'paper_size_name',
        'print_type',
        'qty',
        'unit_price',
        'subtotal',
        'file_path',
        'original_filename',
        'item_notes',
        'width_meter',
        'height_meter',
        'price_per_meter',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'qty' => 'integer',
            'width_meter' => 'decimal:2',
            'height_meter' => 'decimal:2',
            'price_per_meter' => 'decimal:2',
        ];
    }

    /**
     * Mendapatkan transaksi induk dari item ini.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Mendapatkan layanan yang digunakan dalam item ini.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Mendapatkan ukuran kertas yang digunakan dalam item ini.
     */
    public function paperSize(): BelongsTo
    {
        return $this->belongsTo(PaperSize::class);
    }

    /**
     * Mendapatkan label tampilan untuk jenis cetak.
     */
    public function getPrintTypeLabelAttribute(): string
    {
        return match ($this->print_type) {
            'color' => 'Warna',
            'bw' => 'Hitam Putih',
            default => '-',
        };
    }
}
