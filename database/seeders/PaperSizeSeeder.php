<?php

namespace Database\Seeders;

use App\Models\PaperSize;
use Illuminate\Database\Seeder;

class PaperSizeSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = [
            ['name' => 'A4', 'width' => '21.0', 'height' => '29.7'],
            ['name' => 'F4 (Folio)', 'width' => '21.5', 'height' => '33.0'],
            ['name' => 'A3', 'width' => '29.7', 'height' => '42.0'],
            ['name' => 'A3+', 'width' => '32.0', 'height' => '48.0'],
            ['name' => 'A5', 'width' => '14.8', 'height' => '21.0'],
            ['name' => 'B5', 'width' => '17.6', 'height' => '25.0'],
        ];

        foreach ($sizes as $size) {
            PaperSize::firstOrCreate(['name' => $size['name']], $size);
        }
    }
}
