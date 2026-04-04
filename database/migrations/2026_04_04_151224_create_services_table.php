<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel services untuk katalog layanan percetakan.
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('category', ['print', 'banner', 'foto', 'fotocopy', 'laminasi', 'lainnya'])->default('print');
            $table->decimal('base_price', 12, 2)->default(0); // harga dasar / fallback
            $table->string('unit')->default('lembar'); // lembar, meter, pcs, eks, buku, dll
            $table->boolean('has_matrix_pricing')->default(false); // apakah punya pricing matrix
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
