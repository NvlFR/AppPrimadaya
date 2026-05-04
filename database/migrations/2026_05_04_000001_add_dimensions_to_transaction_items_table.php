<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom dimensi (lebar & tinggi) ke transaction_items.
     * Digunakan untuk menyimpan ukuran spanduk/banner yang dihitung per meter persegi.
     */
    public function up(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->decimal('width_meter', 8, 2)->nullable()->after('item_notes');
            $table->decimal('height_meter', 8, 2)->nullable()->after('width_meter');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropColumn(['width_meter', 'height_meter']);
        });
    }
};
