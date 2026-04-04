<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    /**
     * Menampilkan halaman daftar stok bahan.
     */
    public function index(Request $request): Response
    {
        $stocks = Stock::when($request->category, fn ($q) => $q->where('category', $request->category))
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($stock) => [
                'id'          => $stock->id,
                'name'        => $stock->name,
                'category'    => $stock->category,
                'unit'        => $stock->unit,
                'current_qty' => $stock->current_qty,
                'min_qty'     => $stock->min_qty,
                'is_low_stock' => $stock->is_low_stock,
                'notes'       => $stock->notes,
            ]);

        $lowStockCount = Stock::whereRaw('current_qty <= min_qty')->count();

        return Inertia::render('Stocks/Index', [
            'stocks'         => $stocks,
            'low_stock_count' => $lowStockCount,
            'filters'        => $request->only(['search', 'category']),
        ]);
    }

    /**
     * Menyimpan item stok baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'category'    => ['required', 'in:kertas,tinta,bahan,lainnya'],
            'unit'        => ['required', 'string', 'max:50'],
            'current_qty' => ['required', 'numeric', 'min:0'],
            'min_qty'     => ['required', 'numeric', 'min:0'],
            'notes'       => ['nullable', 'string'],
        ]);

        $stock = Stock::create($validated);

        // Catat log stok awal jika ada qty
        if ($stock->current_qty > 0) {
            StockLog::create([
                'stock_id'   => $stock->id,
                'user_id'    => Auth::id(),
                'type'       => 'masuk',
                'qty'        => $stock->current_qty,
                'qty_before' => 0,
                'qty_after'  => $stock->current_qty,
                'notes'      => 'Stok awal saat item ditambahkan.',
            ]);
        }

        return back()->with('success', 'Item stok berhasil ditambahkan.');
    }

    /**
     * Memperbarui jumlah stok bahan (tambah atau kurangi).
     */
    public function update(Request $request, Stock $stock): RedirectResponse
    {
        $request->validate([
            'type'  => ['required', 'in:masuk,keluar'],
            'qty'   => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ], [
            'type.required' => 'Jenis perubahan stok wajib dipilih.',
            'qty.required'  => 'Jumlah stok wajib diisi.',
            'qty.min'       => 'Jumlah stok harus lebih dari 0.',
        ]);

        // Validasi stok keluar dilakukan SEBELUM transaction agar redirect bisa berfungsi
        if ($request->type === 'keluar' && $request->qty > $stock->current_qty) {
            return back()->withErrors(['qty' => 'Jumlah keluar melebihi stok yang tersedia (' . $stock->current_qty . ' ' . $stock->unit . ').' ])->withInput();
        }

        DB::transaction(function () use ($request, $stock) {
            $qtyBefore = $stock->current_qty;

            // Hitung qty baru berdasarkan tipe perubahan
            $qtyAfter = $request->type === 'masuk'
                ? $qtyBefore + $request->qty
                : $qtyBefore - $request->qty;

            // Update stok
            $stock->update(['current_qty' => $qtyAfter]);

            // Catat log perubahan
            StockLog::create([
                'stock_id'   => $stock->id,
                'user_id'    => Auth::id(),
                'type'       => $request->type,
                'qty'        => $request->qty,
                'qty_before' => $qtyBefore,
                'qty_after'  => $qtyAfter,
                'notes'      => $request->notes,
            ]);
        });

        return back()->with('success', 'Stok berhasil diperbarui.');
    }

    /**
     * Menampilkan riwayat log perubahan stok.
     */
    public function logs(Request $request): Response
    {
        $logs = StockLog::with(['stock', 'user'])
            ->when($request->stock_id, fn ($q) => $q->where('stock_id', $request->stock_id))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($log) => [
                'id'          => $log->id,
                'stock_name'  => $log->stock->name,
                'user_name'   => $log->user->name,
                'type'        => $log->type,
                'qty'         => $log->qty,
                'qty_before'  => $log->qty_before,
                'qty_after'   => $log->qty_after,
                'notes'       => $log->notes,
                'created_at'  => $log->created_at->format('d/m/Y H:i'),
            ]);

        $stocks = Stock::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Stocks/Logs', [
            'logs'    => $logs,
            'stocks'  => $stocks,
            'filters' => $request->only(['stock_id']),
        ]);
    }
}
