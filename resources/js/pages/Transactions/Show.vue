<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
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
    PaperclipIcon
} from 'lucide-vue-next';

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
}

interface Transaction {
    id: number;
    transaction_number: string;
    customer: { id: number; name: string; phone: string | null } | null;
    kasir_name: string;
    subtotal: string | number;
    discount_percent: string | number;
    discount_amount: string | number;
    total: string | number;
    payment_method: string;
    amount_paid: string | number;
    change_amount: string | number;
    status: string;
    status_label: string;
    notes: string | null;
    created_at: string;
    items: TransactionItem[];
}

const props = defineProps<{
    transaction: Transaction;
    status_options: Record<string, string>;
}>();

const formStatus = useForm({
    status: props.transaction.status,
});

const formatRupiah = (value: number | string) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(value));
};

const getStatusColor = (status: string) => {
    switch(status) {
        case 'selesai': return 'bg-green-100 text-green-800 border-green-200';
        case 'diambil': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'diproses': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'pending': default: return 'bg-orange-100 text-orange-800 border-orange-200';
    }
};

const updateStatus = () => {
    formStatus.patch(route('transactions.update-status', props.transaction.id), {
        preserveScroll: true,
    });
};

const downloadPdf = () => {
    window.open(route('transactions.pdf', props.transaction.id), '_blank');
};
</script>

<template>
    <AppLayout :breadcrumbs="[
        { title: 'Dashboard', href: route('dashboard') }, 
        { title: 'Transaksi', href: route('transactions.index') || '#' },
        { title: 'Detail Transaksi', href: route('transactions.show', transaction.id) }
    ]">
        <Head :title="`Invoice ${transaction.transaction_number}`" />

        <div class="px-4 py-6 md:px-8 space-y-6 max-w-5xl mx-auto">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                <div class="flex items-center space-x-4">
                    <Link :href="route('transactions.create')">
                        <Button variant="outline" size="icon" class="h-8 w-8 rounded-full">
                            <ArrowLeftIcon class="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                            {{ transaction.transaction_number }}
                            <Badge variant="outline" :class="getStatusColor(transaction.status)">
                                {{ transaction.status_label }}
                            </Badge>
                        </h1>
                        <p class="text-sm text-gray-500">{{ transaction.created_at }} &bull; Kasir: {{ transaction.kasir_name }}</p>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <Button variant="outline" @click="downloadPdf" class="border-blue-200 text-blue-700 hover:bg-blue-50">
                        <PrinterIcon class="h-4 w-4 mr-2" /> Cetak / Download PDF
                    </Button>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Left Column (Invoice Content) -->
                <div class="col-span-1 lg:col-span-2 space-y-6">
                    
                    <!-- Customer Info -->
                    <div class="bg-white rounded-xl border shadow-sm p-6">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4 border-b pb-2">Informasi Pelanggan</h3>
                        <div v-if="transaction.customer" class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Nama Pelanggan / Instansi</p>
                                <p class="font-medium text-gray-900">{{ transaction.customer.name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">No. Telepon / WhatsApp</p>
                                <p class="font-medium text-gray-900">{{ transaction.customer.phone || '-' }}</p>
                            </div>
                        </div>
                        <div v-else class="flex items-center text-gray-500 italic text-sm">
                            <AlertCircleIcon class="h-4 w-4 mr-2" /> Pelanggan Umum (Tanpa Data)
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                            <h3 class="font-semibold text-gray-900">Detail Pesanan</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-white text-gray-500 border-b">
                                    <tr>
                                        <th class="px-6 py-3 font-medium">Layanan & Keterangan</th>
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
                                <h4 class="text-sm font-semibold text-amber-800">Catatan Kasir</h4>
                                <p class="text-sm text-amber-700 mt-1">{{ transaction.notes }}</p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column (Billing & Actions) -->
                <div class="col-span-1 space-y-6">
                    
                    <!-- Billing Summary -->
                    <div class="bg-white rounded-xl border shadow-sm p-6 space-y-4">
                        <h3 class="font-semibold text-gray-900 border-b pb-4">Ringkasan Pembayaran</h3>
                        
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

                        <div class="bg-gray-50 rounded-lg p-4 space-y-2 mt-4 text-sm border">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Metode Pembayaran</span>
                                <span class="font-semibold uppercase">{{ transaction.payment_method }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Total Dibayar</span>
                                <span class="font-medium text-gray-900">{{ formatRupiah(transaction.amount_paid) }}</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-gray-200">
                                <span class="text-gray-500">Kembalian</span>
                                <span class="font-semibold text-green-600">{{ formatRupiah(transaction.change_amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Update Status Workflow -->
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
                                class="w-full bg-blue-600 hover:bg-blue-700" 
                                :disabled="formStatus.processing || formStatus.status === transaction.status"
                            >
                                <CheckCircleIcon class="h-4 w-4 mr-2" /> Simpan Status Baru
                            </Button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
