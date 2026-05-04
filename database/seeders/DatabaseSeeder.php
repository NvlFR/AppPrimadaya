<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            PaperSizeSeeder::class,
            ServiceSeeder::class,
            StockSeeder::class,
            BusinessDataSeeder::class,
            // BigDataSeeder::class, // Aktifkan hanya jika butuh 1jt data demo
        ]);
    }
}
