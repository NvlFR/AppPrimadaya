<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BigDataSeeder extends Seeder
{
    /**
     * Run the database seeds for 1 Million total records.
     * High Performance Seeder using Bulk Inserts.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID'); // Pakai locale Indonesia
        
        // 1. Matikan pengecekan foreign key biar kencang
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Bersihkan data lama (opsional, tapi seeder ini untuk demo data banyak)
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

        // 2. Jalankan Seeder Master Data (Wajib Ada)
        $this->command->info('Menyiapkan master data (User, Service, dll)...');
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            PaperSizeSeeder::class,
            StockSeeder::class,
            ServiceSeeder::class,
        ]);

        // Ambil ID yang dibutuhkan
        $userIds = DB::table('users')->pluck('id')->toArray();
        $serviceIds = DB::table('services')->pluck('id')->toArray();
        $paperSizeIds = DB::table('paper_sizes')->pluck('id')->toArray();

        // --- 1. SEED CUSTOMERS (10,000) --- //
        $this->command->info('Seeding 10,000 customers...');
        $customers = [];
        for ($i = 0; $i < 10000; $i++) {
            $customers[] = [
                'name' => $faker->name,
                'phone' => '08' . $faker->numerify('##########'),
                'address' => $faker->address,
                'notes' => $faker->boolean(20) ? $faker->sentence : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            if (count($customers) >= 1000) {
                DB::table('customers')->insert($customers);
                $customers = [];
            }
        }
        $customerIds = DB::table('customers')->pluck('id')->toArray();

        // --- 2. SEED TRANSACTIONS & ITEMS (500,000 Transactions + approx 1M Items) --- //
        $this->command->info('Seeding 300,000 transactions and items (this might take a few minutes)...');
        
        $startDate = Carbon::now()->subMonths(12);
        
        // Kita loop per bulan biar nggak lemot memorynya
        for ($m = 0; $m < 12; $m++) {
            $monthDate = (clone $startDate)->addMonths($m);
            $this->command->info("Processing month: " . $monthDate->format('F Y'));
            
            $transactionsBatch = [];
            $itemsBatch = [];
            
            // Loop harian (rata-rata 800 - 1000 transaksi per hari untuk dapet total ~300k setahun)
            $daysInMonth = $monthDate->daysInMonth;
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $currentDate = (clone $monthDate)->day($d);
                $dailyCount = rand(800, 1000);
                
                for ($index = 1; $index <= $dailyCount; $index++) {
                    $trxNumber = "TRX-" . $currentDate->format('Ymd') . "-" . str_pad($index, 4, '0', STR_PAD_LEFT);
                    $subtotal = 0;
                    
                    // Create items first to calculate transaction total
                    $itemsInTransaction = rand(1, 3);
                    $tempItems = [];
                    for ($j = 0; $j < $itemsInTransaction; $j++) {
                        $qty = rand(1, 50);
                        $uPrice = rand(1000, 50000);
                        $iSubtotal = $qty * $uPrice;
                        $subtotal += $iSubtotal;
                        
                        $tempItems[] = [
                            'service_id' => $faker->randomElement($serviceIds),
                            'service_name' => $faker->words(3, true),
                            'paper_size_id' => $faker->randomElement($paperSizeIds),
                            'paper_size_name' => $faker->randomElement(['A4', 'A3', 'F4', 'Quto']),
                            'print_type' => $faker->randomElement(['color', 'bw']),
                            'qty' => $qty,
                            'unit_price' => $uPrice,
                            'subtotal' => $iSubtotal,
                            'created_at' => $currentDate,
                            'updated_at' => $currentDate,
                        ];
                    }

                    $discountPercent = $faker->boolean(10) ? rand(5, 20) : 0;
                    $discountAmount = $subtotal * ($discountPercent / 100);
                    $total = $subtotal - $discountAmount;
                    
                    $transactionsBatch[] = [
                        'transaction_number' => $trxNumber,
                        'customer_id' => $faker->boolean(70) ? $faker->randomElement($customerIds) : null,
                        'user_id' => $faker->randomElement($userIds),
                        'subtotal' => $subtotal,
                        'discount_percent' => $discountPercent,
                        'discount_amount' => $discountAmount,
                        'total' => $total,
                        'payment_method' => $faker->randomElement(['cash', 'transfer', 'qris']),
                        'amount_paid' => $total + ($faker->boolean(50) ? 0 : rand(5000, 50000)),
                        'change_amount' => 0, // calculated by logic later but dummy is ok
                        'status' => 'diambil',
                        'notes' => $faker->boolean(10) ? $faker->sentence : null,
                        'created_at' => $currentDate,
                        'updated_at' => $currentDate,
                    ];

                    // Simpan items sementara (perlu ID transaction nanti)
                    // Tapi karena kita insert raw, kita harus insert transaction dulu baru dapet ID-nya
                    // Untuk kecepatan, kita insert batch per hari saja
                }
                
                // --- Insert Batch Harian --- //
                if (count($transactionsBatch) > 0) {
                    // Pakai insertGetId untuk batch itu susah, jadi kita insert per hari saja
                    // Untuk item, kita insert manual (agak sedikit lambat tapi terstruktur)
                    foreach ($transactionsBatch as $trxData) {
                        $tId = DB::table('transactions')->insertGetId($trxData);
                        foreach ($tempItems as $item) {
                            $item['transaction_id'] = $tId;
                            $itemsBatch[] = $item;
                        }
                    }
                    
                    if (count($itemsBatch) > 2000) {
                        DB::table('transaction_items')->insert($itemsBatch);
                        $itemsBatch = [];
                    }
                    $transactionsBatch = [];
                }
            }
        }

        // --- 3. SEED EXPENSES (100,000) --- //
        $this->command->info('Seeding 100,000 expenses...');
        $expenses = [];
        for ($i = 0; $i < 100000; $i++) {
            $eDate = Carbon::now()->subDays(rand(0, 365));
            $expenses[] = [
                'user_id' => $faker->randomElement($userIds),
                'category' => $faker->randomElement(['bahan', 'operasional', 'lainnya']),
                'description' => $faker->sentence(5),
                'amount' => rand(10000, 2000000),
                'expense_date' => $eDate,
                'created_at' => $eDate,
                'updated_at' => $eDate,
            ];

            if (count($expenses) >= 1000) {
                DB::table('expenses')->insert($expenses);
                $expenses = [];
            }
        }

        // --- 4. SEED STOCK LOGS (100,000) --- //
        $this->command->info('Seeding 100,000 stock logs...');
        $stockIds = DB::table('stocks')->pluck('id')->toArray();
        if (!empty($stockIds)) {
            $logs = [];
            for ($i = 0; $i < 100000; $i++) {
                $lDate = Carbon::now()->subDays(rand(0, 365));
                $logs[] = [
                    'stock_id' => $faker->randomElement($stockIds),
                    'user_id' => $faker->randomElement($userIds),
                    'type' => $faker->randomElement(['masuk', 'keluar']),
                    'qty' => rand(1, 100),
                    'notes' => $faker->boolean(30) ? $faker->word : null,
                    'created_at' => $lDate,
                    'updated_at' => $lDate,
                ];

                if (count($logs) >= 1000) {
                    DB::table('stock_logs')->insert($logs);
                    $logs = [];
                }
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->command->info('BigDataSeeder selesai! 1jt+ data total berhasil diinjeksi.');
    }
}
