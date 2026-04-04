<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel stocks untuk manajemen stok bahan percetakan.
     */
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // contoh: Kertas HVS A4, Tinta Hitam Epson
            $table->enum('category', ['kertas', 'tinta', 'bahan', 'lainnya'])->default('bahan');
            $table->string('unit')->default('rim'); // rim, botol, pcs, roll, dll
            $table->decimal('current_qty', 10, 2)->default(0);
            $table->decimal('min_qty', 10, 2)->default(0); // threshold alert stok rendah
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
