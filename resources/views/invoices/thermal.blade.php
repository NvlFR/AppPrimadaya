<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Struk {{ $transaction->transaction_number }}</title>
    <style>
        /* ===================================================
         * CSS Thermal Receipt — Lebar 80mm (58mm printable)
         * Dioptimalkan untuk printer thermal via window.print()
         * =================================================== */

        @page {
            /* Lebar standar printer thermal 80mm */
            size: 80mm auto;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            color: #000000;
            background: #ffffff;
            width: 80mm;
            /* Padding kiri-kanan 3mm agar tidak terpotong */
            padding: 4mm 3mm;
        }

        /* ===================================================
         * Header Toko
         * =================================================== */
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 6px;
            margin-bottom: 6px;
        }

        .shop-name {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .shop-tagline {
            font-size: 9px;
            margin-top: 2px;
        }

        /* ===================================================
         * Info Transaksi (nomor, tanggal, kasir)
         * =================================================== */
        .info-section {
            border-bottom: 1px dashed #000;
            padding-bottom: 6px;
            margin-bottom: 6px;
            font-size: 10px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .info-label {
            color: #555;
        }

        .info-value {
            font-weight: bold;
            text-align: right;
        }

        /* ===================================================
         * Tabel Item
         * =================================================== */
        .items-header {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
            margin-bottom: 4px;
        }

        .item-row {
            margin-bottom: 5px;
            font-size: 10px;
        }

        .item-name {
            font-weight: bold;
            word-break: break-word;
        }

        .item-detail {
            font-size: 9px;
            color: #444;
            margin-left: 2px;
        }

        .item-note {
            font-size: 9px;
            font-style: italic;
            color: #666;
            margin-left: 2px;
        }

        .item-pricing {
            display: flex;
            justify-content: space-between;
            margin-top: 2px;
            font-size: 10px;
        }

        .item-qty-price {
            color: #444;
        }

        .item-subtotal {
            font-weight: bold;
        }

        /* ===================================================
         * Ringkasan Total
         * =================================================== */
        .summary-section {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 6px 0;
            margin: 6px 0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            margin-bottom: 3px;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            font-weight: bold;
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px solid #000;
        }

        /* ===================================================
         * Info Pembayaran
         * =================================================== */
        .payment-section {
            font-size: 10px;
            margin-bottom: 6px;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .kembalian-value {
            font-size: 12px;
            font-weight: bold;
        }

        /* Badge Status Bayar */
        .payment-status-badge {
            display: inline-block;
            font-weight: bold;
            font-size: 10px;
            padding: 2px 6px;
            border: 1px solid #000;
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
            font-weight: bold;
        }

        .sisa-tagihan-value {
            font-weight: bold;
            font-size: 12px;
        }

        /* ===================================================
         * Footer
         * =================================================== */
        .footer {
            border-top: 1px dashed #000;
            padding-top: 6px;
            margin-top: 6px;
            text-align: center;
            font-size: 9px;
            color: #333;
        }

        .footer-thanks {
            font-size: 11px;
            font-weight: bold;
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
            .no-print {
                display: none;
            }

            body {
                padding: 2mm;
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

    <!-- ========================
         HEADER TOKO
         ======================== -->
    <div class="header">
        <img src="{{ asset('logo.png') }}" alt="Primadaya Print"
            style="max-width: 120px; height: auto; display: block; margin: 0 auto 4px;">
        <div class="shop-tagline">Jasa Cetak &amp; Percetakan Digital</div>
    </div>

    <!-- ========================
         INFO TRANSAKSI
         ======================== -->
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">No. Struk</span>
            <span class="info-value">{{ $transaction->transaction_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Tanggal</span>
            <span class="info-value">{{ $transaction->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                WIB</span>
        </div>
        <div class="info-row">
            <span class="info-label">Kasir</span>
            <span class="info-value">{{ $transaction->user->name }}</span>
        </div>
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
                    <div class="item-detail">
                        {{ number_format($item->width_meter, 2) }}m x {{ number_format($item->height_meter, 2) }}m = {{ number_format($area, 2) }} m²
                    </div>
                    <div class="item-detail">
                        Harga/m²: Rp {{ number_format($pricePerMeter, 0, ',', '.') }}
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
            <div style="font-size: 9px; font-style: italic; margin-top: 3px; text-align: center;">
                * Pembayaran belum diterima
            </div>
        @endif
    </div>

    <!-- ========================
         CATATAN KASIR (jika ada)
         ======================== -->
    @if ($transaction->notes)
        <div style="font-size: 9px; border: 1px dashed #666; padding: 4px; margin-bottom: 6px; border-radius: 2px;">
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
        <div style="margin-top: 3px; font-size: 8px; color: #999;">
            Dicetak: {{ now()->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
        </div>
    </div>

</body>
<script>
    // Auto-print saat halaman dibuka (opsional — aktifkan jika dikehendaki)
    // window.addEventListener('load', () => window.print());
</script>

</html>
