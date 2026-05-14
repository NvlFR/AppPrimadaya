<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Struk {{ $transaction->transaction_number }}</title>
    @php
        $paperWidth = $paperWidth ?? 58;
        $pageHeight = $pageHeight ?? 180;
        $bodyPaddingX = $paperWidth === 58 ? 2.8 : 3.4;
        $baseFontSize = $paperWidth === 58 ? 11 : 12;
        $headerFontSize = $paperWidth === 58 ? 14 : 16;
        $taglineFontSize = $paperWidth === 58 ? 10 : 11;
        $logoMaxWidth = $paperWidth === 58 ? 126 : 152;
    @endphp
    <style>
        /* ===================================================
         * CSS Thermal Receipt
         * Dioptimalkan untuk printer thermal via window.print()
         * =================================================== */

        :root {
            --paper-width: {{ $paperWidth }}mm;
            --page-height: {{ $pageHeight }}mm;
            --body-padding-x: {{ $bodyPaddingX }}mm;
            --font-size-base: {{ $baseFontSize }}px;
            --font-size-header: {{ $headerFontSize }}px;
            --font-size-tagline: {{ $taglineFontSize }}px;
        }

        @page {
            size: var(--paper-width) var(--page-height);
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            width: var(--paper-width);
            height: auto;
            background: #ffffff;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: var(--font-size-base);
            font-weight: 600;
            line-height: 1.28;
            letter-spacing: 0.15px;
            color: #000000;
            background: #ffffff;
            width: var(--paper-width);
            height: auto;
            margin: 0;
            padding: 4mm var(--body-padding-x);
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .receipt {
            width: 100%;
        }

        /* ===================================================
         * Header Toko
         * =================================================== */
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 7px;
            margin-bottom: 7px;
        }

        .shop-logo {
            max-width: {{ $logoMaxWidth }}px;
            height: auto;
            display: block;
            margin: 0 auto 5px;
            filter: grayscale(100%) contrast(220%) brightness(0.7);
        }

        .shop-name {
            font-size: var(--font-size-header);
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .shop-tagline {
            font-size: var(--font-size-tagline);
            font-weight: 600;
            margin-top: 2px;
        }

        /* ===================================================
         * Info Transaksi (nomor, tanggal, kasir)
         * =================================================== */
        .info-section {
            border-bottom: 1px dashed #000;
            padding-bottom: 7px;
            margin-bottom: 7px;
            font-size: 11px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 3px;
        }

        .info-label {
            color: #000000ff;
            font-weight: 700;
        }

        .info-value {
            font-weight: 700;
            text-align: right;
        }

        /* ===================================================
         * Tabel Item
         * =================================================== */
        .items-header {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
            margin-bottom: 5px;
        }

        .item-row {
            margin-bottom: 7px;
            font-size: 11px;
        }

        .item-name {
            font-weight: 700;
            word-break: break-word;
        }

        .item-detail {
            font-size: 10px;
            font-weight: 600;
            color: #000000ff;
            margin-left: 2px;
            margin-top: 1px;
        }

        .item-note {
            font-size: 10px;
            font-weight: 600;
            color: #000;
            margin-left: 2px;
            margin-top: 1px;
        }

        .item-pricing {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
            margin-top: 3px;
            font-size: 11px;
        }

        .item-qty-price {
            color: #000000ff;
            font-weight: 600;
        }

        .item-subtotal {
            font-weight: 700;
            text-align: right;
        }

        /* ===================================================
         * Ringkasan Total
         * =================================================== */
        .summary-section {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 7px 0;
            margin: 7px 0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            font-size: 11px;
            margin-bottom: 4px;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            font-size: 15px;
            font-weight: 700;
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px solid #000;
        }

        /* ===================================================
         * Info Pembayaran
         * =================================================== */
        .payment-section {
            font-size: 11px;
            margin-bottom: 7px;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 3px;
        }

        .kembalian-value {
            font-size: 13px;
            font-weight: 700;
        }

        /* Badge Status Bayar */
        .payment-status-badge {
            display: inline-block;
            font-weight: 700;
            font-size: 11px;
            padding: 3px 7px;
            border: 1.5px solid #000;
            border-radius: 3px;
            text-transform: uppercase;
        }

        .badge-belum-bayar {
            border-color: #000;
        }

        .badge-dp {
            border-color: #000;
        }

        .badge-lunas {
            border-color: #000;
        }

        .sisa-tagihan-label {
            font-weight: 700;
        }

        .sisa-tagihan-value {
            font-weight: 700;
            font-size: 13px;
        }

        /* ===================================================
         * Footer
         * =================================================== */
        .footer {
            border-top: 1px dashed #000;
            padding-top: 7px;
            margin-top: 7px;
            text-align: center;
            font-size: 10px;
            font-weight: 600;
            color: #000;
        }

        .footer-thanks {
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        /* ===================================================
         * Print-only: Sembunyikan tombol cetak saat print
         * =================================================== */
        .no-print {
            text-align: center;
            margin-bottom: 8px;
        }

        @media print {

            html,
            body {
                width: var(--paper-width);
                height: auto;
            }

            .no-print {
                display: none;
            }

            body {
                padding: 2.5mm var(--body-padding-x);
            }

            .receipt,
            .summary-section,
            .payment-section,
            .footer,
            .item-row {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }

        /* ===================================================
         * Tombol Cetak (hanya tampil di layar)
         * =================================================== */
        .btn-print {
            display: inline-block;
            padding: 8px 20px;
            background: #1d4ed8;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-family: Arial, sans-serif;
            font-weight: bold;
            cursor: pointer;
            margin: 4px;
        }

        .btn-close {
            display: inline-block;
            padding: 8px 20px;
            background: #f3f4f6;
            color: #333;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            font-family: Arial, sans-serif;
            cursor: pointer;
            margin: 4px;
        }
    </style>
</head>

<body>

    <!-- Tombol cetak — tidak ikut tercetak (Issue #12) -->
    <div class="no-print" style="font-family: Arial, sans-serif; padding: 10px 0;">
        <button class="btn-print" onclick="window.print()">🖨️ Cetak Struk</button>
        <button class="btn-close" onclick="window.close()">✕ Tutup</button>
    </div>

    <div class="receipt" id="thermal-receipt">
        <!-- ========================
         HEADER TOKO
         ======================== -->
        <div class="header">
            <img src="{{ asset('logo.png') }}" alt="Primadaya Print" class="shop-logo">
            {{-- <div class="shop-name">Primadaya Print</div> --}}
            {{-- <div class="shop-tagline">Percetakan Digital</div> --}}
        </div>

        <!-- ========================
         INFO TRANSAKSI
         ======================== -->
        <div class="info-section">
            {{-- <div class="info-row">
            <span class="info-label">No. Struk</span>
            <span class="info-value">{{ $transaction->transaction_number }}</span>
        </div> --}}
            <div class="info-row">
                <span class="info-label">Tanggal</span>
                <span
                    class="info-value">{{ $transaction->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                    WIB</span>
            </div>
            {{-- <div class="info-row">
                <span class="info-label">Kasir</span>
                <span class="info-value">{{ $transaction->user->name }}</span>
            </div> --}}
            @if ($transaction->customer)
                <div class="info-row">
                    <span class="info-label">Pelanggan</span>
                    <span class="info-value">{{ $transaction->customer->name }}</span>
                </div>
                @if ($transaction->customer->phone)
                    <div class="info-row">
                        <span class="info-label">No. HP</span>
                        <span class="info-value">{{ $transaction->customer->phone }}</span>
                    </div>
                @endif
            @endif
        </div>

        <!-- ========================
         DAFTAR ITEM
         ======================== -->
        <div class="items-section">
            <div class="items-header">
                <span>Layanan</span>
                <span>Total</span>
            </div>

            @foreach ($transaction->items as $item)
                <div class="item-row">
                    <div class="item-name">{{ $item->service_name }}</div>
                    @if ($item->paper_size_name || ($item->print_type && $item->print_type !== 'na'))
                        <div class="item-detail">
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
                        {{-- <div class="item-detail">
                            {{ number_format($item->width_meter, 2) }}m x {{ number_format($item->height_meter, 2) }}m
                            = {{ number_format($area, 2) }} m
                        </div> --}}
                        <div class="item-detail">
                            Harga/M: Rp {{ number_format($pricePerMeter, 0, ',', '.') }}
                        </div>
                    @endif
                    @if ($item->item_notes)
                        <div class="item-note">Catatan: {{ $item->item_notes }}</div>
                    @endif
                    <div class="item-pricing">
                        <span class="item-qty-price">
                            @if ($item->width_meter && $item->height_meter)
                                {{ $item->qty }} pcs
                            @else
                                {{ $item->qty }} x Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                            @endif
                        </span>
                        <span class="item-subtotal">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- ========================
         RINGKASAN TOTAL
         ======================== -->
        <div class="summary-section">
            <div class="summary-row">
                <span>Subtotal</span>
                <span>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
            </div>
            @if ($transaction->discount_amount > 0)
                <div class="summary-row">
                    <span>Diskon ({{ number_format($transaction->discount_percent, 0) }}%)</span>
                    <span>- Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
                </div>
            @endif
            <div class="summary-total">
                <span>TOTAL</span>
                <span>Rp {{ number_format($transaction->total, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- ========================
         INFO PEMBAYARAN
         ======================== -->
        <div class="payment-section">
            @php
                $paymentStatus = $transaction->payment_status ?? 'belum_bayar';
            @endphp

            {{-- Status Pembayaran --}}
            <div class="payment-row" style="margin-bottom: 4px;">
                <span>Status Bayar</span>
                <span>
                    @if ($paymentStatus === 'lunas')
                        <span class="payment-status-badge badge-lunas">★ LUNAS</span>
                    @elseif($paymentStatus === 'dp')
                        <span class="payment-status-badge badge-dp">DP</span>
                    @else
                        <span class="payment-status-badge badge-belum-bayar">BELUM BAYAR</span>
                    @endif
                </span>
            </div>

            @if ($paymentStatus === 'lunas')
                {{-- Sudah Lunas: tampilkan metode + jumlah bayar + kembalian --}}
                @if ($transaction->payment_method)
                    <div class="payment-row">
                        <span>Metode Bayar</span>
                        <span><strong>{{ strtoupper($transaction->payment_method) }}</strong></span>
                    </div>
                @endif
                <div class="payment-row">
                    <span>Dibayar</span>
                    <span>Rp {{ number_format($transaction->amount_paid, 0, ',', '.') }}</span>
                </div>
                @if ($transaction->payment_method === 'cash' && $transaction->change_amount > 0)
                    <div class="payment-row">
                        <span>Kembalian</span>
                        <span class="kembalian-value">Rp
                            {{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
            @elseif($paymentStatus === 'dp')
                {{-- DP: tampilkan uang muka dan sisa tagihan --}}
                @if ($transaction->payment_method)
                    <div class="payment-row">
                        <span>Metode Bayar</span>
                        <span><strong>{{ strtoupper($transaction->payment_method) }}</strong></span>
                    </div>
                @endif
                <div class="payment-row">
                    <span>Uang Muka (DP)</span>
                    <span>Rp {{ number_format($transaction->dp_amount, 0, ',', '.') }}</span>
                </div>
                <div class="payment-row" style="border-top: 1px dashed #000; padding-top: 3px; margin-top: 3px;">
                    <span class="sisa-tagihan-label">SISA TAGIHAN</span>
                    <span class="sisa-tagihan-value">Rp
                        {{ number_format($transaction->remaining_amount, 0, ',', '.') }}</span>
                </div>
            @else
                {{-- Belum Bayar: tampilkan total yang harus dibayar --}}
                <div class="payment-row" style="border-top: 1px dashed #000; padding-top: 4px; margin-top: 3px;">
                    <span class="sisa-tagihan-label">TOTAL TAGIHAN</span>
                    <span class="sisa-tagihan-value">Rp {{ number_format($transaction->total, 0, ',', '.') }}</span>
                </div>
                <div style="font-size: 10px; font-weight: 600; margin-top: 4px; text-align: center;">
                    Pembayaran belum diterima
                </div>
            @endif
        </div>

        <!-- ========================
         CATATAN KASIR (jika ada)
         ======================== -->
        @if ($transaction->notes)
            <div
                style="font-size: 10px; font-weight: 600; border: 1px dashed #000; padding: 5px; margin-bottom: 7px; border-radius: 2px;">
                <strong>Catatan:</strong> {{ $transaction->notes }}
            </div>
        @endif

        <!-- ========================
         FOOTER
         ======================== -->
        <div class="footer">
            <div class="footer-thanks">Terima Kasih!</div>
            @if (($transaction->payment_status ?? 'belum_bayar') !== 'belum_bayar')
                <div>Simpan struk ini sebagai bukti pembayaran.</div>
            @else
                <div>Harap selesaikan pembayaran sebelum mengambil pesanan.</div>
            @endif
            {{-- <div style="margin-top: 3px;">Primadaya </div> --}}
            <div style="margin-top: 4px; font-size: 9px; font-weight: 600; color: #000;">
                Dicetak: {{ now()->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
            </div>
        </div>
    </div>

</body>
<script>
    function shouldAutoPrint() {
        const params = new URLSearchParams(window.location.search);
        return params.get('autoprint') === '1';
    }

    function applyThermalPageSize() {
        const receipt = document.getElementById('thermal-receipt');
        if (!receipt) {
            return;
        }

        const pxToMm = 25.4 / 96;
        const measuredHeightPx = receipt.getBoundingClientRect().height;
        const measuredHeightMm = Math.ceil((measuredHeightPx * pxToMm) + 8);
        const finalHeightMm = Math.max(measuredHeightMm, {{ $pageHeight }});

        document.documentElement.style.setProperty('--page-height', `${finalHeightMm}mm`);

        let dynamicPageStyle = document.getElementById('dynamic-page-size');
        if (!dynamicPageStyle) {
            dynamicPageStyle = document.createElement('style');
            dynamicPageStyle.id = 'dynamic-page-size';
            document.head.appendChild(dynamicPageStyle);
        }

        dynamicPageStyle.textContent = `@page { size: {{ $paperWidth }}mm ${finalHeightMm}mm; margin: 0; }`;
    }

    window.addEventListener('load', () => {
        applyThermalPageSize();
        setTimeout(applyThermalPageSize, 150);

        if (shouldAutoPrint()) {
            setTimeout(() => {
                window.print();
            }, 350);
        }
    });
</script>

</html>
