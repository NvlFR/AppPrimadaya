<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

/**
 * Script untuk deploy di Shared Hosting tanpa terminal.
 * Akses: namadomain.com/upgrade.php?key=primadaya123
 */

$expectedKey = 'primadaya123';
$providedKey = $_GET['key'] ?? null;

if ($providedKey !== $expectedKey) {
    http_response_code(403);
    echo 'Akses ditolak.';
    exit;
}

require __DIR__.'/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "<body style='font-family: sans-serif; background: #f4f4f9; padding: 20px;'>";
echo "<div style='max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>";
echo "<h1 style='color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px;'>Primadaya Deployer</h1>";

try {
    echo "<div style='margin-bottom: 20px;'>";
    echo "<h3 style='color: #2563eb;'>Step 1: Migrating Database...</h3>";
    Artisan::call('migrate', ['--force' => true]);
    echo "<pre style='background: #1e293b; color: #f8fafc; padding: 15px; border-radius: 5px; overflow-x: auto;'>".e(Artisan::output()).'</pre>';
    echo '</div>';

    echo "<div style='margin-bottom: 20px;'>";
    echo "<h3 style='color: #2563eb;'>Step 2: Clearing Cache & Optimizing...</h3>";
    Artisan::call('optimize:clear');
    echo "<pre style='background: #1e293b; color: #f8fafc; padding: 15px; border-radius: 5px; overflow-x: auto;'>".e(Artisan::output()).'</pre>';
    echo '</div>';

    echo "<div style='background: #dcfce7; border-left: 5px solid #22c55e; padding: 15px; margin-top: 30px;'>";
    echo "<h3 style='color: #166534; margin: 0;'>Upgrade selesai.</h3>";
    echo "<p style='color: #166534; margin-top: 5px;'>Website sudah memakai versi terbaru. Hapus file ini dari folder public setelah selesai demi keamanan.</p>";
    echo '</div>';
} catch (Throwable $e) {
    http_response_code(500);
    echo "<div style='background: #fee2e2; border-left: 5px solid #ef4444; padding: 15px; margin-top: 30px;'>";
    echo "<h3 style='color: #991b1b; margin: 0;'>Upgrade gagal.</h3>";
    echo "<p style='color: #991b1b; margin-top: 5px;'>Error: ".e($e->getMessage()).'</p>';
    echo '</div>';
}

echo '</div>';
echo '</body>';
