<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { PlusIcon, EyeIcon, PrinterIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { useFormatRupiah } from '@/composables/useFormatRupiah';

interface Transaction {
    id: number;
    transaction_number: string;
    customer_name: string;
    kasir_name: string;
    total: string | number;
    payment_method: string;
    status: string;
    status_label: string;
    created_at: string;
}

const props = defineProps<{
    transactions: {
        data: Transaction[];
        links: any[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search?: string;
        status?: string;
        date_from?: string;
        date_to?: string;
    };
}>();

const { formatRupiah } = useFormatRupiah();

// Filter States
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const dateFromFilter = ref(props.filters.date_from || '');
const dateToFilter = ref(props.filters.date_to || '');

// Auto-search dengan debounce 400ms
let searchTimeout: ReturnType<typeof setTimeout>;
const triggerSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('transactions.index'), {
            search: search.value,
            status: statusFilter.value,
            date_from: dateFromFilter.value,
            date_to: dateToFilter.value,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
};

watch([search, statusFilter, dateFromFilter, dateToFilter], triggerSearch);

// Mapping warna badge berdasarkan status pesanan
const getStatusColor = (status: string) => {
    switch (status) {
        case 'selesai':  return 'bg-green-100 text-green-800 border-green-200';
        case 'diambil':  return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'diproses': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'pending':
        default:         return 'bg-orange-100 text-orange-800 border-orange-200';
    }
};

// Pembayaran label mapping
const getPaymentLabel = (method: string) => {
    const map: Record<string, string> = {
        cash: 'Tunai',
        qris: 'QRIS',
        transfer: 'Transfer',
    };
    return map[method] ?? method.toUpperCase();
};

const downloadPdf = (id: number) => {
    window.open(route('transactions.pdf', id), '_blank');
};

// Navigasi paginasi
const prevPage = props.transactions.links[0];
const nextPage = props.transactions.links[props.transactions.links.length - 1];
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: route('dashboard') }, { title: 'Riwayat Transaksi', href: route('transactions.index') }]">
        <Head title="Riwayat Transaksi" />

        <div class="px-4 py-6 md:px-8 space-y-5 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Riwayat Transaksi</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Pantau semua transaksi pesanan beserta status pengerjaannya.</p>
                </div>
                <Link :href="route('transactions.create')">
                    <Button class="bg-blue-600 hover:bg-blue-700 shadow-sm">
                        <PlusIcon class="h-4 w-4 mr-2" /> Kasir POS Baru
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 bg-white p-4 rounded-xl border shadow-sm items-center">
                <div class="flex-1 min-w-[200px]">
                    <Input v-model="search" type="search" placeholder="Cari No. Transaksi (TRX-)..." />
                </div>
                <select
                    v-model="statusFilter"
                    class="h-9 w-[150px] rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                >
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="diproses">Diproses</option>
                    <option value="selesai">Selesai</option>
                    <option value="diambil">Diambil</option>
                </select>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 whitespace-nowrap">Dari:</span>
                    <Input type="date" v-model="dateFromFilter" class="w-[140px] h-9 text-sm" />
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 whitespace-nowrap">s/d:</span>
                    <Input type="date" v-model="dateToFilter" class="w-[140px] h-9 text-sm" />
                </div>
            </div>

            <!-- Summary Info -->
            <div v-if="transactions.total > 0" class="text-sm text-gray-500">
                Menampilkan <span class="font-semibold text-gray-800">{{ transactions.from }}–{{ transactions.to }}</span> dari <span class="font-semibold text-gray-800">{{ transactions.total }}</span> transaksi
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600 font-medium border-b border-gray-100 whitespace-nowrap">
                            <tr>
                                <th class="px-5 py-3">No. Transaksi</th>
                                <th class="px-5 py-3">Pelanggan</th>
                                <th class="px-5 py-3">Kasir</th>
                                <th class="px-5 py-3 text-right">Total Bayar</th>
                                <th class="px-5 py-3">Metode</th>
                                <th class="px-5 py-3 text-center">Status</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in transactions.data" :key="item.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-4">
                                    <p class="font-bold text-gray-900 font-mono text-xs">{{ item.transaction_number }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ item.created_at }}</p>
                                </td>
                                <td class="px-5 py-4 font-medium text-gray-800">{{ item.customer_name }}</td>
                                <td class="px-5 py-4 text-gray-500">{{ item.kasir_name }}</td>
                                <td class="px-5 py-4 text-right">
                                    <span class="font-semibold text-gray-900">{{ formatRupiah(item.total) }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-xs font-medium uppercase tracking-wide text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                        {{ getPaymentLabel(item.payment_method) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <Badge variant="outline" :class="getStatusColor(item.status)">
                                        {{ item.status_label }}
                                    </Badge>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button variant="outline" size="sm" class="h-8 shadow-sm text-gray-600" title="Cetak PDF" @click="downloadPdf(item.id)">
                                            <PrinterIcon class="h-3.5 w-3.5" />
                                        </Button>
                                        <Link :href="route('transactions.show', item.id)">
                                            <Button variant="outline" size="sm" class="h-8 shadow-sm text-blue-600 border-blue-200 hover:bg-blue-50">
                                                <EyeIcon class="h-3.5 w-3.5 mr-1" /> Detail
                                            </Button>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="transactions.data.length === 0">
                                <td colspan="7" class="px-6 py-16 text-center text-gray-400">
                                    <PrinterIcon class="h-10 w-10 mx-auto mb-3 text-gray-200" />
                                    <p class="font-medium text-gray-600">Tidak ada transaksi ditemukan.</p>
                                    <p class="text-sm mt-1">Coba sesuaikan filter pencarian di atas.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="flex justify-between items-center" v-if="transactions.last_page > 1">
                <span class="text-sm text-gray-400">Halaman {{ transactions.current_page }} / {{ transactions.last_page }}</span>
                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!prevPage?.url"
                        @click="prevPage?.url && router.get(prevPage.url)"
                    >
                        &larr; Sebelumnya
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!nextPage?.url"
                        @click="nextPage?.url && router.get(nextPage.url)"
                    >
                        Berikutnya &rarr;
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
