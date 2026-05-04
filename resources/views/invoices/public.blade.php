<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $transaction->transaction_number }} - Primadaya Print</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #1d4ed8;
            --primary-dark: #1e40af;
            --secondary: #64748b;
            --success: #059669;
            --warning: #d97706;
            --danger: #dc2626;
            --background: #f1f5f9;
            --card: #ffffff;
            --text: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background);
            color: var(--text);
            line-height: 1.5;
            padding: 40px 20px;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            max-width: 850px;
            margin: 0 auto;
            position: relative;
        }

        .invoice-card {
            background: var(--card);
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
            overflow: hidden;
            padding: 40px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 24px;
            margin-bottom: 30px;
        }

        .brand-logo {
            max-height: 56px;
            width: auto;
        }

        .invoice-meta {
            text-align: right;
        }

        .meta-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            font-weight: 700;
        }

        .invoice-number {
            font-family: 'JetBrains Mono', monospace;
            font-size: 20px;
            font-weight: 700;
            color: var(--text);
            margin: 4px 0;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-diproses { background: #dbeafe; color: #1e40af; }
        .status-selesai { background: #d1fae5; color: #065f46; }
        .status-diambil { background: #f3f4f6; color: #374151; }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .info-item .label {
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
            margin-bottom: 6px;
            display: block;
        }

        .info-item .value {
            font-weight: 600;
            font-size: 14px;
        }

        .info-item .sub-value {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* Table */
        .table-container {
            margin-bottom: 30px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: var(--primary);
            color: white;
            text-align: left;
            padding: 12px 16px;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }

        .service-name { font-weight: 700; color: #0f172a; }
        .service-details { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
        .service-note { font-size: 12px; color: var(--warning); font-style: italic; margin-top: 4px; }
        .service-file { font-size: 12px; color: var(--primary); margin-top: 4px; display: flex; align-items: center; gap: 4px; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Summary Section */
        .summary-container {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 40px;
        }

        .notes-section {
            background: #fffbeb;
            border-left: 4px solid var(--warning);
            padding: 16px;
            border-radius: 0 8px 8px 0;
            height: fit-content;
        }

        .notes-title {
            font-size: 12px;
            font-weight: 700;
            color: #92400e;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .notes-content { font-size: 13px; color: #78350f; }

        .price-summary {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }

        .price-row:last-child { border-bottom: none; }
        .price-row.total { background: var(--primary); color: white; padding: 16px; }
        .price-row.total .label { font-weight: 700; font-size: 15px; }
        .price-row.total .value { font-weight: 800; font-size: 18px; }

        /* Payment Section */
        .payment-card {
            margin-top: 20px;
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .payment-status-header {
            padding: 12px 16px;
            font-weight: 800;
            font-size: 12px;
            text-transform: uppercase;
            display: flex;
            justify-content: space-between;
        }

        .ps-lunas { background: #f0fdf4; color: #166534; border-bottom: 2px solid #86efac; }
        .ps-dp { background: #fffbeb; color: #92400e; border-bottom: 2px solid #fcd34d; }
        .ps-unpaid { background: #fef2f2; color: #991b1b; border-bottom: 2px solid #fca5a5; }

        .payment-details { padding: 12px 16px; }
        .payment-line { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; }
        .payment-line:last-child { margin-bottom: 0; }
        .payment-line .val { font-weight: 700; }
        .payment-line.highlight { color: var(--success); font-size: 14px; }
        .payment-line.warning { color: var(--danger); }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px dashed var(--border);
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Floating Button */
        .download-fab {
            position: fixed;
            bottom: 32px;
            right: 32px;
            background: var(--primary);
            color: white;
            padding: 16px 28px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 25px -5px rgba(29, 78, 216, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 100;
        }

        .download-fab:hover {
            background: var(--primary-dark);
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 20px 30px -10px rgba(29, 78, 216, 0.5);
        }

        @media (max-width: 768px) {
            .invoice-card { padding: 24px; }
            .info-grid { grid-template-columns: 1fr; gap: 15px; }
            .summary-container { grid-template-columns: 1fr; }
            .header { flex-direction: column; gap: 16px; }
            .invoice-meta { text-align: left; }
            .download-fab { bottom: 20px; right: 20px; padding: 14px 20px; font-size: 14px; }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="invoice-card">
            
            <!-- Header -->
            <div class="header">
                <div class="brand">
                    @php
                        $logoPath = public_path('logo.png');
                        $logoData = '';
                        if (file_exists($logoPath)) {
                            $logoData = base64_encode(file_get_contents($logoPath));
                        }
                    @endphp
                    @if($logoData)
                        <img src="data:image/png;base64,{{ $logoData }}" alt="Primadaya Print" class="brand-logo">
                    @endif
                    <div style="margin-top: 8px;">
                        <h2 style="font-size: 16px; font-weight: 800; color: var(--primary); margin: 0;">PRIMADAYA PRINT</h2>
                        <p style="font-size: 11px; color: var(--text-muted); margin: 0;">Digital Printing & Offset</p>
                    </div>
                </div>
                <div class="invoice-meta">
                    <span class="meta-label">Invoice Digital</span>
                    <h1 class="invoice-number">{{ $transaction->transaction_number }}</h1>
                    <div class="status-badge status-{{ $transaction->status }}">
                        {{ $transaction->status_label }}
                    </div>
                </div>
            </div>

            <!-- Customer & Transaction Info -->
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">Pelanggan</span>
                    <div class="value">{{ $transaction->customer ? $transaction->customer->name : 'Pelanggan Umum' }}</div>
                    @if($transaction->customer && $transaction->customer->phone)
                        <div class="sub-value">{{ $transaction->customer->phone }}</div>
                    @endif
                </div>
                <div class="info-item">
                    <span class="label">Tanggal Transaksi</span>
                    <div class="value">{{ $transaction->created_at->format('d F Y') }}</div>
                    <div class="sub-value">{{ $transaction->created_at->setTimezone('Asia/Jakarta')->format('H:i') }} WIB</div>
                </div>
                <div class="info-item">
                    <span class="label">Kasir</span>
                    <div class="value">{{ $transaction->user->name }}</div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50%">Layanan & Detail</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Harga</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transaction->items as $item)
                        <tr>
                            <td>
                                <div class="service-name">{{ $item->service_name }}</div>
                                @if($item->paper_size_name || ($item->print_type && $item->print_type !== 'na'))
                                    <div class="service-details">
                                        {{ $item->paper_size_name ? 'Kertas ' . $item->paper_size_name : '' }}
                                        {{ ($item->paper_size_name && $item->print_type !== 'na') ? '|' : '' }}
                                        {{ $item->print_type === 'bw' ? 'Hitam Putih' : ($item->print_type === 'color' ? 'Warna' : '') }}
                                    </div>
                                @endif
                                @if($item->width_meter && $item->height_meter)
                                    <div class="service-details">
                                        Ukuran: {{ number_format($item->width_meter, 2) }}m &times; {{ number_format($item->height_meter, 2) }}m
                                        ({{ number_format($item->width_meter * $item->height_meter, 2) }} m²)
                                    </div>
                                @endif
                                @if($item->original_filename)
                                    <div class="service-file">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.51a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                        {{ $item->original_filename }}
                                    </div>
                                @endif
                                @if($item->item_notes)
                                    <div class="service-note">Catatan: {{ $item->item_notes }}</div>
                                @endif
                            </td>
                            <td class="text-center font-bold">{{ $item->qty }}</td>
                            <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="text-right font-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Summary -->
            <div class="summary-container">
                <div class="summary-left">
                    @if($transaction->notes)
                        <div class="notes-section">
                            <div class="notes-title">Catatan Kasir</div>
                            <div class="notes-content">{{ $transaction->notes }}</div>
                        </div>
                    @else
                        <div style="color: var(--text-muted); font-size: 13px; font-style: italic;">
                            Terima kasih telah mempercayakan pesanan Anda kepada Primadaya Print. 
                            Silakan simpan invoice digital ini sebagai bukti transaksi yang sah.
                        </div>
                    @endif
                </div>
                
                <div class="summary-right">
                    <div class="price-summary">
                        <div class="price-row">
                            <span class="label">Subtotal</span>
                            <span class="value">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($transaction->discount_amount > 0)
                            <div class="price-row" style="color: var(--danger)">
                                <span class="label">Diskon ({{ number_format($transaction->discount_percent, 0) }}%)</span>
                                <span class="value">-Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="price-row total">
                            <span class="label">TOTAL TAGIHAN</span>
                            <span class="value">Rp {{ number_format($transaction->total, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Payment Info -->
                    <div class="payment-card">
                        @php $pStatus = $transaction->payment_status; @endphp
                        @if($pStatus === 'lunas')
                            <div class="payment-status-header ps-lunas">
                                <span>LUNAS</span>
                                <span>PAID</span>
                            </div>
                            <div class="payment-details">
                                <div class="payment-line">
                                    <span>Metode</span>
                                    <span class="val uppercase">{{ $transaction->payment_method ?? 'Cash' }}</span>
                                </div>
                                <div class="payment-line highlight">
                                    <span>Dibayar</span>
                                    <span class="val">Rp {{ number_format($transaction->amount_paid, 0, ',', '.') }}</span>
                                </div>
                                @if($transaction->change_amount > 0)
                                    <div class="payment-line">
                                        <span>Kembalian</span>
                                        <span class="val">Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                            </div>
                        @elseif($pStatus === 'dp')
                            <div class="payment-status-header ps-dp">
                                <span>DP / SEBAGIAN</span>
                                <span>PARTIAL</span>
                            </div>
                            <div class="payment-details">
                                <div class="payment-line">
                                    <span>Uang Muka</span>
                                    <span class="val">Rp {{ number_format($transaction->dp_amount, 0, ',', '.') }}</span>
                                </div>
                                <div class="payment-line warning highlight">
                                    <span>Sisa Tagihan</span>
                                    <span class="val">Rp {{ number_format($transaction->remaining_amount, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @else
                            <div class="payment-status-header ps-unpaid">
                                <span>BELUM DIBAYAR</span>
                                <span>UNPAID</span>
                            </div>
                            <div class="payment-details" style="text-align: center; font-size: 12px; padding: 20px;">
                                Silakan hubungi kasir untuk penyelesaian pembayaran pesanan Anda.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <div>
                    <strong>Primadaya Print</strong><br>
                    Cetak & Percetakan Digital Profesional
                </div>
                <div class="text-right">
                    Digital Invoice Generated at<br>
                    {{ now()->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Download Button -->
    <a href="{{ route('public.invoice.pdf', ['uuid' => $transaction->uuid]) }}" class="download-fab">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
        Download PDF
    </a>
</body>

</html>
