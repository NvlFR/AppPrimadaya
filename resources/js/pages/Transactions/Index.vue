<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { 
    PlusIcon, 
    EyeIcon, 
    PrinterIcon 
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

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

// Filter States
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const dateFromFilter = ref(props.filters.date_from || '');
const dateToFilter = ref(props.filters.date_to || '');

// Automatic search
let searchTimeout: any;
const triggerSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('transactions.index'), { 
            search: search.value, 
            status: statusFilter.value,
            date_from: dateFromFilter.value,
            date_to: dateToFilter.value
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

watch([search, statusFilter, dateFromFilter, dateToFilter], triggerSearch);

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

const downloadPdf = (id: number) => {
    window.open(route('transactions.pdf', id), '_blank');
};
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: route('dashboard') }, { title: 'Riwayat Transaksi', href: route('transactions.index') }]">
        <Head title="Riwayat Transaksi" />

        <div class="px-4 py-6 md:px-8 space-y-6 max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Riwayat Transaksi</h1>
                    <p class="text-sm text-gray-500">Pantau semua transaksi pesanan yang masuk beserta log statusnya.</p>
                </div>
                <Link :href="route('transactions.create')">
                    <Button class="bg-blue-600 hover:bg-blue-700">
                        <PlusIcon class="h-4 w-4 mr-2" /> Kasir POS Baru
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-4 bg-white p-4 rounded-xl border shadow-sm items-center">
                <div class="flex-1 min-w-[200px]">
                    <Input v-model="search" type="search" placeholder="Cari No. Transaksi (TRX-)..." />
                </div>
                <select v-model="statusFilter" class="flex h-9 w-[150px] rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="diproses">Diproses</option>
                    <option value="selesai">Selesai</option>
                    <option value="diambil">Diambil</option>
                </select>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500">Dari:</span>
                    <Input type="date" v-model="dateFromFilter" class="w-[140px] h-9 text-sm" />
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500">Sampai:</span>
                    <Input type="date" v-model="dateToFilter" class="w-[140px] h-9 text-sm" />
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border shadow-sm overflow-hidden whitespace-nowrap overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600 font-medium border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-3">No. Transaksi / Waktu</th>
                            <th class="px-6 py-3">Pelanggan</th>
                            <th class="px-6 py-3">Kasir</th>
                            <th class="px-6 py-3">Total Bayar</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in transactions.data" :key="item.id" class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-900">{{ item.transaction_number }}</p>
                                <p class="text-xs text-gray-500">{{ item.created_at }}</p>
                            </td>
                            <td class="px-6 py-4 font-medium">{{ item.customer_name }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ item.kasir_name }}</td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-900">{{ formatRupiah(item.total) }}</p>
                                <p class="text-xs text-gray-500 uppercase">{{ item.payment_method }}</p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <Badge variant="outline" :class="getStatusColor(item.status)">
                                    {{ item.status_label }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <Button variant="outline" size="sm" class="h-8 shadow-sm text-gray-600" title="Cetak PDF" @click="downloadPdf(item.id)">
                                    <PrinterIcon class="h-3 w-3" />
                                </Button>
                                <Link :href="route('transactions.show', item.id)">
                                    <Button variant="outline" size="sm" class="h-8 shadow-sm text-blue-600 border-blue-200 hover:bg-blue-50">
                                        <EyeIcon class="h-3 w-3 mr-1" /> Buka
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="transactions.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <p class="font-medium">Tidak ada transaksi ditemukan.</p>
                                <p class="text-sm">Coba sesuaikan filter pencarian di atas.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-between items-center bg-white px-4 py-3 rounded-xl border shadow-sm" v-if="transactions.total > 0">
                <div class="text-sm text-gray-500">
                    Menampilkan <span class="font-medium text-gray-900">{{ transactions.from }}</span> - <span class="font-medium text-gray-900">{{ transactions.to }}</span> dari <span class="font-medium text-gray-900">{{ transactions.total }}</span> pesanan
                </div>
                <div class="flex space-x-2" v-if="transactions.last_page > 1">
                    <Button 
                        v-if="transactions.links[0].url" 
                        variant="outline" 
                        size="sm"
                        @click="router.get(transactions.links[0].url)" 
                        :disabled="!transactions.links[0].url"
                    >
                        Prev
                    </Button>
                    <Button 
                        v-if="transactions.links[transactions.links.length - 1].url" 
                        variant="outline" 
                        size="sm"
                        @click="router.get(transactions.links[transactions.links.length - 1].url)"
                        :disabled="!transactions.links[transactions.links.length - 1].url"
                    >
                        Next
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
