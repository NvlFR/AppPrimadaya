<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel transactions sebagai header transaksi kasir.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique(); // TRX-YYYYMMDD-XXXX
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // kasir yang input
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->nullable()->default(0);
            $table->decimal('discount_amount', 12, 2)->nullable()->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->enum('payment_method', ['cash', 'transfer', 'qris'])->default('cash');
            $table->decimal('amount_paid', 12, 2)->default(0); // nominal yang dibayarkan
            $table->decimal('change_amount', 12, 2)->default(0); // kembalian
            $table->enum('status', ['pending', 'diproses', 'selesai', 'diambil'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
