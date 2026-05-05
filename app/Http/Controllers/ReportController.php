<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * Menampilkan dashboard laporan harian & bulanan.
     */
    public function index(Request $request): Response
    {
        $type = $request->query('type', 'daily');
        $date = $request->query('date', Carbon::today()->format('Y-m-d'));
        $month = $request->query('month', Carbon::now()->format('Y-m'));

        $summary = [
            'revenue' => 0,
            'paid' => 0,
            'unpaid' => 0,
            'expenses' => 0,
            'profit' => 0,
            'transactions_count' => 0,
            'growth' => [
                'revenue' => 0,
                'transactions' => 0,
            ],
        ];

        $transactions = [];
        $expenses = [];
        $hourlyData = [];

        if ($type === 'daily') {
            $selectedDate = Carbon::parse($date);
            $prevDate = (clone $selectedDate)->subDay();

            // Base queries
            $baseTransactionQuery = Transaction::whereDate('created_at', $selectedDate)->whereNotIn('status', ['pending']);
            $prevTransactionQuery = Transaction::whereDate('created_at', $prevDate)->whereNotIn('status', ['pending']);
            $baseExpenseQuery = Expense::whereDate('expense_date', $selectedDate);

            // Summary
            $summary['revenue'] = (clone $baseTransactionQuery)->sum('total');
            $summary['paid'] = (clone $baseTransactionQuery)->sum('amount_paid');
            $summary['unpaid'] = (clone $baseTransactionQuery)->sum('remaining_amount');
            $summary['transactions_count'] = (clone $baseTransactionQuery)->count();
            $summary['expenses'] = (clone $baseExpenseQuery)->sum('amount');

            // Growth calculation
            $prevRevenue = (clone $prevTransactionQuery)->sum('total');
            $prevCount = (clone $prevTransactionQuery)->count();
            $summary['growth']['revenue'] = $prevRevenue > 0 ? (($summary['revenue'] - $prevRevenue) / $prevRevenue) * 100 : 0;
            $summary['growth']['transactions'] = $prevCount > 0 ? (($summary['transactions_count'] - $prevCount) / $prevCount) * 100 : 0;

            // Hourly Analysis
            $hourlyData = Transaction::selectRaw('HOUR(created_at) as hour, count(*) as count, sum(total) as revenue')
                ->whereDate('created_at', $selectedDate)
                ->whereNotIn('status', ['pending'])
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();

            $summary['status_counts'] = (clone $baseTransactionQuery)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status');

            $transactions = (clone $baseTransactionQuery)
                ->with('customer:id,name')
                ->select('id', 'transaction_number', 'customer_id', 'total', 'payment_status', 'created_at', 'status')
                ->latest()
                ->get()
                ->map(fn ($trx) => [
                    'id' => $trx->id,
                    'transaction_number' => $trx->transaction_number,
                    'customer' => $trx->customer,
                    'total' => $trx->total,
                    'payment_status' => $trx->payment_status,
                    'status' => $trx->status,
                    'created_at' => $trx->created_at->format('H:i'),
                ]);

            $expenses = (clone $baseExpenseQuery)
                ->select('id', 'description', 'category', 'amount')
                ->latest()
                ->get();

        } elseif ($type === 'monthly') {
            $selectedMonth = Carbon::parse($month.'-01');
            $prevMonth = (clone $selectedMonth)->subMonth();
            
            $year = $selectedMonth->year;
            $monthNum = $selectedMonth->month;

            $baseTransactionQuery = Transaction::whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNum)
                ->whereNotIn('status', ['pending']);
            
            $prevTransactionQuery = Transaction::whereYear('created_at', $prevMonth->year)
                ->whereMonth('created_at', $prevMonth->month)
                ->whereNotIn('status', ['pending']);

            $baseExpenseQuery = Expense::whereYear('expense_date', $year)
                ->whereMonth('expense_date', $monthNum);

            $summary['revenue'] = (clone $baseTransactionQuery)->sum('total');
            $summary['paid'] = (clone $baseTransactionQuery)->sum('amount_paid');
            $summary['unpaid'] = (clone $baseTransactionQuery)->sum('remaining_amount');
            $summary['transactions_count'] = (clone $baseTransactionQuery)->count();
            $summary['expenses'] = (clone $baseExpenseQuery)->sum('amount');

            // Growth calculation
            $prevRevenue = (clone $prevTransactionQuery)->sum('total');
            $prevCount = (clone $prevTransactionQuery)->count();
            $summary['growth']['revenue'] = $prevRevenue > 0 ? (($summary['revenue'] - $prevRevenue) / $prevRevenue) * 100 : 0;
            $summary['growth']['transactions'] = $prevCount > 0 ? (($summary['transactions_count'] - $prevCount) / $prevCount) * 100 : 0;

            $summary['status_counts'] = (clone $baseTransactionQuery)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status');

            $transactions = Transaction::selectRaw('
                    DATE(created_at) as date, 
                    count(*) as total_transactions, 
                    sum(total) as daily_revenue,
                    sum(amount_paid) as daily_paid,
                    sum(remaining_amount) as daily_unpaid
                ')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNum)
                ->whereNotIn('status', ['pending'])
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get();

            $expenses = Expense::selectRaw('expense_date as date, sum(amount) as daily_expense')
                ->whereYear('expense_date', $year)
                ->whereMonth('expense_date', $monthNum)
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get();
        }

        $summary['profit'] = $summary['revenue'] - $summary['expenses'];

        // Extra Analytics: Top Services
        $topServices = TransactionItem::selectRaw('service_name, sum(qty) as total_qty, sum(subtotal) as total_revenue')
            ->whereHas('transaction', function($q) use ($type, $date, $month) {
                $q->whereNotIn('status', ['pending']);
                if ($type === 'daily') {
                    $q->whereDate('created_at', $date);
                } else {
                    $selectedMonth = Carbon::parse($month.'-01');
                    $q->whereYear('created_at', $selectedMonth->year)
                      ->whereMonth('created_at', $selectedMonth->month);
                }
            })
            ->groupBy('service_name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        // Extra Analytics: Payment Methods
        $paymentMethodsQuery = Transaction::selectRaw('payment_method, sum(total) as total_revenue, count(*) as count')
            ->whereNotIn('status', ['pending']);
        
        if ($type === 'daily') {
            $paymentMethodsQuery->whereDate('created_at', $date);
        } else {
            $selectedMonth = Carbon::parse($month.'-01');
            $paymentMethodsQuery->whereYear('created_at', $selectedMonth->year)
                               ->whereMonth('created_at', $selectedMonth->month);
        }

        $paymentMethods = $paymentMethodsQuery->groupBy('payment_method')
            ->get()
            ->map(fn($item) => [
                'method' => $item->payment_method ?: 'unspecified',
                'revenue' => $item->total_revenue,
                'count' => $item->count,
            ]);

        return Inertia::render('Reports/Index', [
            'type' => $type,
            'date' => $date,
            'month' => $month,
            'summary' => $summary,
            'transactions' => $transactions,
            'expenses' => $expenses,
            'top_services' => $topServices,
            'payment_methods' => $paymentMethods,
            'hourly_data' => $hourlyData,
        ]);
    }

    /**
     * Export laporan ke format CSV yang bisa langsung diunduh.
     * Mendukung mode harian dan bulanan sesuai parameter request.
     */
    public function export(Request $request): HttpResponse
    {
        $type = $request->query('type', 'daily');
        $date = $request->query('date', Carbon::today()->format('Y-m-d'));
        $month = $request->query('month', Carbon::now()->format('Y-m'));

        $rows = [];
        $filename = '';

        // 1. Get Summary Data (Similar to index logic but simplified for CSV)
        if ($type === 'daily') {
            $selectedDate = Carbon::parse($date);
            $filename = 'Laporan-Harian-'.$selectedDate->format('d-m-Y').'.csv';
            
            $baseTrx = Transaction::whereDate('created_at', $selectedDate)->whereNotIn('status', ['pending']);
            $baseExp = Expense::whereDate('expense_date', $selectedDate);
            
            $title = "LAPORAN HARIAN PRIMADAYA PRINT - " . $selectedDate->format('d/m/Y');
        } else {
            $selectedMonth = Carbon::parse($month.'-01');
            $filename = 'Laporan-Bulanan-'.$selectedMonth->format('m-Y').'.csv';
            
            $baseTrx = Transaction::whereYear('created_at', $selectedMonth->year)
                ->whereMonth('created_at', $selectedMonth->month)
                ->whereNotIn('status', ['pending']);
            $baseExp = Expense::whereYear('expense_date', $selectedMonth->year)
                ->whereMonth('expense_date', $selectedMonth->month);
                
            $title = "LAPORAN BULANAN PRIMADAYA PRINT - " . $selectedMonth->translatedFormat('F Y');
        }

        $revenue = (clone $baseTrx)->sum('total');
        $paid = (clone $baseTrx)->sum('amount_paid');
        $unpaid = (clone $baseTrx)->sum('remaining_amount');
        $expTotal = (clone $baseExp)->sum('amount');
        $trxCount = (clone $baseTrx)->count();
        $profit = $revenue - $expTotal;

        // 2. Build CSV Rows
        $rows[] = [$title];
        $rows[] = []; // Empty line

        // Summary Section
        $rows[] = ['RINGKASAN BISNIS'];
        $rows[] = ['Total Omzet', 'Rp ' . number_format($revenue, 0, ',', '.')];
        $rows[] = ['Total Lunas (Uang Masuk)', 'Rp ' . number_format($paid, 0, ',', '.')];
        $rows[] = ['Total Piutang', 'Rp ' . number_format($unpaid, 0, ',', '.')];
        $rows[] = ['Total Pengeluaran', 'Rp ' . number_format($expTotal, 0, ',', '.')];
        $rows[] = ['Estimasi Laba Bersih', 'Rp ' . number_format($profit, 0, ',', '.')];
        $rows[] = ['Jumlah Transaksi', $trxCount];
        $rows[] = [];

        // Operational Status Section
        $statusCounts = (clone $baseTrx)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $rows[] = ['STATUS OPERASIONAL'];
        $rows[] = ['Diproses', $statusCounts['diproses'] ?? 0];
        $rows[] = ['Siap Ambil (Selesai)', $statusCounts['selesai'] ?? 0];
        $rows[] = ['Sudah Diambil', $statusCounts['diambil'] ?? 0];
        $rows[] = [];

        // Payment Methods Section
        $rows[] = ['METODE PEMBAYARAN'];
        $rows[] = ['Metode', 'Total Nominal (Rp)', 'Jumlah'];
        (clone $baseTrx)->selectRaw('payment_method, sum(total) as total_revenue, count(*) as count')
            ->groupBy('payment_method')
            ->get()
            ->each(function($item) use (&$rows) {
                $rows[] = [
                    strtoupper($item->payment_method ?: 'unspecified'),
                    number_format($item->total_revenue, 0, ',', '.'),
                    $item->count
                ];
            });
        $rows[] = [];

        // Top Services Section
        $rows[] = ['LAYANAN TERLARIS (TOP 5)'];
        $rows[] = ['Layanan', 'Total Qty', 'Total Revenue (Rp)'];
        TransactionItem::selectRaw('service_name, sum(qty) as total_qty, sum(subtotal) as total_revenue')
            ->whereHas('transaction', function($q) use ($type, $date, $month) {
                $q->whereNotIn('status', ['pending']);
                if ($type === 'daily') {
                    $q->whereDate('created_at', $date);
                } else {
                    $selectedMonth = Carbon::parse($month.'-01');
                    $q->whereYear('created_at', $selectedMonth->year)
                      ->whereMonth('created_at', $selectedMonth->month);
                }
            })
            ->groupBy('service_name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get()
            ->each(function($svc) use (&$rows) {
                $rows[] = [
                    $svc->service_name,
                    $svc->total_qty,
                    number_format($svc->total_revenue, 0, ',', '.')
                ];
            });
        $rows[] = [];

        // Detailed Data Section
        if ($type === 'daily') {
            $rows[] = ['RINCIAN TRANSAKSI'];
            $rows[] = ['No. Nota', 'Pelanggan', 'Total (Rp)', 'Status Pesanan', 'Status Bayar', 'Waktu'];

            (clone $baseTrx)->with('customer:id,name')
                ->orderBy('created_at')
                ->each(function ($trx) use (&$rows) {
                    $rows[] = [
                        $trx->transaction_number,
                        $trx->customer?->name ?? 'Umum',
                        number_format($trx->total, 0, ',', '.'),
                        $trx->status_label,
                        ucfirst(str_replace('_', ' ', $trx->payment_status)),
                        $trx->created_at->format('H:i'),
                    ];
                });

            $rows[] = [];
            $rows[] = ['RINCIAN PENGELUARAN'];
            $rows[] = ['Tanggal', 'Deskripsi', 'Kategori', 'Nominal (Rp)'];
            (clone $baseExp)->orderBy('expense_date')
                ->each(function ($exp) use (&$rows) {
                    $rows[] = [
                        $exp->expense_date,
                        $exp->description,
                        $exp->category_label,
                        number_format($exp->amount, 0, ',', '.'),
                    ];
                });
        } else {
            $rows[] = ['REKAPITULASI HARIAN'];
            $rows[] = ['Tanggal', 'Jml Transaksi', 'Total Omzet (Rp)', 'Terbayar (Rp)', 'Piutang (Rp)'];

            Transaction::selectRaw('
                    DATE(created_at) as date, 
                    count(*) as jml, 
                    sum(total) as total_harian,
                    sum(amount_paid) as paid_harian,
                    sum(remaining_amount) as unpaid_harian
                ')
                ->whereYear('created_at', $selectedMonth->year)
                ->whereMonth('created_at', $selectedMonth->month)
                ->whereNotIn('status', ['pending'])
                ->groupBy('date')
                ->orderBy('date')
                ->each(function ($row) use (&$rows) {
                    $rows[] = [
                        $row->date,
                        $row->jml,
                        number_format($row->total_harian, 0, ',', '.'),
                        number_format($row->paid_harian, 0, ',', '.'),
                        number_format($row->unpaid_harian, 0, ',', '.'),
                    ];
                });
        }

        // Generate CSV content
        $csvContent = "\xEF\xBB\xBF"; // UTF-8 BOM
        foreach ($rows as $row) {
            $escaped = array_map(fn ($cell) => '"'.str_replace('"', '""', (string) $cell).'"', $row);
            $csvContent .= implode(',', $escaped)."\r\n";
        }

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
