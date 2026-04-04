<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    /**
     * Menampilkan halaman daftar pelanggan.
     */
    public function index(Request $request): Response
    {
        $customers = Customer::withCount('transactions')
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($customer) => [
                'id'                 => $customer->id,
                'name'               => $customer->name,
                'phone'              => $customer->phone,
                'address'            => $customer->address,
                'transactions_count' => $customer->transactions_count,
                'created_at'         => $customer->created_at->format('d/m/Y'),
            ]);

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters'   => $request->only(['search']),
        ]);
    }

    /**
     * Menampilkan detail pelanggan beserta riwayat transaksinya.
     */
    public function show(Customer $customer): Response
    {
        $transactions = $customer->transactions()
            ->with('user')
            ->latest()
            ->paginate(10)
            ->through(fn ($trx) => [
                'id'                 => $trx->id,
                'transaction_number' => $trx->transaction_number,
                'total'              => $trx->total,
                'status'             => $trx->status,
                'status_label'       => $trx->status_label,
                'payment_method'     => $trx->payment_method,
                'created_at'         => $trx->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Customers/Show', [
            'customer'     => [
                'id'                  => $customer->id,
                'name'                => $customer->name,
                'phone'               => $customer->phone,
                'address'             => $customer->address,
                'notes'               => $customer->notes,
                'total_spent'         => $customer->transactions()->sum('total'),
                'transactions_count'  => $customer->transactions()->count(),
                'created_at'          => $customer->created_at->format('d/m/Y'),
            ],
            'transactions' => $transactions,
        ]);
    }

    /**
     * Menyimpan pelanggan baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'notes'   => ['nullable', 'string'],
        ], [
            'name.required' => 'Nama pelanggan wajib diisi.',
        ]);

        Customer::create($validated);

        return back()->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    /**
     * Memperbarui data pelanggan.
     */
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'notes'   => ['nullable', 'string'],
        ]);

        $customer->update($validated);

        return back()->with('success', 'Data pelanggan berhasil diperbarui.');
    }

    /**
     * Menghapus pelanggan (soft delete).
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return back()->with('success', 'Pelanggan berhasil dihapus.');
    }
}
