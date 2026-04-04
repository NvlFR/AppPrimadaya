<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeds for initial stocks.
     */
    public function run(): void
    {
        $stocks = [
            ['name' => 'Kertas A4 80gr', 'category' => 'kertas', 'unit' => 'rim', 'current_qty' => 500, 'min_qty' => 50],
            ['name' => 'Kertas F4 70gr', 'category' => 'kertas', 'unit' => 'rim', 'current_qty' => 300, 'min_qty' => 30],
            ['name' => 'Kertas Foto Glossy A4', 'category' => 'kertas', 'unit' => 'pack', 'current_qty' => 100, 'min_qty' => 10],
            ['name' => 'Tinta Black Bottle (1L)', 'category' => 'tinta', 'unit' => 'botol', 'current_qty' => 20, 'min_qty' => 2],
            ['name' => 'Tinta CMY Bottle (1L Set)', 'category' => 'tinta', 'unit' => 'set', 'current_qty' => 15, 'min_qty' => 2],
            ['name' => 'Spiral Jilid (S/M/L Bundle)', 'category' => 'lainnya', 'unit' => 'pack', 'current_qty' => 45, 'min_qty' => 5],
            ['name' => 'Mika Cover', 'category' => 'lainnya', 'unit' => 'rim', 'current_qty' => 10, 'min_qty' => 2],
        ];

        foreach ($stocks as $stock) {
            DB::table('stocks')->insert(array_merge($stock, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
