<?php

namespace Database\Seeders;

use App\Models\PaperSize;
use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Service tanpa matrix (harga flat)
        Service::firstOrCreate(['name' => 'Jilid Spiral'], [
            'category' => 'lainnya',
            'base_price' => 15000,
            'unit' => 'buku',
            'has_matrix_pricing' => false,
            'description' => 'Jilid spiral kawat ukuran A4/F4',
        ]);

        Service::firstOrCreate(['name' => 'Laminasi KTP/Card'], [
            'category' => 'laminasi',
            'base_price' => 3000,
            'unit' => 'pcs',
            'has_matrix_pricing' => false,
            'description' => 'Laminasi panas tebal',
        ]);

        // 2. Service dengan matrix (Print Dokumen HVS)
        $printHvs = Service::firstOrCreate(['name' => 'Print Dokumen HVS 80gr'], [
            'category' => 'print',
            'base_price' => 0, // Harga akan ambil dari matrix
            'unit' => 'lembar',
            'has_matrix_pricing' => true,
            'description' => 'Print dokumen hitam putih atau warna di kertas HVS 80gr',
        ]);

        if ($printHvs->wasRecentlyCreated) {
            $a4Id = PaperSize::where('name', 'A4')->value('id');
            $f4Id = PaperSize::where('name', 'F4 (Folio)')->value('id');

            $prices = [
                ['paper_size_id' => $a4Id, 'print_type' => 'bw', 'price' => 500],
                ['paper_size_id' => $a4Id, 'print_type' => 'color', 'price' => 1500],
                ['paper_size_id' => $f4Id, 'print_type' => 'bw', 'price' => 600],
                ['paper_size_id' => $f4Id, 'print_type' => 'color', 'price' => 2000],
            ];

            foreach ($prices as $price) {
                ServicePrice::create(array_merge(['service_id' => $printHvs->id], $price));
            }
        }
        
        // 3. Service dengan matrix (Banner / MMT)
        $printBanner = Service::firstOrCreate(['name' => 'Cetak Banner / MMT 280gr'], [
            'category' => 'banner',
            'base_price' => 25000,
            'unit' => 'meter',
            'has_matrix_pricing' => false,
            'description' => 'Cetak banner outdor bahan MMT tebal 280 gram (Harga per meter persegi)',
        ]);
    }
}
