<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaperSize extends Model
{
    protected $fillable = ['name', 'width', 'height'];

    /**
     * Mendapatkan semua pricing yang menggunakan ukuran kertas ini.
     */
    public function servicePrices(): HasMany
    {
        return $this->hasMany(ServicePrice::class);
    }
}
