<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Invoice {{ $transaction->transaction_number }}</title>
    <style>
        /* Reset & Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #1a1a2e;
            background: #ffffff;
            line-height: 1.5;
        }

        @if(!$is_pdf)
        body {
            background: #f1f5f9;
            padding: 40px 0;
            font-family: 'Inter', -apple-system, sans-serif;
        }
        .page {
            background: #ffffff;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .download-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #1d4ed8;
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 10px 15px -3px rgba(29, 78, 216, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            z-index: 1000;
        }
        .download-btn:hover {
            background: #1e40af;
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(29, 78, 216, 0.4);
        }
        @endif

        /* Layout Utama */
        .page {
            padding: 32px 36px;
            max-width: 780px;
            margin: 0 auto;
        }

        /* Header Invoice */
        .invoice-header {
            display: table;
            width: 100%;
            border-bottom: 3px solid #1d4ed8;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .invoice-header-left {
            display: table-cell;
            vertical-align: middle;
            width: 60%;
        }

        .invoice-header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 40%;
        }

        /* Nama Bisnis */
        .business-name {
            font-size: 22px;
            font-weight: bold;
            color: #1d4ed8;
            letter-spacing: 0.5px;
        }

        .business-tagline {
            font-size: 10px;
            color: #6b7280;
            margin-top: 2px;
        }

        /* Nomor Invoice */
        .invoice-number-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9ca3af;
            font-weight: bold;
        }

        .invoice-number {
            font-size: 18px;
            font-weight: bold;
            color: #1a1a2e;
            font-family: 'DejaVu Sans Mono', monospace;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        .status-diproses {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        .status-selesai {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .status-diambil {
            background: #e5e7eb;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        /* Info Grid (Customer & Kasir & Tanggal) */
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 14px 16px;
        }

        .info-col {
            display: table-cell;
            vertical-align: top;
            width: 33.33%;
            padding-right: 12px;
        }

        .info-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #9ca3af;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 11px;
            color: #1a1a2e;
            font-weight: 600;
        }

        .info-value-sub {
            font-size: 10px;
            color: #6b7280;
            margin-top: 1px;
        }

        /* Tabel Item */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .items-table thead tr {
            background: #1d4ed8;
            color: #ffffff;
        }

        .items-table thead th {
            padding: 9px 12px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: bold;
        }

        .items-table thead th.text-right {
            text-align: right;
        }

        .items-table thead th.text-center {
            text-align: center;
        }

        .items-table tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .items-table tbody td {
            padding: 9px 12px;
            vertical-align: top;
        }

        .items-table tbody td.text-right {
            text-align: right;
        }

        .items-table tbody td.text-center {
            text-align: center;
        }

        /* Nama layanan & detail */
        .service-name {
            font-weight: 700;
            color: #111827;
        }

        .service-detail {
            font-size: 9px;
            color: #6b7280;
            margin-top: 2px;
        }

        .service-note {
            font-size: 9px;
            color: #b45309;
            font-style: italic;
            margin-top: 2px;
        }

        .service-file {
            font-size: 9px;
            color: #1d4ed8;
            margin-top: 2px;
        }

        /* Ringkasan Pembayaran */
        .summary-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .summary-left {
            display: table-cell;
            width: 55%;
            vertical-align: top;
            padding-right: 16px;
        }

        .summary-right {
            display: table-cell;
            width: 45%;
            vertical-align: top;
        }

        /* Catatan */
        .notes-box {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-left: 4px solid #f59e0b;
            border-radius: 4px;
            padding: 10px 12px;
        }

        .notes-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #92400e;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .notes-text {
            font-size: 10px;
            color: #78350f;
        }

        /* Tabel ringkasan harga */
        .price-summary {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
        }

        .price-row {
            display: table;
            width: 100%;
            border-bottom: 1px solid #f3f4f6;
            padding: 8px 12px;
        }

        .price-row:last-child {
            border-bottom: none;
        }

        .price-row-label {
            display: table-cell;
            color: #6b7280;
            font-size: 10px;
        }

        .price-row-value {
            display: table-cell;
            text-align: right;
            font-size: 10px;
            color: #111827;
        }

        .price-row-total {
            background: #1d4ed8;
        }

        .price-row-total .price-row-label {
            color: #bfdbfe;
            font-weight: bold;
            font-size: 11px;
        }

        .price-row-total .price-row-value {
            color: #ffffff;
            font-weight: bold;
            font-size: 14px;
        }

        .price-row-discount .price-row-value {
            color: #dc2626;
        }

        /* Blok Pembayaran */
        .payment-info {
            margin-top: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
        }

        .payment-status-header {
            padding: 8px 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: table;
            width: 100%;
        }

        .ps-belum-bayar {
            background: #fef2f2;
            border-bottom: 2px solid #fca5a5;
            color: #991b1b;
        }

        .ps-dp {
            background: #fffbeb;
            border-bottom: 2px solid #fcd34d;
            color: #92400e;
        }

        .ps-lunas {
            background: #f0fdf4;
            border-bottom: 2px solid #86efac;
            color: #065f46;
        }

        .ps-icon {
            display: table-cell;
        }

        .ps-label {
            display: table-cell;
            text-align: right;
            font-size: 11px;
        }

        .payment-row {
            display: table;
            width: 100%;
            padding: 7px 12px;
            border-bottom: 1px solid #f3f4f6;
        }

        .payment-row:last-child {
            border-bottom: none;
        }

        .payment-label {
            display: table-cell;
            font-size: 10px;
            color: #374151;
        }

        .payment-value {
            display: table-cell;
            text-align: right;
            font-size: 10px;
            font-weight: 600;
            color: #111827;
        }

        .kembalian-value {
            color: #059669;
            font-weight: bold;
            font-size: 12px;
        }

        .sisa-row {
            background: #fff7ed;
        }

        .sisa-row .payment-label {
            color: #c2410c;
            font-weight: bold;
            font-size: 10px;
        }

        .sisa-row .payment-value {
            color: #c2410c;
            font-weight: bold;
            font-size: 13px;
        }

        .unpaid-notice {
            padding: 10px 12px;
            font-size: 10px;
            color: #991b1b;
            font-style: italic;
            text-align: center;
        }

        /* Footer */
        .invoice-footer {
            border-top: 1px dashed #d1d5db;
            margin-top: 20px;
            padding-top: 14px;
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            vertical-align: middle;
            font-size: 9px;
            color: #9ca3af;
        }

        .footer-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            font-size: 9px;
            color: #9ca3af;
        }

        .footer-right strong {
            display: block;
            font-size: 10px;
            color: #6b7280;
        }

        .text-danger {
            color: #dc2626;
        }

        .font-bold {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="page">

        {{-- ===================== HEADER ===================== --}}
        <div class="invoice-header">
            <div class="invoice-header-left">
                @php
                    $logoPath = public_path('logo.png');
                    $logoData = '';
                    if (file_exists($logoPath)) {
                        $logoData = base64_encode(file_get_contents($logoPath));
                    }
                @endphp
                @if($logoData)
                    <img src="data:image/png;base64,{{ $logoData }}" alt="Primadaya Print"
                        style="max-height: 48px; width: auto; display: block; margin-bottom: 4px;">
                @else
                    <div style="font-size: 24px; font-weight: bold; color: #1d4ed8;">PRIMADAYA PRINT</div>
                @endif
            </div>
            <div class="invoice-header-right">
                <div class="invoice-number-label">Invoice</div>
                <div class="invoice-number">{{ $transaction->transaction_number }}</div>
                <div>
                    @php
                        $statusClass = match ($transaction->status) {
                            'diproses' => 'status-diproses',
                            'selesai' => 'status-selesai',
                            'diambil' => 'status-diambil',
                            default => 'status-pending',
                        };
                    @endphp
                    <span class="status-badge {{ $statusClass }}">
                        {{ $transaction->status_label }}
                    </span>
                </div>
            </div>
        </div>

        {{-- ===================== INFO PELANGGAN & KASIR ===================== --}}
        <div class="info-section">
            <div class="info-col">
                <div class="info-label">Pelanggan</div>
                @if ($transaction->customer)
                    <div class="info-value">{{ $transaction->customer->name }}</div>
                    @if ($transaction->customer->phone)
                        <div class="info-value-sub">{{ $transaction->customer->phone }}</div>
                    @endif
                @else
                    <div class="info-value">Pelanggan Umum</div>
                    <div class="info-value-sub">-</div>
                @endif
            </div>
            <div class="info-col">
                <div class="info-label">Kasir</div>
                <div class="info-value">{{ $transaction->user->name }}</div>
                <div class="info-value-sub">{{ $transaction->created_at->format('d/m/Y') }}</div>
            </div>
            <div class="info-col">
                <div class="info-label">Tanggal & Waktu</div>
                <div class="info-value">{{ $transaction->created_at->format('d F Y') }}</div>
                <div class="info-value-sub">Pukul
                    {{ $transaction->created_at->setTimezone('Asia/Jakarta')->format('H:i') }} WIB</div>
            </div>
        </div>

        {{-- ===================== TABEL ITEM ===================== --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%">#</th>
                    <th>Layanan & Keterangan</th>
                    <th class="text-center" style="width: 12%">Qty</th>
                    <th class="text-right" style="width: 18%">Harga Satuan</th>
                    <th class="text-right" style="width: 18%">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transaction->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div class="service-name">{{ $item->service_name }}</div>
                            @if ($item->paper_size_name || ($item->print_type && $item->print_type !== 'na'))
                                <div class="service-detail">
                                    @if ($item->paper_size_name)
                                        Kertas {{ $item->paper_size_name }}
                                    @endif
                                    @if ($item->paper_size_name && $item->print_type !== 'na')
                                        |
                                    @endif
                                    @if ($item->print_type === 'bw')
                                        Hitam Putih
                                    @elseif($item->print_type === 'color')
                                        Warna Full
                                    @endif
                                </div>
                            @endif
                            @if ($item->width_meter && $item->height_meter)
                                @php
                                    $area = $item->width_meter * $item->height_meter;
                                    $pricePerMeter = $area > 0 ? $item->unit_price / $area : 0;
                                @endphp
                                <div class="service-detail">
                                    Ukuran: {{ number_format($item->width_meter, 2) }}m &times; {{ number_format($item->height_meter, 2) }}m = {{ number_format($area, 2) }} m²
                                </div>
                                <div class="service-detail">
                                    Harga/m²: Rp {{ number_format($pricePerMeter, 0, ',', '.') }}
                                </div>
                            @endif
                            @if ($item->original_filename)
                                <div class="service-file">&#128206; {{ $item->original_filename }}</div>
                            @endif
                            @if ($item->item_notes)
                                <div class="service-note">Catatan: {{ $item->item_notes }}</div>
                            @endif
                        </td>
                        <td class="text-center">{{ $item->qty }}</td>
                        <td class="text-right">
                            @if ($item->width_meter && $item->height_meter)
                                Rp {{ number_format($item->unit_price, 0, ',', '.') }}<br>
                                <span class="service-detail">(total area)</span>
                            @else
                                Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                            @endif
                        </td>
                        <td class="text-right font-bold">
                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- ===================== RINGKASAN & PEMBAYARAN ===================== --}}
        <div class="summary-section">
            <div class="summary-left">
                {{-- Catatan Kasir --}}
                @if ($transaction->notes)
                    <div class="notes-box">
                        <div class="notes-label">&#9888; Catatan Kasir</div>
                        <div class="notes-text">{{ $transaction->notes }}</div>
                    </div>
                @else
                    <div style="color: #9ca3af; font-size: 10px; font-style: italic; padding-top: 8px;">
                        Dokumen ini merupakan bukti transaksi yang sah.<br>
                        Terima kasih telah menggunakan layanan kami.
                    </div>
                @endif
            </div>

            <div class="summary-right">
                {{-- Rincian Harga --}}
                <div class="price-summary">
                    <div class="price-row">
                        <div class="price-row-label">Subtotal</div>
                        <div class="price-row-value">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</div>
                    </div>
                    @if ($transaction->discount_amount > 0)
                        <div class="price-row price-row-discount">
                            <div class="price-row-label">Diskon
                                ({{ number_format($transaction->discount_percent, 0) }}%)</div>
                            <div class="price-row-value text-danger">- Rp
                                {{ number_format($transaction->discount_amount, 0, ',', '.') }}</div>
                        </div>
                    @endif
                    <div class="price-row price-row-total">
                        <div class="price-row-label">TOTAL TAGIHAN</div>
                        <div class="price-row-value">Rp {{ number_format($transaction->total, 0, ',', '.') }}</div>
                    </div>
                </div>

                {{-- Info Pembayaran --}}
                @php
                    $paymentStatus = $transaction->payment_status ?? 'belum_bayar';
                @endphp
                <div class="payment-info">

                    {{-- Header status badge --}}
                    @if ($paymentStatus === 'lunas')
                        <div class="payment-status-header ps-lunas">
                            <span class="ps-icon">✅ Pembayaran</span>
                            <span class="ps-label">LUNAS</span>
                        </div>
                    @elseif($paymentStatus === 'dp')
                        <div class="payment-status-header ps-dp">
                            <span class="ps-icon">⏳ Pembayaran</span>
                            <span class="ps-label">DP / SEBAGIAN</span>
                        </div>
                    @else
                        <div class="payment-status-header ps-belum-bayar">
                            <span class="ps-icon">⚠️ Pembayaran</span>
                            <span class="ps-label">BELUM DIBAYAR</span>
                        </div>
                    @endif

                    @if ($paymentStatus === 'lunas')
                        {{-- LUNAS: tampilkan detail pembayaran penuh --}}
                        @if ($transaction->payment_method)
                            <div class="payment-row">
                                <div class="payment-label">Metode Pembayaran</div>
                                <div class="payment-value">{{ strtoupper($transaction->payment_method) }}</div>
                            </div>
                        @endif
                        <div class="payment-row">
                            <div class="payment-label">Total Dibayar</div>
                            <div class="payment-value">Rp {{ number_format($transaction->amount_paid, 0, ',', '.') }}
                            </div>
                        </div>
                        @if ($transaction->payment_method === 'cash' && $transaction->change_amount > 0)
                            <div class="payment-row">
                                <div class="payment-label">Kembalian</div>
                                <div class="payment-value kembalian-value">Rp
                                    {{ number_format($transaction->change_amount, 0, ',', '.') }}</div>
                            </div>
                        @endif
                    @elseif($paymentStatus === 'dp')
                        {{-- DP: tampilkan uang muka dan sisa --}}
                        @if ($transaction->payment_method)
                            <div class="payment-row">
                                <div class="payment-label">Metode Pembayaran</div>
                                <div class="payment-value">{{ strtoupper($transaction->payment_method) }}</div>
                            </div>
                        @endif
                        <div class="payment-row">
                            <div class="payment-label">Uang Muka (DP)</div>
                            <div class="payment-value">Rp {{ number_format($transaction->dp_amount, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="payment-row sisa-row">
                            <div class="payment-label">Sisa Tagihan</div>
                            <div class="payment-value">Rp
                                {{ number_format($transaction->remaining_amount, 0, ',', '.') }}</div>
                        </div>
                    @else
                        {{-- BELUM BAYAR: tampilkan total yang harus dilunasi --}}
                        <div class="unpaid-notice">
                            Pesanan ini belum menerima pembayaran.<br>
                            Total tagihan: <strong>Rp {{ number_format($transaction->total, 0, ',', '.') }}</strong>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===================== FOOTER ===================== --}}
        <div class="invoice-footer">
            <div class="footer-left">
                Invoice ini dicetak otomatis oleh sistem kasir Primadaya Print.<br>
                @if (($transaction->payment_status ?? 'belum_bayar') !== 'belum_bayar')
                    Simpan sebagai bukti pembayaran yang sah.
                @else
                    Harap selesaikan pembayaran untuk melunasi pesanan ini.
                @endif
            </div>
            <div class="footer-right">
                <strong>Primadaya Print</strong>
                Dicetak: {{ now()->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
            </div>
        </div>
    </div>

    @if(!$is_pdf)
    <a href="{{ url()->current() }}/pdf" class="download-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
        Download PDF
    </a>
    @endif
</body>

</html>
