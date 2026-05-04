<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import {
    PrinterIcon,
    ArrowLeftIcon,
    CheckCircleIcon,
    AlertCircleIcon,
    DownloadIcon,
    PaperclipIcon,
    CreditCardIcon,
    BanknoteIcon,
    ClockIcon,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useFormatRupiah } from '@/composables/useFormatRupiah';

interface TransactionItemService {
    id: number;
    name: string;
}

interface TransactionItem {
    id: number;
    service_name: string;
    paper_size_name: string | null;
    print_type: string;
    print_type_label: string;
    qty: number;
    unit_price: string | number;
    subtotal: string | number;
    item_notes: string | null;
    original_filename: string | null;
    width_meter: string | number | null;
    height_meter: string | number | null;
    price_per_meter: string | number | null;
    service?: TransactionItemService | null;
}

interface Transaction {
    id: number;
    transaction_number: string;
    invoice_number: string | null;
    uuid: string;
    customer: { id: number; name: string; phone: string | null } | null;
    kasir_name: string;
    subtotal: string | number;
    discount_percent: string | number;
    discount_amount: string | number;
    total: string | number;
    payment_method: string | null;
    amount_paid: string | number | null;
    change_amount: string | number | null;
    status: string;
    status_label: string;
    payment_status: string;
    payment_status_label: string;
    dp_amount: string | number | null;
    remaining_amount: string | number | null;
    notes: string | null;
    created_at: string;
    items: TransactionItem[];
}

const props = defineProps<{
    transaction: Transaction;
    status_options: Record<string, string>;
    payment_status_options: Record<string, string>;
}>();

const formStatus = useForm({
    status: props.transaction.status,
});

const formPayment = useForm({
    payment_type: 'lunas' as 'lunas' | 'dp',
    payment_method: 'cash' as 'cash' | 'transfer' | 'qris',
    amount_paid: '' as string | number,
});

const { formatRupiah } = useFormatRupiah();
const customerMissing = computed(() => !props.transaction.customer);
const toNumber = (value: string | number | null | undefined) => Number(value ?? 0);

const getStatusColor = (status: string) => {
    switch (status) {
        case 'selesai': return 'bg-green-100 text-green-800 border-green-200';
        case 'diambil': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'diproses': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'pending': default: return 'bg-orange-100 text-orange-800 border-orange-200';
    }
};

const getPaymentStatusColor = (status: string) => {
    switch (status) {
        case 'lunas':       return 'bg-green-100 text-green-800 border-green-200';
        case 'dp':          return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'belum_bayar': return 'bg-red-100 text-red-800 border-red-200';
        default:            return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const isUnpaid = computed(() => props.transaction.payment_status !== 'lunas');

// Sisa tagihan yang harus dibayar sekarang
const sisaTagihan = computed(() => toNumber(props.transaction.remaining_amount ?? props.transaction.total));

// Placeholder nominal berdasarkan tipe pembayaran
const amountPlaceholder = computed(() => {
    if (formPayment.payment_type === 'lunas') {
        return `Min. ${formatRupiah(sisaTagihan.value)}`;
    }
    return 'Masukkan Nominal DP';
});


const shareToWhatsApp = () => {
    const trx = props.transaction;
    const items = trx.items
        .map((item) => `- ${item.service_name || item.service?.name || 'Layanan'} (x${item.qty})`)
        .join('\n');
    const publicUrl = window.location.origin + '/invoice/' + trx.uuid;
    const paymentMethod = (trx.payment_method ?? 'cash').toUpperCase();

    let message = `Halo *${trx.customer?.name || 'Pelanggan'}*,\n\n`;
    message += `Berikut adalah detail pesanan Anda di *Primadaya Print*:\n\n`;
    message += `*No. Invoice:* ${trx.invoice_number || trx.transaction_number}\n`;
    message += `*Status:* ${trx.status_label}\n`;
    message += `*Metode Bayar:* ${paymentMethod}

`;
    message += `*Layanan*:\n${items}

`;
    message += `*Total:* ${formatRupiah(trx.total)}\n`;

    const remaining = toNumber(trx.remaining_amount);
    if (remaining > 0) {
        message += `*Sisa Tagihan:* ${formatRupiah(remaining)}\n`;
    } else {
        message += `*Status Bayar:* LUNAS\n`;
    }

    message += `\n📄 *Lihat Invoice (Web):*\n${publicUrl}
`;
    message += `📥 *Download PDF:*\n${publicUrl}/pdf

`;
    message += `Terima kasih telah mempercayakan pesanan Anda kepada kami!`;

    const phone = trx.customer?.phone || '';
    const encodedMessage = encodeURIComponent(message);
    const waUrl = `https://wa.me/${phone.replace(/[^0-9]/g, '')}?text=${encodedMessage}`;

    window.open(waUrl, '_blank');
};


const updateStatus = () => {
    formStatus.patch(route('transactions.status', props.transaction.id), {
        preserveScroll: true,
    });
};

const submitPayment = () => {
    formPayment.post(route('transactions.payment', props.transaction.id), {
        preserveScroll: true,
        onSuccess: () => {
            formPayment.reset('amount_paid');
        },
    });
};

const downloadPdf = () => {
    window.open(route('transactions.pdf', props.transaction.id), '_blank');
};

const printThermal = () => {
    const url = route('transactions.thermal', props.transaction.id);
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = url;
    document.body.appendChild(iframe);
    iframe.onload = () => {
        const iframeWindow = iframe.contentWindow;
        if (!iframeWindow) {
            document.body.removeChild(iframe);
            return;
        }

        iframeWindow.focus();
        iframeWindow.print();
        setTimeout(() => {
            document.body.removeChild(iframe);
        }, 1000);
    };
};
</script>

<template>
    <AppLayout :breadcrumbs="[
        { title: 'Dashboard', href: route('dashboard') },
        { title: 'Transaksi', href: route('transactions.index') || '#' },
        { title: 'Detail Transaksi', href: route('transactions.show', transaction.id) }
    ]">
        <Head :title="`Pesanan ${transaction.transaction_number}`" />

        <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <Link :href="route('transactions.index')">
                        <Button variant="outline" size="icon" class="h-8 w-8 rounded-full">
                            <ArrowLeftIcon class="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3 flex-wrap">
                            {{ transaction.transaction_number }}
                            <Badge variant="outline" :class="getStatusColor(transaction.status)">
                                {{ transaction.status_label }}
                            </Badge>
                            <Badge variant="outline" :class="getPaymentStatusColor(transaction.payment_status)">
                                {{ transaction.payment_status_label }}
                            </Badge>
                        </h1>
                        <p class="text-sm text-gray-500">{{ transaction.created_at }} &bull; Kasir: {{ transaction.kasir_name }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <Button variant="outline" size="sm" class="bg-green-50 text-green-700 border-green-200 hover:bg-green-100" @click="shareToWhatsApp">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        Share WA
                    </Button>
                    <Button variant="outline" @click="printThermal" class="border-gray-300 text-gray-700 hover:bg-gray-50">
                        <PrinterIcon class="h-4 w-4 mr-2" /> Struk Thermal
                    </Button>
                    <Button variant="outline" @click="downloadPdf" class="border-blue-200 text-blue-700 hover:bg-blue-50">
                        <DownloadIcon class="h-4 w-4 mr-2" /> Download PDF
                    </Button>
                </div>
            </div>

            <!-- Tagihan Belum Lunas — Alert Banner -->
            <div v-if="isUnpaid" class="rounded-xl border border-orange-200 bg-orange-50 px-5 py-4 flex items-start gap-3">
                <ClockIcon class="h-5 w-5 text-orange-500 mt-0.5 flex-shrink-0" />
                <div>
                    <p class="font-semibold text-orange-800 text-sm">
                        {{ transaction.payment_status === 'dp' ? 'Pembayaran Belum Lunas' : 'Belum Ada Pembayaran' }}
                    </p>
                    <p class="text-sm text-orange-700 mt-0.5">
                        Sisa tagihan:
                        <span class="font-bold">{{ formatRupiah(sisaTagihan) }}</span>
                        <span v-if="Number(transaction.dp_amount) > 0">
                            (DP terbayar: {{ formatRupiah(transaction.dp_amount) }})
                        </span>
                    </p>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                <!-- Left Column (Invoice Content) -->
                <div class="col-span-1 space-y-6 lg:col-span-2">

                    <!-- Customer Info -->
                    <div class="bg-white rounded-xl border shadow-sm p-6">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4 border-b pb-2">Informasi Pelanggan</h3>
                        <div v-if="transaction.customer" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-sm text-gray-500">Nama Pelanggan / Instansi</p>
                                <p class="font-medium text-gray-900">{{ transaction.customer?.name ?? 'Pelanggan Umum' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">No. Telepon / WhatsApp</p>
                                <p class="font-medium text-gray-900">{{ transaction.customer?.phone ?? '-' }}</p>
                            </div>
                        </div>
                        <div v-else class="flex items-center text-gray-500 italic text-sm">
                            <div class="w-full rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 not-italic">
                                <div class="flex items-start gap-3">
                                    <AlertCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" />
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold text-amber-800">Data pelanggan tidak tersedia</p>
                                        <p class="text-sm text-amber-700">
                                            Transaksi ini tercatat tanpa pelanggan terhubung.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
                        <div class="border-b border-gray-100 bg-gray-50/50 px-4 py-4 sm:px-6">
                            <h3 class="font-semibold text-gray-900">Detail Pesanan</h3>
                        </div>
                        <div class="mobile-data-list p-4 sm:p-6">
                            <div v-for="item in transaction.items" :key="`transaction-show-mobile-${item.id}`" class="mobile-data-card space-y-3">
                                <div class="space-y-2">
                                    <p class="text-base font-semibold text-gray-900 break-words">{{ item.service_name }}</p>
                                    <div class="flex flex-wrap gap-2 text-xs text-gray-500">
                                        <Badge v-if="item.paper_size_name" variant="secondary" class="font-normal">{{ item.paper_size_name }}</Badge>
                                        <Badge v-if="item.print_type !== 'na'" variant="outline" class="font-normal">{{ item.print_type_label }}</Badge>
                                    </div>
                                    <div v-if="item.original_filename" class="text-xs text-blue-600 flex items-center bg-blue-50 bg-opacity-50 w-fit px-2 py-1 rounded">
                                        <PaperclipIcon class="h-3 w-3 mr-1" /> {{ item.original_filename }}
                                    </div>
                                    <div v-if="item.width_meter && item.height_meter" class="text-xs font-mono text-indigo-600 bg-indigo-50 px-2 py-1 rounded w-fit">
                                        Ukuran: {{ item.width_meter }}m x {{ item.height_meter }}m
                                    </div>
                                    <p v-if="item.item_notes" class="text-sm italic text-gray-600">
                                        Catatan: {{ item.item_notes }}
                                    </p>
                                </div>

                                <div class="grid grid-cols-3 gap-3 text-sm">
                                    <div>
                                        <p class="text-xs uppercase tracking-wide text-gray-400">Qty</p>
                                        <p class="mt-1 font-medium text-gray-900">{{ item.qty }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-wide text-gray-400">Harga</p>
                                        <p class="mt-1 font-medium text-gray-900">{{ formatRupiah(item.unit_price) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-wide text-gray-400">Subtotal</p>
                                        <p class="mt-1 font-semibold text-gray-900">{{ formatRupiah(item.subtotal) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="data-table-scroll hidden md:block">
                            <table class="data-table">
                                <thead class="bg-white text-gray-500 border-b">
                                    <tr>
                                        <th class="px-6 py-3 font-medium">Layanan &amp; Keterangan</th>
                                        <th class="px-6 py-3 font-medium text-center">Qty</th>
                                        <th class="px-6 py-3 font-medium text-right">Harga Satuan</th>
                                        <th class="px-6 py-3 font-medium text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="item in transaction.items" :key="item.id">
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-gray-900">{{ item.service_name }}</p>
                                            <div class="text-xs text-gray-500 mt-1 flex gap-2 flex-wrap">
                                                <Badge v-if="item.paper_size_name" variant="secondary" class="font-normal">{{ item.paper_size_name }}</Badge>
                                                <Badge v-if="item.print_type !== 'na'" variant="outline" class="font-normal">{{ item.print_type_label }}</Badge>
                                            </div>
                                            <div v-if="item.original_filename" class="mt-2 text-xs text-blue-600 flex items-center bg-blue-50 bg-opacity-50 w-fit px-2 py-1 rounded">
                                                <PaperclipIcon class="h-3 w-3 mr-1" /> {{ item.original_filename }}
                                            </div>
                                            <div v-if="item.width_meter && item.height_meter" class="mt-2 text-xs font-mono text-indigo-600 bg-indigo-50 px-2 py-1 rounded w-fit">
                                                Ukuran: {{ item.width_meter }}m x {{ item.height_meter }}m
                                            </div>
                                            <div v-if="item.item_notes" class="mt-2 text-xs text-gray-600 italic">
                                                Catatan: {{ item.item_notes }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center font-medium">{{ item.qty }}</td>
                                        <td class="px-6 py-4 text-right">{{ formatRupiah(item.unit_price) }}</td>
                                        <td class="px-6 py-4 text-right font-medium text-gray-900">{{ formatRupiah(item.subtotal) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Transaction Notes -->
                    <div v-if="transaction.notes" class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                        <div class="flex items-start">
                            <AlertCircleIcon class="h-5 w-5 text-amber-500 mt-0.5 mr-3 flex-shrink-0" />
                            <div>
                                <h4 class="text-sm font-semibold text-amber-800">Catatan</h4>
                                <p class="text-sm text-amber-700 mt-1">{{ transaction.notes }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (Billing & Actions) -->
                <div class="col-span-1 space-y-6">

                    <!-- Billing Summary -->
                    <div class="bg-white rounded-xl border shadow-sm p-6 space-y-4">
                        <h3 class="font-semibold text-gray-900 border-b pb-4">Ringkasan Tagihan</h3>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between text-gray-600">
                                <span>Subtotal</span>
                                <span>{{ formatRupiah(transaction.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600" v-if="Number(transaction.discount_amount) > 0">
                                <span>Diskon ({{ transaction.discount_percent }}%)</span>
                                <span class="text-red-500">-{{ formatRupiah(transaction.discount_amount) }}</span>
                            </div>
                            <div class="pt-3 border-t flex justify-between items-center">
                                <span class="font-bold text-gray-900">Total Tagihan</span>
                                <span class="text-lg font-black text-blue-600">{{ formatRupiah(transaction.total) }}</span>
                            </div>
                        </div>

                        <!-- Info Pembayaran (jika sudah ada) -->
                        <div v-if="transaction.payment_status !== 'belum_bayar'" class="bg-gray-50 rounded-lg p-4 space-y-2 mt-2 text-sm border">
                            <div v-if="Number(transaction.dp_amount) > 0" class="flex justify-between">
                                <span class="text-gray-500">DP Terbayar</span>
                                <span class="font-semibold text-green-600">{{ formatRupiah(transaction.dp_amount) }}</span>
                            </div>
                            <div v-if="Number(transaction.remaining_amount) > 0" class="flex justify-between">
                                <span class="text-gray-500">Sisa Tagihan</span>
                                <span class="font-semibold text-red-600">{{ formatRupiah(transaction.remaining_amount) }}</span>
                            </div>
                            <div v-if="transaction.payment_method" class="flex justify-between">
                                <span class="text-gray-500">Metode Bayar</span>
                                <span class="font-semibold uppercase">{{ transaction.payment_method }}</span>
                            </div>
                            <div v-if="transaction.payment_status === 'lunas'" class="flex justify-between pt-2 border-t border-gray-200">
                                <span class="text-gray-500">Kembalian</span>
                                <span class="font-semibold text-green-600">{{ formatRupiah(transaction.change_amount ?? 0) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Form Proses Pembayaran (muncul kalau belum lunas) -->
                    <div v-if="isUnpaid" class="bg-white rounded-xl border shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <CreditCardIcon class="h-4 w-4 text-blue-600" />
                            Proses Pembayaran
                        </h3>

                        <form @submit.prevent="submitPayment" class="space-y-4">
                            <!-- Tipe Pembayaran -->
                            <div class="space-y-2">
                                <Label>Tipe Pembayaran</Label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        @click="formPayment.payment_type = 'lunas'"
                                        :class="[
                                            'flex flex-col items-center justify-center rounded-lg border-2 p-3 text-sm transition-all',
                                            formPayment.payment_type === 'lunas'
                                                ? 'border-blue-500 bg-blue-50 text-blue-700 font-semibold'
                                                : 'border-gray-200 text-gray-600 hover:border-blue-200'
                                        ]"
                                    >
                                        <BanknoteIcon class="h-5 w-5 mb-1" />
                                        Bayar Lunas
                                    </button>
                                    <button
                                        type="button"
                                        @click="formPayment.payment_type = 'dp'"
                                        :class="[
                                            'flex flex-col items-center justify-center rounded-lg border-2 p-3 text-sm transition-all',
                                            formPayment.payment_type === 'dp'
                                                ? 'border-yellow-500 bg-yellow-50 text-yellow-700 font-semibold'
                                                : 'border-gray-200 text-gray-600 hover:border-yellow-200'
                                        ]"
                                    >
                                        <CreditCardIcon class="h-5 w-5 mb-1" />
                                        Bayar DP
                                    </button>
                                </div>
                            </div>

                            <!-- Metode Pembayaran -->
                            <div class="space-y-2">
                                <Label for="payment_method">Metode Pembayaran</Label>
                                <select
                                    id="payment_method"
                                    v-model="formPayment.payment_method"
                                    class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                >
                                    <option value="cash">Tunai (Cash)</option>
                                    <option value="transfer">Transfer Bank</option>
                                    <option value="qris">QRIS</option>
                                </select>
                                <p v-if="formPayment.errors.payment_method" class="text-xs text-red-500">{{ formPayment.errors.payment_method }}</p>
                            </div>

                            <!-- Nominal -->
                            <div class="space-y-2">
                                <Label for="amount_paid">
                                    {{ formPayment.payment_type === 'lunas' ? 'Nominal Diterima' : 'Nominal DP' }}
                                </Label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 font-medium">Rp</span>
                                    <input
                                        id="amount_paid"
                                        v-model="formPayment.amount_paid"
                                        type="number"
                                        min="1"
                                        :placeholder="amountPlaceholder"
                                        class="flex h-10 w-full rounded-md border border-input bg-transparent pl-10 pr-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    />
                                </div>
                                <p v-if="formPayment.errors.amount_paid" class="text-xs text-red-500">{{ formPayment.errors.amount_paid }}</p>

                                <!-- Info sisa untuk mode lunas -->
                                <!-- <p v-if="formPayment.payment_type === 'lunas'" class="text-xs text-blue-600">
                                    Sisa tagihan: {{ formatRupiah(sisaTagihan) }}
                                </p> -->
                            </div>

                            <Button
                                type="submit"
                                :class="[
                                    'w-full',
                                    formPayment.payment_type === 'lunas'
                                        ? 'bg-blue-600 hover:bg-blue-700'
                                        : 'bg-yellow-500 hover:bg-yellow-600'
                                ]"
                                :disabled="formPayment.processing"
                            >
                                <CheckCircleIcon class="h-4 w-4 mr-2" />
                                {{ formPayment.payment_type === 'lunas' ? 'Konfirmasi Lunas' : 'Catat DP' }}
                            </Button>
                        </form>
                    </div>

                    <!-- Lunas Badge (tampil kalau sudah lunas) -->
                    <div v-else class="bg-green-50 border border-green-200 rounded-xl p-5 text-center">
                        <CheckCircleIcon class="h-8 w-8 text-green-500 mx-auto mb-2" />
                        <p class="font-bold text-green-800">Pembayaran Lunas</p>
                        <p class="text-sm text-green-600 mt-1">Transaksi ini sudah selesai dibayar.</p>
                    </div>

                    <!-- Update Status Pesanan -->
                    <div class="bg-white rounded-xl border shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Update Status Pesanan</h3>
                        <form @submit.prevent="updateStatus" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="status">Status Saat Ini</Label>
                                <select
                                    id="status"
                                    v-model="formStatus.status"
                                    class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                >
                                    <option v-for="(label, key) in status_options" :key="key" :value="key">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>
                            <Button
                                type="submit"
                                class="w-full bg-gray-700 hover:bg-gray-800"
                                :disabled="formStatus.processing || formStatus.status === transaction.status"
                            >
                                <CheckCircleIcon class="h-4 w-4 mr-2" /> Simpan Status
                            </Button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
