<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat counter harian nomor transaksi agar aman saat kasir submit bersamaan.
     */
    public function up(): void
    {
        Schema::create('transaction_sequences', function (Blueprint $table) {
            $table->id();
            $table->date('sequence_date')->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });

        $sequences = [];

        DB::table('transactions')
            ->select(['id', 'transaction_number', 'created_at'])
            ->orderBy('id')
            ->chunkById(500, function ($transactions) use (&$sequences) {
                foreach ($transactions as $transaction) {
                    if (! preg_match('/^TRX-\d{8}-(\d{4,})$/', $transaction->transaction_number, $matches)) {
                        continue;
                    }

                    $sequenceDate = Carbon::parse($transaction->created_at)->toDateString();
                    $number = (int) $matches[1];

                    $sequences[$sequenceDate] = max($sequences[$sequenceDate] ?? 0, $number);
                }
            });

        $now = now();

        foreach ($sequences as $sequenceDate => $lastNumber) {
            DB::table('transaction_sequences')->insert([
                'sequence_date' => $sequenceDate,
                'last_number' => $lastNumber,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_sequences');
    }
};
