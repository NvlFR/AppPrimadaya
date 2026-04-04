<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'category',
        'description',
        'amount',
        'expense_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    /**
     * Label tampilan untuk setiap kategori pengeluaran.
     */
    public const CATEGORY_LABELS = [
        'bahan'       => 'Bahan Baku',
        'operasional' => 'Operasional',
        'gaji'        => 'Gaji',
        'lainnya'     => 'Lainnya',
    ];

    /**
     * Mendapatkan pengguna yang menginput pengeluaran ini.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mendapatkan label tampilan untuk kategori saat ini.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? $this->category;
    }
}
