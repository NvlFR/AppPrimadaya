<?php

namespace App\Http\Controllers;

use App\Models\PaperSize;
use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    /**
     * Menampilkan halaman daftar layanan percetakan.
     */
    public function index(Request $request): Response
    {
        $services = Service::with('prices.paperSize')
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->category, fn ($q) => $q->where('category', $request->category))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($service) => [
                'id' => $service->id,
                'name' => $service->name,
                'category' => $service->category,
                'base_price' => $service->base_price,
                'unit' => $service->unit,
                'has_matrix_pricing' => $service->has_matrix_pricing,
                'is_per_meter' => $service->is_per_meter,
                'is_active' => $service->is_active,
                'description' => $service->description,
                'prices_count' => $service->prices->count(),
            ]);

        $paperSizes = PaperSize::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Services/Index', [
            'services' => $services,
            'paper_sizes' => $paperSizes,
            'filters' => $request->only(['search', 'category']),
        ]);
    }

    /**
     * Menyimpan layanan baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:print,banner,foto,fotocopy,laminasi,lainnya'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            'has_matrix_pricing' => ['boolean'],
            'is_per_meter' => ['boolean'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string'],
        ], [
            'name.required' => 'Nama layanan wajib diisi.',
            'category.required' => 'Kategori layanan wajib dipilih.',
            'base_price.required' => 'Harga dasar wajib diisi.',
        ]);

        Service::create($validated);

        return back()->with('success', 'Layanan berhasil ditambahkan.');
    }

    /**
     * Memperbarui data layanan yang sudah ada.
     */
    public function update(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:print,banner,foto,fotocopy,laminasi,lainnya'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            'has_matrix_pricing' => ['boolean'],
            'is_per_meter' => ['boolean'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $service->update($validated);

        return back()->with('success', 'Layanan berhasil diperbarui.');
    }

    /**
     * Menghapus layanan dari database (soft delete).
     */
    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return back()->with('success', 'Layanan berhasil dihapus.');
    }

    /**
     * Mendapatkan daftar harga (pricing matrix) untuk layanan tertentu.
     */
    public function getPrices(Service $service): JsonResponse
    {
        $prices = $service->prices()->with('paperSize')->get();

        return response()->json($prices);
    }

    /**
     * Menyimpan atau memperbarui pricing matrix untuk layanan.
     */
    public function storePrices(Request $request, Service $service): RedirectResponse
    {
        $request->validate([
            'prices' => ['required', 'array'],
            'prices.*.paper_size_id' => ['nullable', 'exists:paper_sizes,id'],
            'prices.*.print_type' => ['required', 'in:color,bw,na'],
            'prices.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        // Hapus semua pricing lama lalu buat ulang
        $service->prices()->delete();

        foreach ($request->prices as $priceData) {
            ServicePrice::create([
                'service_id' => $service->id,
                'paper_size_id' => $priceData['paper_size_id'] ?? null,
                'print_type' => $priceData['print_type'],
                'price' => $priceData['price'],
            ]);
        }

        return back()->with('success', 'Pricing matrix berhasil disimpan.');
    }
}
