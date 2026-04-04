<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * Menampilkan dashboard laporan harian & bulanan.
     */
    public function index(Request $request): Response
    {
        $type = $request->query('type', 'daily'); // daily, monthly
        $date = $request->query('date', Carbon::today()->format('Y-m-d'));
        $month = $request->query('month', Carbon::now()->format('Y-m'));

        $summary = [
            'revenue' => 0,
            'expenses' => 0,
            'profit' => 0,
            'transactions_count' => 0,
        ];

        $transactions = [];
        $expenses = [];

        if ($type === 'daily') {
            $selectedDate = Carbon::parse($date);
            
            $transactionsQuery = Transaction::with('customer')
                ->whereDate('created_at', $selectedDate)
                ->whereNotIn('status', ['pending']);
                
            $expensesQuery = Expense::whereDate('expense_date', $selectedDate);

            $summary['revenue'] = $transactionsQuery->sum('total');
            $summary['transactions_count'] = $transactionsQuery->count();
            $summary['expenses'] = $expensesQuery->sum('amount');
            
            $transactions = $transactionsQuery->latest()->get();
            $expenses = $expensesQuery->latest()->get();
            
        } elseif ($type === 'monthly') {
            $selectedMonth = Carbon::parse($month . '-01');
            $year = $selectedMonth->year;
            $monthNum = $selectedMonth->month;

            $transactionsQuery = Transaction::whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNum)
                ->whereNotIn('status', ['pending']);
                
            $expensesQuery = Expense::whereYear('expense_date', $year)
                ->whereMonth('expense_date', $monthNum);

            $summary['revenue'] = $transactionsQuery->sum('total');
            $summary['transactions_count'] = $transactionsQuery->count();
            $summary['expenses'] = $expensesQuery->sum('amount');
            
            // Untuk laporan bulanan, kita group per hari
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
            'type' => $type,
            'date' => $date,
            'month' => $month,
            'summary' => $summary,
            'transactions' => $transactions,
            'expenses' => $expenses,
        ]);
    }

    /**
     * Export laporan ke PDF/Excel (opsional untuk nanti).
     */
    public function export(Request $request)
    {
        // TODO: Implement export fitur PDF / Excel kalau klien butuh
        return back()->with('info', 'Fitur export segera hadir.');
    }
}
