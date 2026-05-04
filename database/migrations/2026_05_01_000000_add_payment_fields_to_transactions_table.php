<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom untuk mendukung alur pembayaran terpisah dari pencatatan pesanan.
     * Mendukung fitur DP (Down Payment) dan bayar di akhir.
     *
     * Perubahan:
     * - Tambah payment_status (belum_bayar, dp, lunas)
     * - Tambah dp_amount (nominal DP yang sudah dibayar)
     * - Tambah remaining_amount (sisa tagihan yang belum dibayar)
     * - Ubah payment_method dan amount_paid menjadi nullable
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Status pembayaran (terpisah dari status pesanan/produksi)
            $table->enum('payment_status', ['belum_bayar', 'dp', 'lunas'])
                ->default('belum_bayar')
                ->after('status');

            // Nominal DP yang sudah dibayar (null jika belum DP)
            $table->decimal('dp_amount', 12, 2)->nullable()->default(0)->after('payment_status');

            // Sisa tagihan setelah dikurangi DP
            $table->decimal('remaining_amount', 12, 2)->nullable()->default(0)->after('dp_amount');
        });

        // Update kolom payment_method agar nullable (pesanan bisa disimpan tanpa bayar dulu)
        // Modifikasi enum agar support nullable dengan cara ubah via raw SQL
        DB::statement("ALTER TABLE transactions MODIFY payment_method ENUM('cash','transfer','qris','dp') NULL DEFAULT NULL");
        DB::statement('ALTER TABLE transactions MODIFY amount_paid DECIMAL(12,2) NULL DEFAULT NULL');
        DB::statement('ALTER TABLE transactions MODIFY change_amount DECIMAL(12,2) NULL DEFAULT NULL');

        // Set payment_status = 'lunas' untuk semua transaksi lama yang sudah ada
        // (asumsi semua transaksi lama sudah dibayar karena flow lama adalah bayar langsung)
        DB::table('transactions')->whereNotNull('amount_paid')->update([
            'payment_status' => 'lunas',
            'remaining_amount' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'dp_amount', 'remaining_amount']);
        });

        // Kembalikan ke non-nullable
        DB::statement("ALTER TABLE transactions MODIFY payment_method ENUM('cash','transfer','qris') NOT NULL DEFAULT 'cash'");
        DB::statement('ALTER TABLE transactions MODIFY amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE transactions MODIFY change_amount DECIMAL(12,2) NOT NULL DEFAULT 0');
    }
};
