<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel ini menyimpan harga khusus (override) per pelanggan per layanan.
     * Jika ada record di sini, harga di kasir akan otomatis ter-override.
     */
    public function up(): void
    {
        Schema::create('customer_service_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->decimal('custom_price', 12, 2)->comment('Harga khusus yang menggantikan base_price untuk pelanggan ini');
            $table->text('notes')->nullable()->comment('Catatan alasan pemberian harga khusus');
            $table->timestamps();

            // Satu pelanggan hanya bisa punya satu harga khusus per layanan
            $table->unique(['customer_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_service_prices');
    }
};
