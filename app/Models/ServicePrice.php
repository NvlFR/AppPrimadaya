<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePrice extends Model
{
    protected $fillable = [
        'service_id',
        'paper_size_id',
        'print_type',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    /**
     * Mendapatkan layanan yang memiliki pricing ini.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Mendapatkan ukuran kertas untuk pricing ini.
     */
    public function paperSize(): BelongsTo
    {
        return $this->belongsTo(PaperSize::class);
    }
}
