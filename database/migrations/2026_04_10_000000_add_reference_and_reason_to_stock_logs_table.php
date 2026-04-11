<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan referensi polimorfik dan alasan perubahan stok pada stock_logs.
     */
    public function up(): void
    {
        Schema::table('stock_logs', function (Blueprint $table) {
            $table->string('reference_type')->nullable()->after('qty_after');
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            $table->enum('reason', ['rusak', 'kadaluarsa', 'salah_input', 'koreksi', 'lainnya'])
                ->nullable()
                ->after('reference_id');

            $table->index(['reference_type', 'reference_id'], 'stock_logs_reference_index');
        });
    }

    public function down(): void
    {
        Schema::table('stock_logs', function (Blueprint $table) {
            $table->dropIndex('stock_logs_reference_index');
            $table->dropColumn(['reference_type', 'reference_id', 'reason']);
        });
    }
};
