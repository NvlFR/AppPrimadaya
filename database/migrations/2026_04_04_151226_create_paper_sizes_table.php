<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel paper_sizes untuk pilihan ukuran kertas pada layanan cetak.
     */
    public function up(): void
    {
        Schema::create('paper_sizes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // A4, A3, F4, A5, Banner, dll
            $table->string('width')->nullable();  // dalam cm (opsional)
            $table->string('height')->nullable(); // dalam cm (opsional)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paper_sizes');
    }
};
