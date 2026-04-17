<?php

namespace App\Http\Controllers;

use App\Models\PaperSize;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    /**
     * Menampilkan daftar semua transaksi dengan filter.
     */
    public function index(Request $request): Response
    {
        // Hanya muat kolom yang dibutuhkan untuk menghindari over-fetching
        $transactions = Transaction::select(
            'transactions.id',
            'transactions.transaction_number',
            'transactions.customer_id',
            'transactions.user_id',
            'transactions.total',
            'transactions.payment_method',
            'transactions.status',
            'transactions.created_at'
        )
            ->with([
                'customer:id,name',
                'user:id,name',
            ])
            ->when($request->search, fn ($q) => $q->where('transaction_number', 'like', "%{$request->search}%"))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->date_from, fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($trx) => [
                'id' => $trx->id,
                'transaction_number' => $trx->transaction_number,
                'customer_name' => $trx->customer?->name ?? 'Umum',
                'kasir_name' => $trx->user->name,
                'total' => $trx->total,
                'payment_method' => $trx->payment_method,
                'status' => $trx->status,
                'status_label' => $trx->status_label,
                'created_at' => $trx->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Menampilkan form kasir untuk membuat transaksi baru.
     */
    public function create(): Response
    {
        $services = Service::where('is_active', true)
            ->with('prices.paperSize')
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'category' => $s->category,
                'base_price' => $s->base_price,
                'unit' => $s->unit,
                'has_matrix_pricing' => $s->has_matrix_pricing,
                'is_per_meter' => (bool) $s->is_per_meter,
                'is_pinned' => (bool) $s->is_pinned,
                'prices' => $s->prices->map(fn ($p) => [
                    'paper_size_id' => $p->paper_size_id,
                    'paper_size_name' => $p->paperSize?->name,
                    'print_type' => $p->print_type,
                    'price' => $p->price,
                ]),
            ]);

        $paperSizes = PaperSize::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Transactions/Create', [
            'services' => $services,
            // Daftar pelanggan tidak dimuat saat halaman dibuka (Issue #25)
            // Data pelanggan di-load secara asinkron via endpoint /customers/search
            'paper_sizes' => $paperSizes,
        ]);
    }

    /**
     * Menyimpan transaksi baru beserta semua item-nya.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['required', 'exists:services,id'],
            'items.*.paper_size_id' => ['nullable', 'exists:paper_sizes,id'],
            'items.*.print_type' => ['required', 'in:color,bw,na'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.width' => ['nullable', 'numeric', 'min:0.1'],
            'items.*.height' => ['nullable', 'numeric', 'min:0.1'],
            'items.*.item_notes' => ['nullable', 'string'],
            'discount_type' => ['nullable', 'in:percent,flat'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,transfer,qris'],
            'amount_paid' => ['required_if:payment_method,cash', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $transaction = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $transaction = DB::transaction(function () use ($request) {
                    $serviceIds = collect($request->items)->pluck('service_id')->filter()->unique();
                    $services = Service::whereIn('id', $serviceIds)->get()->keyBy('id');

                    $resolvedItems = collect($request->items)->map(function ($itemData) use ($services) {
                        $service = $services->get($itemData['service_id']);
                        $qty = max(1, (int) $itemData['qty']);
                        $unitPrice = (float) $itemData['unit_price'];

                        if ($service?->is_per_meter) {
                            $width = max(0.1, (float) ($itemData['width'] ?? 0.1));
                            $height = max(0.1, (float) ($itemData['height'] ?? 0.1));
                            $unitPrice = (float) $service->base_price * $width * $height;
                        }

                        $itemData['qty'] = $qty;
                        $itemData['unit_price'] = $unitPrice;

                        return $itemData;
                    });

                    // Hitung subtotal dari semua item berdasarkan harga final yang sudah dinormalisasi
                    $subtotal = $resolvedItems->sum(fn ($item) => $item['unit_price'] * $item['qty']);

                    // Hitung diskon — mendukung persen dan flat nominal
                    $discountType = $request->discount_type ?? 'percent';
                    $discountValue = $request->discount_value ?? 0;

                    if ($discountType === 'percent') {
                        $discountPercent = min($discountValue, 100);
                        $discountAmount = $subtotal * ($discountPercent / 100);
                    } else {
                        // Flat nominal — pastikan tidak melebihi subtotal
                        $discountAmount = min($discountValue, $subtotal);
                        $discountPercent = $subtotal > 0 ? round(($discountAmount / $subtotal) * 100, 2) : 0;
                    }

                    $total = $subtotal - $discountAmount;
                    if ($request->payment_method === 'cash' && $request->amount_paid < $total) {
                        throw ValidationException::withMessages([
                            'amount_paid' => 'Nominal pembayaran tunai harus sama atau lebih besar dari total tagihan.',
                        ]);
                    }
                    $changeAmount = max(0, $request->amount_paid - $total);

                    // Generate nomor transaksi unik
                    $transactionNumber = $this->generateTransactionNumber();

                    // Buat header transaksi
                    $transaction = Transaction::create([
                        'transaction_number' => $transactionNumber,
                        'customer_id' => $request->customer_id,
                        'user_id' => Auth::id(),
                        'subtotal' => $subtotal,
                        'discount_percent' => $discountPercent,
                        'discount_amount' => $discountAmount,
                        'total' => $total,
                        'payment_method' => $request->payment_method,
                        'amount_paid' => $request->amount_paid,
                        'change_amount' => $changeAmount,
                        'status' => 'pending',
                        'notes' => $request->notes,
                    ]);

                    $paperSizeIds = collect($request->items)->pluck('paper_size_id')->filter()->unique();
                    $paperSizes = PaperSize::whereIn('id', $paperSizeIds)->get()->keyBy('id');

                    // Buat semua item transaksi
                    foreach ($resolvedItems as $itemData) {
                        $service = $services->get($itemData['service_id']);
                        $paperSize = isset($itemData['paper_size_id']) ? $paperSizes->get($itemData['paper_size_id']) : null;

                        // Handle upload file custom order
                        $filePath = null;
                        $originalFilename = null;
                        if (isset($itemData['file']) && $itemData['file']) {
                            $file = $itemData['file'];
                            $filePath = Storage::disk('public')->put("orders/{$transaction->id}", $file);
                            $originalFilename = $file->getClientOriginalName();
                        }

                        TransactionItem::create([
                            'transaction_id' => $transaction->id,
                            'service_id' => $service?->id,
                            'service_name' => $service?->name,
                            'paper_size_id' => $paperSize?->id,
                            'paper_size_name' => $paperSize?->name,
                            'print_type' => $itemData['print_type'],
                            'qty' => $itemData['qty'],
                            'unit_price' => $itemData['unit_price'],
                            'subtotal' => $itemData['unit_price'] * $itemData['qty'],
                            'file_path' => $filePath,
                            'original_filename' => $originalFilename,
                            'item_notes' => $itemData['item_notes'] ?? null,
                        ]);
                    }

                    return $transaction;
                });

                break;
            } catch (QueryException $exception) {
                if ($attempt === 3 || ! $this->isDuplicateKeyException($exception)) {
                    throw $exception;
                }
            }
        }

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Transaksi berhasil disimpan.');
    }

    /**
     * Menampilkan detail transaksi beserta semua item.
     */
    public function show(Transaction $transaction): Response
    {
        $transaction->load(['customer', 'user', 'items.service', 'items.paperSize']);

        return Inertia::render('Transactions/Show', [
            'transaction' => [
                'id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'customer' => $transaction->customer ? [
                    'id' => $transaction->customer->id,
                    'name' => $transaction->customer->name,
                    'phone' => $transaction->customer->phone,
                ] : null,
                'kasir_name' => $transaction->user->name,
                'subtotal' => $transaction->subtotal,
                'discount_percent' => $transaction->discount_percent,
                'discount_amount' => $transaction->discount_amount,
                'total' => $transaction->total,
                'payment_method' => $transaction->payment_method,
                'amount_paid' => $transaction->amount_paid,
                'change_amount' => $transaction->change_amount,
                'status' => $transaction->status,
                'status_label' => $transaction->status_label,
                'notes' => $transaction->notes,
                'created_at' => $transaction->created_at->format('d/m/Y H:i'),
                'items' => $transaction->items->map(fn ($item) => [
                    'id' => $item->id,
                    'service_name' => $item->service_name,
                    'paper_size_name' => $item->paper_size_name,
                    'print_type' => $item->print_type,
                    'print_type_label' => $item->print_type_label,
                    'qty' => $item->qty,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'item_notes' => $item->item_notes,
                    'original_filename' => $item->original_filename,
                ]),
            ],
            'status_options' => Transaction::STATUS_LABELS,
        ]);
    }

    /**
     * Memperbarui status pesanan.
     */
    public function updateStatus(Request $request, Transaction $transaction): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:pending,diproses,selesai,diambil'],
        ]);

        $transaction->update(['status' => $request->status]);

        return back()->with('success', 'Status pesanan berhasil diperbarui.');
    }

    /**
     * Menampilkan halaman tracking status semua pesanan.
     */
    public function orders(Request $request): Response
    {
        $perPage = (int) $request->integer('per_page', 12);
        $perPage = in_array($perPage, [12, 24, 48], true) ? $perPage : 12;

        // Hanya muat kolom yang dibutuhkan untuk daftar pesanan
        $orders = Transaction::select(
            'transactions.id',
            'transactions.transaction_number',
            'transactions.customer_id',
            'transactions.total',
            'transactions.status',
            'transactions.created_at'
        )
            ->with(['customer:id,name'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->where('transaction_number', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($trx) => [
                'id' => $trx->id,
                'transaction_number' => $trx->transaction_number,
                'customer_name' => $trx->customer?->name ?? 'Umum',
                'total' => $trx->total,
                'status' => $trx->status,
                'status_label' => $trx->status_label,
                'created_at' => $trx->created_at->format('d/m/Y H:i'),
            ]);

        $statusCounts = Transaction::groupBy('status')
            ->select('status', DB::raw('count(*) as count'))
            ->pluck('count', 'status');

        return Inertia::render('Orders/Index', [
            'orders' => array_merge($orders->toArray(), ['status_counts' => $statusCounts]),
            'filters' => $request->only(['search', 'status', 'per_page']),
            'status_options' => Transaction::STATUS_LABELS,
        ]);
    }

    /**
     * Memperbarui status banyak pesanan sekaligus.
     */
    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_ids' => ['required', 'array', 'min:1'],
            'transaction_ids.*' => ['integer', 'exists:transactions,id'],
            'status' => ['required', 'in:pending,diproses,selesai,diambil'],
        ]);

        Transaction::whereIn('id', $validated['transaction_ids'])
            ->update(['status' => $validated['status']]);

        return back()->with('success', 'Status pesanan berhasil diperbarui secara massal.');
    }

    /**
     * Menghapus banyak transaksi sekaligus untuk admin.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_ids' => ['required', 'array', 'min:1'],
            'transaction_ids.*' => ['integer', 'exists:transactions,id'],
        ]);

        $transactions = Transaction::whereIn('id', $validated['transaction_ids'])->get();

        foreach ($transactions as $transaction) {
            $transaction->forceDelete();
        }

        return back()->with('success', count($transactions).' transaksi berhasil dihapus.');
    }

    /**
     * Mengunduh invoice transaksi dalam format PDF.
     */
    public function downloadPdf(Transaction $transaction)
    {
        $transaction->load(['customer', 'user', 'items']);

        $pdf = Pdf::loadView('invoices.transaction', [
            'transaction' => $transaction,
        ]);

        $filename = "Invoice-{$transaction->transaction_number}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Menampilkan struk thermal 80mm untuk dicetak langsung dari browser.
     * Endpoint ini mengembalikan HTML yang dioptimalkan untuk printer thermal.
     */
    public function printThermal(Transaction $transaction)
    {
        $transaction->load(['customer', 'user', 'items']);

        return view('invoices.thermal', [
            'transaction' => $transaction,
        ]);
    }

    /**
     * Generate nomor transaksi unik dengan format TRX-YYYYMMDD-XXXX.
     */
    private function generateTransactionNumber(): string
    {
        $today = Carbon::today();
        $sequenceDate = $today->toDateString();
        $prefix = 'TRX-'.$today->format('Ymd').'-';

        $sequence = DB::table('transaction_sequences')
            ->where('sequence_date', $sequenceDate)
            ->lockForUpdate()
            ->first();

        if (! $sequence) {
            DB::table('transaction_sequences')->insert([
                'sequence_date' => $sequenceDate,
                'last_number' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $prefix.'0001';
        }

        $nextNumber = $sequence->last_number + 1;

        DB::table('transaction_sequences')
            ->where('id', $sequence->id)
            ->update([
                'last_number' => $nextNumber,
                'updated_at' => now(),
            ]);

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Mengecek error unique constraint untuk retry saat submit transaksi simultan.
     */
    private function isDuplicateKeyException(QueryException $exception): bool
    {
        return $exception->getCode() === '23000'
            || in_array($exception->errorInfo[1] ?? null, [1062, 19], true);
    }
}
