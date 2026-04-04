<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel stock_logs untuk mencatat riwayat perubahan stok masuk/keluar.
     */
    public function up(): void
    {
        Schema::create('stock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['masuk', 'keluar'])->default('masuk');
            $table->decimal('qty', 10, 2);
            $table->decimal('qty_before', 10, 2)->default(0); // stok sebelum perubahan
            $table->decimal('qty_after', 10, 2)->default(0);  // stok setelah perubahan
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_logs');
    }
};
