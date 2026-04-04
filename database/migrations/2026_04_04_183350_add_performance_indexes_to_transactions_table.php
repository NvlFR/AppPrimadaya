<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan index performa pada kolom yang sering digunakan untuk filter, sorting, dan JOIN.
     * Tanpa index ini, query bisa melakukan full-table-scan yang lambat saat data besar.
     */
    public function up(): void
    {
        // Index untuk tabel transactions — digunakan saat filter, laporan, dan list transaksi
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('status');                    // Filter berdasarkan status pesanan
            $table->index('created_at');                // Sorting dan filter laporan harian/bulanan
            $table->index('user_id');                   // JOIN ke kasir
            $table->index('customer_id');               // JOIN ke pelanggan
            $table->index(['status', 'created_at']);    // Filter gabungan status + tanggal
        });

        // Index untuk tabel transaction_items
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->index('transaction_id');            // JOIN ke header transaksi
            $table->index('service_id');                // JOIN ke layanan
        });

        // Index untuk tabel expenses — digunakan di laporan keuangan
        Schema::table('expenses', function (Blueprint $table) {
            $table->index('expense_date');              // Filter tanggal laporan
        });
    }

    /**
     * Membalik perubahan dengan menghapus index yang dibuat.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropIndex(['transaction_id']);
            $table->dropIndex(['service_id']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['expense_date']);
        });
    }
};
