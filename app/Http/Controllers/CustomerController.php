<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerServicePrice;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
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
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'transactions_count' => $customer->transactions_count,
                'created_at' => $customer->created_at->format('d/m/Y'),
            ]);

        // Kirim daftar layanan aktif ke halaman pelanggan untuk keperluan form harga khusus
        $services = Service::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'base_price', 'unit']);

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search']),
            'services' => $services,
        ]);
    }

    /**
     * Menampilkan detail pelanggan beserta riwayat transaksinya.
     */
    public function show(Customer $customer): Response
    {
        // Muat aggregate data (count & sum) dalam satu langkah
        $customer->loadCount('transactions')->loadSum('transactions', 'total');

        $transactions = $customer->transactions()
            ->with('user:id,name')
            ->latest()
            ->paginate(10)
            ->through(fn ($trx) => [
                'id' => $trx->id,
                'transaction_number' => $trx->transaction_number,
                'total' => $trx->total,
                'status' => $trx->status,
                'status_label' => $trx->status_label,
                'payment_method' => $trx->payment_method,
                'created_at' => $trx->created_at->format('d/m/Y H:i'),
            ]);

        // Muat harga khusus yang sudah ditetapkan untuk pelanggan ini
        $customPrices = $customer->customServicePrices()
            ->with('service:id,name,base_price,unit')
            ->get()
            ->map(fn ($cp) => [
                'id' => $cp->id,
                'service_id' => $cp->service_id,
                'service_name' => $cp->service->name,
                'custom_price' => (float) $cp->custom_price,
                'notes' => $cp->notes,
            ]);

        return Inertia::render('Customers/Show', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'notes' => $customer->notes,
                'total_spent' => $customer->transactions_sum_total ?? 0,
                'transactions_count' => $customer->transactions_count,
                'created_at' => $customer->created_at->format('d/m/Y'),
            ],
            'transactions' => $transactions,
            'customPrices' => $customPrices,
        ]);
    }

    /**
     * Mencari pelanggan secara asinkron berdasarkan query pencarian.
     * Digunakan oleh combobox async di halaman kasir (Issue #25).
     * Mengembalikan maksimal 20 data untuk menjaga performa.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        $customers = Customer::query()
            ->when($query, fn ($q) => $q->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            }))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'phone']);

        return response()->json($customers);
    }

    /**
     * Mengambil daftar harga khusus untuk pelanggan tertentu.
     * Digunakan oleh kasir untuk auto-apply harga saat customer dipilih.
     */
    public function getCustomPrices(Customer $customer): JsonResponse
    {
        $customPrices = $customer->customServicePrices()
            ->get(['service_id', 'custom_price'])
            ->keyBy('service_id')
            ->map(fn ($cp) => (float) $cp->custom_price);

        return response()->json($customPrices);
    }

    /**
     * Menyimpan pelanggan baru ke database beserta harga khusus (opsional).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'custom_prices' => ['nullable', 'array'],
            'custom_prices.*.service_id' => ['required', 'exists:services,id'],
            'custom_prices.*.custom_price' => ['required', 'numeric', 'min:0'],
        ], [
            'name.required' => 'Nama pelanggan wajib diisi.',
            'custom_prices.*.custom_price.min' => 'Harga tidak boleh negatif.',
        ]);

        $customer = Customer::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Simpan harga khusus jika ada
        if (!empty($validated['custom_prices'])) {
            foreach ($validated['custom_prices'] as $priceData) {
                CustomerServicePrice::create([
                    'customer_id' => $customer->id,
                    'service_id' => $priceData['service_id'],
                    'custom_price' => $priceData['custom_price'],
                ]);
            }
        }

        // Share data customers terbaru agar bisa di-reload via Inertia partial reload
        // (digunakan oleh fitur tambah pelanggan inline di halaman kasir)
        return back()->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    /**
     * Memperbarui data pelanggan.
     */
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'custom_prices' => ['nullable', 'array'],
            'custom_prices.*.service_id' => ['required', 'exists:services,id'],
            'custom_prices.*.custom_price' => ['required', 'numeric', 'min:0'],
        ]);

        $customer->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Sync harga khusus — hapus semua lalu insert ulang
        if (isset($validated['custom_prices'])) {
            $customer->customServicePrices()->delete();
            foreach ($validated['custom_prices'] as $priceData) {
                CustomerServicePrice::create([
                    'customer_id' => $customer->id,
                    'service_id' => $priceData['service_id'],
                    'custom_price' => $priceData['custom_price'],
                ]);
            }
        }

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
