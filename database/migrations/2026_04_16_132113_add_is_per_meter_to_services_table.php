<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom is_per_meter ke tabel services.
     * Digunakan untuk layanan seperti Spanduk yang harganya dihitung per meter persegi (lebar × tinggi).
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('is_per_meter')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('is_per_meter');
        });
    }
};
