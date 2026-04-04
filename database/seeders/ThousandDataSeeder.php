<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ThousandDataSeeder extends Seeder
{
    /**
     * Run the database seeds for 1,000 records across all tables.
     * Fokus pada kelengkapan data sesuai permintaan.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        // 1. Matikan foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->command->info('Membersihkan data lama...');
        DB::table('transaction_items')->truncate();
        DB::table('transactions')->truncate();
        DB::table('customers')->truncate();
        DB::table('expenses')->truncate();
        DB::table('stock_logs')->truncate();
        DB::table('users')->truncate();
        DB::table('roles')->truncate();
        DB::table('services')->truncate();
        DB::table('paper_sizes')->truncate();
        DB::table('stocks')->truncate();

        // 2. Jalankan Seeder Master Data Dasar
        $this->command->info('Menyiapkan master data (User, Service, dll)...');
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            PaperSizeSeeder::class,
            StockSeeder::class,
            ServiceSeeder::class,
        ]);

        // Ambil data referensi
        $userIds = DB::table('users')->pluck('id')->toArray();
        $serviceIds = DB::table('services')->pluck('id')->toArray();
        $paperSizeIds = DB::table('paper_sizes')->pluck('id')->toArray();
        $stockIds = DB::table('stocks')->pluck('id')->toArray();

        // --- 1. SEED CUSTOMERS (1,000) ---
        $this->command->info('Seeding 1,000 customers...');
        $customers = [];
        for ($i = 0; $i < 1000; $i++) {
            $customers[] = [
                'name' => $faker->name,
                'phone' => '08' . $faker->numerify('##########'),
                'address' => $faker->address,
                'notes' => $faker->boolean(20) ? $faker->sentence : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('customers')->insert($customers);
        $customerIds = DB::table('customers')->pluck('id')->toArray();

        // --- 2. SEED TRANSACTIONS & ITEMS (1,000) ---
        $this->command->info('Seeding 1,000 transactions and items...');
        
        for ($i = 0; $i < 1000; $i++) {
            $currentDate = Carbon::now()->subDays(rand(0, 30));
            $trxNumber = "TRX-" . $currentDate->format('Ymd') . "-" . Str::random(6) . "-" . str_pad($i + 1, 5, '0', STR_PAD_LEFT);
            
            $subtotal = 0;
            $itemsInTransaction = rand(1, 3);
            $transactionItems = [];

            for ($j = 0; $j < $itemsInTransaction; $j++) {
                $qty = rand(1, 20);
                $uPrice = rand(1000, 25000);
                $iSubtotal = $qty * $uPrice;
                $subtotal += $iSubtotal;
                
                $transactionItems[] = [
                    'service_id' => $faker->randomElement($serviceIds),
                    'service_name' => "Layanan " . ($j + 1),
                    'paper_size_id' => $faker->randomElement($paperSizeIds),
                    'paper_size_name' => $faker->randomElement(['A4', 'A3', 'F4']),
                    'print_type' => $faker->randomElement(['color', 'bw']),
                    'qty' => $qty,
                    'unit_price' => $uPrice,
                    'subtotal' => $iSubtotal,
                    'created_at' => $currentDate,
                    'updated_at' => $currentDate,
                ];
            }

            $discountPercent = $faker->boolean(15) ? 10 : 0;
            $discountAmount = $subtotal * ($discountPercent / 100);
            $total = $subtotal - $discountAmount;

            $tId = DB::table('transactions')->insertGetId([
                'transaction_number' => $trxNumber,
                'customer_id' => $faker->randomElement($customerIds),
                'user_id' => $faker->randomElement($userIds),
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'payment_method' => $faker->randomElement(['cash', 'transfer', 'qris']),
                'amount_paid' => $total,
                'change_amount' => 0,
                'status' => 'selesai',
                'created_at' => $currentDate,
                'updated_at' => $currentDate,
            ]);

            foreach ($transactionItems as $item) {
                $item['transaction_id'] = $tId;
                DB::table('transaction_items')->insert($item);
            }
        }

        // --- 3. SEED EXPENSES (1,000) ---
        $this->command->info('Seeding 1,000 expenses...');
        $expenses = [];
        for ($i = 0; $i < 1000; $i++) {
            $eDate = Carbon::now()->subDays(rand(0, 30));
            $expenses[] = [
                'user_id' => $faker->randomElement($userIds),
                'category' => $faker->randomElement(['bahan', 'operasional', 'gaji', 'lainnya']),
                'description' => 'Biaya ' . $faker->words(3, true),
                'amount' => rand(50000, 500000),
                'expense_date' => $eDate,
                'created_at' => $eDate,
                'updated_at' => $eDate,
            ];
            
            if (count($expenses) >= 500) {
                DB::table('expenses')->insert($expenses);
                $expenses = [];
            }
        }
        if (count($expenses) > 0) DB::table('expenses')->insert($expenses);

        // --- 4. SEED STOCK LOGS (1,000) ---
        $this->command->info('Seeding 1,000 stock logs...');
        $logs = [];
        for ($i = 0; $i < 1000; $i++) {
            $lDate = Carbon::now()->subDays(rand(0, 30));
            $logs[] = [
                'stock_id' => $faker->randomElement($stockIds),
                'user_id' => $faker->randomElement($userIds),
                'type' => $faker->randomElement(['masuk', 'keluar']),
                'qty' => rand(1, 50),
                'notes' => 'Log stok otomatis',
                'created_at' => $lDate,
                'updated_at' => $lDate,
            ];

            if (count($logs) >= 500) {
                DB::table('stock_logs')->insert($logs);
                $logs = [];
            }
        }
        if (count($logs) > 0) DB::table('stock_logs')->insert($logs);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->command->info('ThousandDataSeeder Berhasil! Semua tabel terisi 1.000 data.');
    }
}
