<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    /**
     * Menampilkan halaman daftar pengeluaran.
     */
    public function index(Request $request): Response
    {
        $expenses = Expense::with('user')
            ->when($request->category, fn ($q) => $q->where('category', $request->category))
            ->when($request->date_from, fn ($q) => $q->where('expense_date', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->where('expense_date', '<=', $request->date_to))
            ->latest('expense_date')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($expense) => [
                'id'             => $expense->id,
                'category'       => $expense->category,
                'category_label' => $expense->category_label,
                'description'    => $expense->description,
                'amount'         => $expense->amount,
                'expense_date'   => $expense->expense_date->format('d/m/Y'),
                'user_name'      => $expense->user->name,
                'notes'          => $expense->notes,
            ]);

        // Total pengeluaran berdasarkan filter aktif
        $totalFiltered = Expense::when($request->category, fn ($q) => $q->where('category', $request->category))
            ->when($request->date_from, fn ($q) => $q->where('expense_date', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->where('expense_date', '<=', $request->date_to))
            ->sum('amount');

        return Inertia::render('Expenses/Index', [
            'expenses'        => $expenses,
            'total_filtered'  => $totalFiltered,
            'categories'      => Expense::CATEGORY_LABELS,
            'filters'         => $request->only(['category', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Menyimpan pengeluaran baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category'     => ['required', 'in:bahan,operasional,gaji,lainnya'],
            'description'  => ['required', 'string', 'max:255'],
            'amount'       => ['required', 'numeric', 'min:1'],
            'expense_date' => ['required', 'date'],
            'notes'        => ['nullable', 'string'],
        ], [
            'category.required'     => 'Kategori wajib dipilih.',
            'description.required'  => 'Deskripsi pengeluaran wajib diisi.',
            'amount.required'       => 'Nominal pengeluaran wajib diisi.',
            'expense_date.required' => 'Tanggal pengeluaran wajib diisi.',
        ]);

        Expense::create([
            ...$validated,
            'user_id' => Auth::id(),
        ]);

        return back()->with('success', 'Pengeluaran berhasil dicatat.');
    }

    /**
     * Menghapus catatan pengeluaran (soft delete).
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return back()->with('success', 'Pengeluaran berhasil dihapus.');
    }
}
