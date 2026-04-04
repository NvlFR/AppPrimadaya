<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Transaction;
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
        $type  = $request->query('type', 'daily');
        $date  = $request->query('date', Carbon::today()->format('Y-m-d'));
        $month = $request->query('month', Carbon::now()->format('Y-m'));

        $summary = [
            'revenue'            => 0,
            'expenses'           => 0,
            'profit'             => 0,
            'transactions_count' => 0,
        ];

        $transactions = [];
        $expenses     = [];

        if ($type === 'daily') {
            $selectedDate = Carbon::parse($date);

            // Gunakan query builder terpisah agar count dan get tidak konflik
            $baseTransactionQuery = Transaction::whereDate('created_at', $selectedDate)
                ->whereNotIn('status', ['pending']);

            $baseExpenseQuery = Expense::whereDate('expense_date', $selectedDate);

            $summary['revenue']            = (clone $baseTransactionQuery)->sum('total');
            $summary['transactions_count'] = (clone $baseTransactionQuery)->count();
            $summary['expenses']           = (clone $baseExpenseQuery)->sum('amount');

            $transactions = (clone $baseTransactionQuery)
                ->with('customer:id,name')
                ->select('id', 'transaction_number', 'customer_id', 'total')
                ->latest()
                ->get()
                ->map(fn ($trx) => [
                    'id'                 => $trx->id,
                    'transaction_number' => $trx->transaction_number,
                    'customer'           => $trx->customer,
                    'total'              => $trx->total,
                ]);

            $expenses = (clone $baseExpenseQuery)
                ->select('id', 'description', 'category', 'amount')
                ->latest()
                ->get();

        } elseif ($type === 'monthly') {
            $selectedMonth = Carbon::parse($month . '-01');
            $year          = $selectedMonth->year;
            $monthNum      = $selectedMonth->month;

            $baseTransactionQuery = Transaction::whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNum)
                ->whereNotIn('status', ['pending']);

            $baseExpenseQuery = Expense::whereYear('expense_date', $year)
                ->whereMonth('expense_date', $monthNum);

            $summary['revenue']            = (clone $baseTransactionQuery)->sum('total');
            $summary['transactions_count'] = (clone $baseTransactionQuery)->count();
            $summary['expenses']           = (clone $baseExpenseQuery)->sum('amount');

            // Laporan bulanan: group per hari
            $transactions = Transaction::selectRaw('DATE(created_at) as date, count(*) as total_transactions, sum(total) as daily_revenue')
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

        return Inertia::render('Reports/Index', [
            'type'         => $type,
            'date'         => $date,
            'month'        => $month,
            'summary'      => $summary,
            'transactions' => $transactions,
            'expenses'     => $expenses,
        ]);
    }

    /**
     * Export laporan ke format CSV yang bisa langsung diunduh.
     * Mendukung mode harian dan bulanan sesuai parameter request.
     */
    public function export(Request $request): HttpResponse
    {
        $type  = $request->query('type', 'daily');
        $date  = $request->query('date', Carbon::today()->format('Y-m-d'));
        $month = $request->query('month', Carbon::now()->format('Y-m'));

        $rows     = [];
        $filename = '';

        if ($type === 'daily') {
            $selectedDate = Carbon::parse($date);
            $filename     = 'Laporan-Harian-' . $selectedDate->format('d-m-Y') . '.csv';

            // Header tabel pendapatan
            $rows[] = ['No. Nota', 'Pelanggan', 'Total (Rp)', 'Status', 'Waktu'];

            Transaction::whereDate('created_at', $selectedDate)
                ->whereNotIn('status', ['pending'])
                ->with('customer:id,name')
                ->orderBy('created_at')
                ->each(function ($trx) use (&$rows) {
                    $rows[] = [
                        $trx->transaction_number,
                        $trx->customer?->name ?? 'Umum',
                        number_format($trx->total, 0, ',', '.'),
                        $trx->status_label,
                        $trx->created_at->format('H:i'),
                    ];
                });

            $rows[] = []; // Baris kosong sebagai pemisah

            // Header tabel pengeluaran
            $rows[] = ['Tanggal', 'Deskripsi', 'Kategori', 'Nominal (Rp)'];
            Expense::whereDate('expense_date', $selectedDate)
                ->orderBy('expense_date')
                ->each(function ($exp) use (&$rows) {
                    $rows[] = [
                        $exp->expense_date,
                        $exp->description,
                        $exp->category_label,
                        number_format($exp->amount, 0, ',', '.'),
                    ];
                });

        } elseif ($type === 'monthly') {
            $selectedMonth = Carbon::parse($month . '-01');
            $filename      = 'Laporan-Bulanan-' . $selectedMonth->format('m-Y') . '.csv';

            $rows[] = ['Tanggal', 'Jumlah Transaksi', 'Pendapatan (Rp)'];

            Transaction::selectRaw('DATE(created_at) as date, count(*) as jml, sum(total) as total_harian')
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
                    ];
                });
        }

        // Generate konten CSV
        $csvContent = '';
        foreach ($rows as $row) {
            // Escape nilai agar tidak merusak struktur CSV
            $escaped = array_map(fn ($cell) => '"' . str_replace('"', '""', (string) $cell) . '"', $row);
            $csvContent .= implode(',', $escaped) . "\r\n";
        }

        // Tambahkan BOM UTF-8 agar Excel membaca karakter Indonesia dengan benar
        $csvContent = "\xEF\xBB\xBF" . $csvContent;

        return response($csvContent, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
