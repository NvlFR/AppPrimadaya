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
        } else if ($todayRevenue > 0) {
            $revenueGrowth = 100;
        }

        $recentTransactions = $this->getRecentTransactions();
        
        $activeOrders = Transaction::with(['customer'])
            ->whereIn('status', ['pending', 'diproses', 'selesai'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 1 WHEN status = 'diproses' THEN 2 WHEN status = 'selesai' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($trx) => [
                'id'                 => $trx->id,
                'transaction_number' => $trx->transaction_number,
                'customer_name'      => $trx->customer?->name ?? 'Umum',
                'status'             => $trx->status,
                'status_label'       => $trx->status_label,
                'created_at'         => $trx->created_at->format('H:i'),
            ]);

        $stats = [
            'today_revenue'      => $todayRevenue,
            'yesterday_revenue'  => $yesterdayRevenue,
            'revenue_growth'     => round($revenueGrowth, 1),
            'today_transactions' => $todayTransactions,
            'monthly_revenue'    => 0,
            'monthly_expenses'   => 0,
            'pending_orders'     => $pendingOrders,
            'net_profit'         => 0,
        ];

        $salesChart = [];
        $categorySales = collect([]);

        if ($isAdmin) {
            $adminStats = $this->getAdminStats($thisMonth);
            $stats = array_merge($stats, $adminStats);
            $salesChart = $this->getSalesChart();
            $categorySales = $this->getCategorySales($thisMonth);
        }

        return Inertia::render('Dashboard/Index', [
            'stats'                => $stats,
            'sales_chart'          => $salesChart,
            'recent_transactions'  => $recentTransactions,
            'active_orders'        => $activeOrders,
            'category_sales'       => $categorySales,
        ]);
    }

    private function getRecentTransactions()
    {
        return Transaction::with(['customer', 'user'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($trx) => [
                'id'                 => $trx->id,
                'transaction_number' => $trx->transaction_number,
                'customer_name'      => $trx->customer?->name ?? 'Umum',
                'total'              => $trx->total,
                'status'             => $trx->status,
                'status_label'       => $trx->status_label,
                'created_at'         => $trx->created_at->format('d/m/Y H:i'),
            ]);
    }

    private function getAdminStats(Carbon $thisMonth): array
    {
        $monthlyRevenue = (float) Transaction::where('created_at', '>=', $thisMonth)
            ->whereNotIn('status', ['pending'])
            ->sum('total');

        $monthlyExpenses = (float) Expense::where('expense_date', '>=', $thisMonth)->sum('amount');

        return [
            'monthly_revenue'   => $monthlyRevenue,
            'monthly_expenses'  => $monthlyExpenses,
            'net_profit'        => $monthlyRevenue - $monthlyExpenses,
        ];
    }

    private function getSalesChart(): array
    {
        $salesChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $salesChart[] = [
                'date'    => $date->format('d/m'),
                'label'   => $date->locale('id')->isoFormat('ddd'),
                'revenue' => (float) Transaction::whereDate('created_at', $date)
                    ->whereNotIn('status', ['pending'])
                    ->sum('total'),
                'count'   => Transaction::whereDate('created_at', $date)->count(),
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
}
