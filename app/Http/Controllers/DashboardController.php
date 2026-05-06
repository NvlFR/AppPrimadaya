<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard dengan ringkasan statistik harian.
     */
    public function index(Request $request): Response
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $isAdmin = $request->user()?->role?->name === 'admin';
        $monthlyTransactionQuery = Transaction::where('created_at', '>=', $thisMonth)
            ->whereNotIn('status', ['pending']);

        // Jumlah transaksi hari ini
        $todayTransactions = Transaction::whereDate('created_at', $today)->count();

        // Pesanan pending (belum selesai)
        $pendingOrders = Transaction::where('status', 'pending')->count();

        // Pendapatan hari ini (dapat dilihat oleh kasir dan admin)
        $todayRevenue = (float) Transaction::whereDate('created_at', $today)
            ->whereNotIn('status', ['pending'])
            ->sum('total');

        $yesterday = Carbon::yesterday();
        $yesterdayRevenue = (float) Transaction::whereDate('created_at', $yesterday)
            ->whereNotIn('status', ['pending'])
            ->sum('total');

        $revenueGrowth = 0;
        if ($yesterdayRevenue > 0) {
            $revenueGrowth = (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100;
        } elseif ($todayRevenue > 0) {
            $revenueGrowth = 100;
        }

        $recentTransactions = $this->getRecentTransactions();

        $activeOrders = Transaction::with(['customer'])
            ->whereIn('status', ['pending', 'diproses', 'selesai'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 1 WHEN status = 'diproses' THEN 2 WHEN status = 'selesai' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($trx) => [
                'id' => $trx->id,
                'transaction_number' => $trx->transaction_number,
                'customer_name' => $trx->customer?->name ?? 'Umum',
                'status' => $trx->status,
                'status_label' => $trx->status_label,
                'created_at' => $trx->created_at->format('H:i'),
            ]);

        // Total uang yang sudah diterima (Cash/Transfer/QRIS) hari ini, termasuk DP
        $todayPaid = (float) Transaction::whereDate('created_at', $today)
            ->sum('amount_paid');

        // Total piutang (sisa tagihan yang belum dibayar) dari transaksi hari ini
        $todayUnpaid = (float) Transaction::whereDate('created_at', $today)
            ->sum('remaining_amount');

        // Recent Expenses (Last 5)
        $recentExpenses = Expense::latest('expense_date')
            ->take(5)
            ->get();

        // Top Customers This Month
        $topCustomers = DB::table('transactions')
            ->join('customers', 'transactions.customer_id', '=', 'customers.id')
            ->where('transactions.created_at', '>=', $thisMonth)
            ->whereNotIn('transactions.status', ['pending'])
            ->select('customers.name', DB::raw('SUM(transactions.total) as total_spent'), DB::raw('COUNT(transactions.id) as transaction_count'))
            ->groupBy('customers.id', 'customers.name')
            ->orderBy('total_spent', 'desc')
            ->take(5)
            ->get();

        // Total uang diterima bulan ini dari transaksi non-pending
        $monthlyPaid = (float) (clone $monthlyTransactionQuery)
            ->sum('amount_paid');

        // Total piutang bulan ini dari transaksi non-pending
        $monthlyUnpaid = (float) (clone $monthlyTransactionQuery)
            ->sum('remaining_amount');

        // Total omzet bulan ini dari transaksi non-pending
        $monthlyRevenue = (float) (clone $monthlyTransactionQuery)
            ->sum('total');

        $stats = [
            'today_revenue' => $todayRevenue,
            'yesterday_revenue' => $yesterdayRevenue,
            'revenue_growth' => round($revenueGrowth, 1),
            'today_transactions' => $todayTransactions,
            'today_paid' => $todayPaid,
            'today_unpaid' => $todayUnpaid,
            'monthly_paid' => $monthlyPaid,
            'monthly_unpaid' => $monthlyUnpaid,
            'monthly_revenue' => $monthlyRevenue,
            'monthly_expenses' => 0,
            'pending_orders' => $pendingOrders,
            'net_profit' => 0,
        ];

        $salesChart = [];
        $categorySales = collect([]);

        if ($isAdmin) {
            $adminStats = $this->getAdminStats($thisMonth);
            $stats = array_merge($stats, $adminStats);
            $salesChart = $this->getSalesChart();
            $categorySales = $this->getCategorySales($thisMonth);
        }

        // Daftar transaksi lunas hari ini
        $paidTransactions = Transaction::with('customer')
            ->whereDate('created_at', $today)
            ->where('payment_status', 'lunas')
            ->orderBy('created_at', 'desc')
            ->get();

        // Daftar piutang (DP / Belum Bayar) hari ini
        $unpaidTransactions = Transaction::with('customer')
            ->whereDate('created_at', $today)
            ->whereIn('payment_status', ['belum_bayar', 'dp'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Daftar transaksi lunas bulan ini
        $monthlyPaidTransactions = Transaction::with('customer')
            ->where('created_at', '>=', $thisMonth)
            ->whereNotIn('status', ['pending'])
            ->where('payment_status', 'lunas')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Daftar piutang bulan ini
        $monthlyUnpaidTransactions = Transaction::with('customer')
            ->where('created_at', '>=', $thisMonth)
            ->whereNotIn('status', ['pending'])
            ->whereIn('payment_status', ['belum_bayar', 'dp'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'paid_transactions' => $paidTransactions,
            'unpaid_transactions' => $unpaidTransactions,
            'monthly_paid_transactions' => $monthlyPaidTransactions,
            'monthly_unpaid_transactions' => $monthlyUnpaidTransactions,
            'sales_chart' => $salesChart,
            'recent_transactions' => $recentTransactions,
            'active_orders' => $activeOrders,
            'category_sales' => $categorySales,
            'payment_methods' => $this->getPaymentMethodStats($thisMonth),
            'top_services' => $this->getTopServices($thisMonth),
            'recent_expenses' => $recentExpenses,
            'top_customers' => $topCustomers,
            'low_stock_alerts' => \App\Models\Stock::whereColumn('current_qty', '<=', 'min_qty')
                ->take(5)
                ->get(),
        ]);
    }

    private function getRecentTransactions()
    {
        return Transaction::with(['customer', 'user'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($trx) => [
                'id' => $trx->id,
                'transaction_number' => $trx->transaction_number,
                'customer_name' => $trx->customer?->name ?? 'Umum',
                'total' => $trx->total,
                'status' => $trx->status,
                'status_label' => $trx->status_label,
                'created_at' => $trx->created_at->format('d/m/Y H:i'),
            ]);
    }

    private function getAdminStats(Carbon $thisMonth): array
    {
        $monthlyRevenue = (float) Transaction::where('created_at', '>=', $thisMonth)
            ->whereNotIn('status', ['pending'])
            ->sum('total');

        $monthlyExpenses = (float) Expense::where('expense_date', '>=', $thisMonth)->sum('amount');

        return [
            'monthly_revenue' => $monthlyRevenue,
            'monthly_expenses' => $monthlyExpenses,
            'net_profit' => $monthlyRevenue - $monthlyExpenses,
        ];
    }

    private function getSalesChart(): array
    {
        $salesChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $salesChart[] = [
                'date' => $date->format('d/m'),
                'label' => $date->locale('id')->isoFormat('ddd'),
                'revenue' => (float) Transaction::whereDate('created_at', $date)
                    ->whereNotIn('status', ['pending'])
                    ->sum('total'),
                'count' => Transaction::whereDate('created_at', $date)->count(),
            ];
        }

        return $salesChart;
    }

    private function getCategorySales(Carbon $thisMonth)
    {
        return DB::table('transaction_items')
            ->join('services', 'transaction_items.service_id', '=', 'services.id')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.created_at', '>=', $thisMonth)
            ->whereNotIn('transactions.status', ['pending'])
            ->select('services.category', \DB::raw('SUM(transaction_items.subtotal) as revenue'))
            ->groupBy('services.category')
            ->get()
            ->map(function ($item) {
                return [
                    'label' => match ($item->category) {
                        'print' => 'Print Dokumen',
                        'banner' => 'Banner / Spanduk',
                        'foto' => 'Cetak Foto',
                        'fotocopy' => 'Fotocopy',
                        'laminasi' => 'Laminasi / Jilid',
                        default => ucfirst($item->category),
                    },
                    'category' => $item->category,
                    'revenue' => (float) $item->revenue,
                ];
            });
    }

    private function getPaymentMethodStats(Carbon $thisMonth)
    {
        return Transaction::where('created_at', '>=', $thisMonth)
            ->whereNotIn('status', ['pending'])
            ->select('payment_method', DB::raw('count(*) as count'), DB::raw('SUM(total) as revenue'))
            ->groupBy('payment_method')
            ->get()
            ->map(function ($item) {
                return [
                    'label' => strtoupper($item->payment_method ?: 'LAINNYA'),
                    'count' => $item->count,
                    'revenue' => (float) $item->revenue,
                ];
            });
    }

    private function getTopServices(Carbon $thisMonth)
    {
        return DB::table('transaction_items')
            ->join('services', 'transaction_items.service_id', '=', 'services.id')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.created_at', '>=', $thisMonth)
            ->whereNotIn('transactions.status', ['pending'])
            ->select('services.name', DB::raw('SUM(transaction_items.qty) as total_qty'), DB::raw('SUM(transaction_items.subtotal) as revenue'))
            ->groupBy('services.id', 'services.name')
            ->orderBy('total_qty', 'desc')
            ->take(5)
            ->get();
    }
}
