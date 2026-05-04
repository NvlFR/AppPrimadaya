<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Halaman landing — redirect ke login atau dashboard
Route::get('/', function () {
    return redirect()->route('login');
});

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Routes yang membutuhkan autentikasi
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Transaksi & Kasir
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::patch('/transactions/{transaction}/status', [TransactionController::class, 'updateStatus'])->name('transactions.status');
    Route::post('/transactions/{transaction}/payment', [TransactionController::class, 'processPayment'])->name('transactions.payment');
    Route::get('/transactions/{transaction}/pdf', [TransactionController::class, 'downloadPdf'])->name('transactions.pdf');
    Route::get('/transactions/{transaction}/thermal', [TransactionController::class, 'printThermal'])->name('transactions.thermal');

    // Layanan / Produk (Melihat saja)
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/{service}/prices', [ServiceController::class, 'getPrices'])->name('services.prices');

    // Pelanggan (Kasir bisa Tambah & Lihat)
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    // Endpoint async search untuk combobox pelanggan di halaman kasir (Issue #25)
    // WAJIB dideklarasikan sebelum wildcard {customer} agar tidak tertangkap sebagai ID
    Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    // Endpoint untuk fetch harga khusus pelanggan — digunakan oleh kasir saat customer dipilih
    Route::get('/customers/{customer}/custom-prices', [CustomerController::class, 'getCustomPrices'])->name('customers.custom-prices');

    // Status Pesanan
    Route::get('/orders', [TransactionController::class, 'orders'])->name('orders.index');
    Route::patch('/orders/bulk-status', [TransactionController::class, 'bulkUpdateStatus'])->name('orders.bulk-status');

    // Route yang hanya bisa diakses Admin
    Route::middleware(['role:admin'])->group(function () {

        // Manajemen Layanan & Harga (Hanya Admin)
        Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
        Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
        Route::post('/services/{service}/prices', [ServiceController::class, 'storePrices'])->name('services.prices.store');

        // Manajemen Pelanggan (Hanya Admin yang bisa Hapus)
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

        // Pengeluaran
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

        // Stok Bahan
        Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
        Route::get('/stocks/logs', [StockController::class, 'logs'])->name('stocks.logs');
        Route::post('/stocks', [StockController::class, 'store'])->name('stocks.store');
        Route::patch('/stocks/{stock}', [StockController::class, 'update'])->name('stocks.update');

        // Bulk delete transaksi untuk admin
        Route::delete('/transactions/bulk-destroy', [TransactionController::class, 'bulkDestroy'])->name('transactions.bulk-destroy');
        Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

        // Laporan
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

        // Manajemen User
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});

require __DIR__.'/settings.php';

// Public Invoice Link
Route::get('/invoice/{uuid}', [TransactionController::class, 'publicInvoice'])->name('public.invoice');
Route::get('/invoice/{uuid}/pdf', [TransactionController::class, 'publicPdf'])->name('public.invoice.pdf');
