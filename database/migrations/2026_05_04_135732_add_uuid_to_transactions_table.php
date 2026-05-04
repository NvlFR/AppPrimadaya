<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $col) {
            $col->uuid('uuid')->nullable()->unique()->after('id');
        });

        // Isi UUID buat data yang udah ada
        DB::table('transactions')->whereNull('uuid')->get()->each(function ($trx) {
            DB::table('transactions')->where('id', $trx->id)->update(['uuid' => (string) Str::uuid()]);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $col) {
            $col->dropColumn('uuid');
        });
    }
};
