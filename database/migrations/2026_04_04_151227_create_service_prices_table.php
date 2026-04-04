<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel service_prices sebagai pricing matrix:
     * harga per layanan berdasarkan ukuran kertas dan jenis cetak.
     */
    public function up(): void
    {
        Schema::create('service_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('paper_size_id')->nullable()->constrained('paper_sizes')->nullOnDelete();
            $table->enum('print_type', ['color', 'bw', 'na'])->default('na'); // color, black&white, tidak berlaku
            $table->decimal('price', 12, 2);
            $table->timestamps();

            // Kombinasi service + paper_size + print_type harus unik
            $table->unique(['service_id', 'paper_size_id', 'print_type'], 'unique_service_price');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_prices');
    }
};
