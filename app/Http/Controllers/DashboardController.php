<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        // Total penjualan hari ini
        $todayRevenue = Transaction::whereDate('created_at', $today)
            ->whereNotIn('status', ['pending'])
            ->sum('total');

        // Jumlah transaksi hari ini
        $todayTransactions = Transaction::whereDate('created_at', $today)->count();

        // Total transaksi bulan ini
        $monthlyRevenue = Transaction::where('created_at', '>=', $thisMonth)
            ->whereNotIn('status', ['pending'])
            ->sum('total');

        // Pesanan pending (belum selesai)
        $pendingOrders = Transaction::where('status', 'pending')->count();

        // Grafik penjualan 7 hari terakhir
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

        // Transaksi terbaru (5 terakhir)
        $recentTransactions = Transaction::with(['customer', 'user'])
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

        // Total pengeluaran bulan ini
        $monthlyExpenses = Expense::where('expense_date', '>=', $thisMonth)->sum('amount');

        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'today_revenue'      => $todayRevenue,
                'today_transactions' => $todayTransactions,
                'monthly_revenue'    => $monthlyRevenue,
                'monthly_expenses'   => $monthlyExpenses,
                'pending_orders'     => $pendingOrders,
                'net_profit'         => $monthlyRevenue - $monthlyExpenses,
            ],
            'sales_chart'          => $salesChart,
            'recent_transactions'  => $recentTransactions,
        ]);
    }
}
