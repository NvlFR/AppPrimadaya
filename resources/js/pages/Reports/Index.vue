<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { 
    DownloadIcon, 
    TrendingUpIcon, 
    TrendingDownIcon,
    DollarSignIcon,
    CalendarCheckIcon,
    ShoppingCartIcon
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { useFormatRupiah } from '@/composables/useFormatRupiah';

const props = defineProps<{
    type: 'daily' | 'monthly';
    date: string;
    month: string;
    summary: {
        revenue: number;
        expenses: number;
        profit: number;
        transactions_count: number;
    };
    transactions: any[];
    expenses: any[];
}>();

const selectedType = ref(props.type);
const selectedDate = ref(props.date);
const selectedMonth = ref(props.month);

// Apply filters
const applyFilter = () => {
    router.get(route('reports.index'), {
        type: selectedType.value,
        date: selectedType.value === 'daily' ? selectedDate.value : undefined,
        month: selectedType.value === 'monthly' ? selectedMonth.value : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch(selectedType, () => {
    // If switching types, immediately fetch
    applyFilter();
});

const { formatRupiah } = useFormatRupiah();

// Arahkan ke endpoint export dengan parameter yang sedang aktif
const exportData = () => {
    const params = new URLSearchParams({
        type: selectedType.value,
        ...(selectedType.value === 'daily' ? { date: selectedDate.value } : { month: selectedMonth.value }),
    });
    window.open(route('reports.export') + '?' + params.toString(), '_blank');
};
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: route('dashboard') }, { title: 'Laporan Keuangan', href: route('reports.index') }]">
        <Head title="Laporan Keuangan" />

        <div class="px-4 py-6 md:px-8 space-y-6 max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Laporan Keuangan</h1>
                    <p class="text-sm text-gray-500">Analisa omzet, pengeluaran operasional, dan laba bersih cetak.</p>
                </div>
                <Button @click="exportData" variant="outline" class="border-blue-200 text-blue-700 hover:bg-blue-50">
                    <DownloadIcon class="h-4 w-4 mr-2" /> Export CSV
                </Button>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl border shadow-sm flex flex-col sm:flex-row gap-4 items-center w-fit">
                <div class="flex bg-gray-100 p-1 rounded-lg">
                    <button 
                        @click="selectedType = 'daily'" 
                        class="px-4 py-1.5 text-sm font-medium rounded-md transition-colors"
                        :class="selectedType === 'daily' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-900'"
                    >
                        Harian
                    </button>
                    <button 
                        @click="selectedType = 'monthly'" 
                        class="px-4 py-1.5 text-sm font-medium rounded-md transition-colors"
                        :class="selectedType === 'monthly' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-900'"
                    >
                        Bulanan
                    </button>
                </div>

                <div class="h-6 w-px bg-gray-200 hidden sm:block"></div>

                <div v-if="selectedType === 'daily'" class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500 font-medium">Pilih Tanggal:</span>
                    <Input type="date" v-model="selectedDate" @change="applyFilter" class="w-[150px] h-9 text-sm" />
                </div>

                <div v-if="selectedType === 'monthly'" class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500 font-medium">Bulan:</span>
                    <Input type="month" v-model="selectedMonth" @change="applyFilter" class="w-[150px] h-9 text-sm" />
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Omzet -->
                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between text-gray-500">
                        <span class="text-sm font-semibold">Total Pendapatan (Omzet)</span>
                        <div class="p-2 bg-green-50 text-green-600 rounded-md">
                            <TrendingUpIcon class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900">{{ formatRupiah(summary.revenue) }}</div>
                </div>

                <!-- Pengeluaran -->
                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between text-gray-500">
                        <span class="text-sm font-semibold">Total Pengeluaran</span>
                        <div class="p-2 bg-red-50 text-red-600 rounded-md">
                            <TrendingDownIcon class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900">{{ formatRupiah(summary.expenses) }}</div>
                </div>

                <!-- Laba -->
                <div class="bg-blue-600 border border-blue-700 rounded-xl p-5 shadow-sm space-y-2 text-white">
                    <div class="flex items-center justify-between text-blue-100">
                        <span class="text-sm font-semibold">Laba Bersih (Estimasi)</span>
                        <div class="p-2 bg-white/20 rounded-md">
                            <DollarSignIcon class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-white">{{ formatRupiah(summary.profit) }}</div>
                </div>

                <!-- TRX Count -->
                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between text-gray-500">
                        <span class="text-sm font-semibold">Jumlah Pesanan Valid</span>
                        <div class="p-2 bg-blue-50 text-blue-600 rounded-md">
                            <ShoppingCartIcon class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900">{{ summary.transactions_count }} <span class="text-sm font-normal text-gray-500">Trx</span></div>
                </div>
            </div>

            <!-- Details Table (Daily) -->
            <div v-if="selectedType === 'daily'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Pendapatan Harian -->
                <div class="bg-white border rounded-xl shadow-sm overflow-hidden flex flex-col">
                    <div class="px-5 py-4 border-b bg-gray-50 flex items-center justify-between font-semibold">
                        <span class="text-gray-900 flex items-center">Rincian Pendapatan</span>
                        <Badge variant="outline" class="bg-green-50 text-green-700 border-green-200">Pesanan Selesai</Badge>
                    </div>
                    <div class="overflow-x-auto p-0 flex-1">
                        <table class="w-full text-sm text-left">
                            <thead class="text-gray-500 bg-white border-b">
                                <tr>
                                    <th class="px-4 py-2 font-medium">Nota / Pelanggan</th>
                                    <th class="px-4 py-2 font-medium text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="trx in transactions" :key="trx.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900">{{ trx.transaction_number }}</div>
                                        <div class="text-xs text-gray-500">{{ trx.customer?.name || 'Umum' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-green-600">
                                        + {{ formatRupiah(trx.total) }}
                                    </td>
                                </tr>
                                <tr v-if="transactions.length === 0">
                                    <td colspan="2" class="px-4 py-8 text-center text-gray-500 text-xs">Belum ada pendapatan tervalidasi hari ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pengeluaran Harian -->
                <div class="bg-white border rounded-xl shadow-sm overflow-hidden flex flex-col">
                    <div class="px-5 py-4 border-b bg-gray-50 flex items-center justify-between font-semibold">
                        <span class="text-gray-900 flex items-center">Rincian Pengeluaran</span>
                        <Badge variant="outline" class="bg-red-50 text-red-700 border-red-200">Operasional</Badge>
                    </div>
                    <div class="overflow-x-auto p-0 flex-1">
                        <table class="w-full text-sm text-left">
                            <thead class="text-gray-500 bg-white border-b">
                                <tr>
                                    <th class="px-4 py-2 font-medium">Deskripsi / Kategori</th>
                                    <th class="px-4 py-2 font-medium text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="exp in expenses" :key="exp.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900">{{ exp.description }}</div>
                                        <div class="text-xs text-gray-500 uppercase">{{ exp.category }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-red-600">
                                        - {{ formatRupiah(exp.amount) }}
                                    </td>
                                </tr>
                                <tr v-if="expenses.length === 0">
                                    <td colspan="2" class="px-4 py-8 text-center text-gray-500 text-xs">Tidak ada catatan pengeluaran hari ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Details Table (Monthly) -->
            <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Pendapatan Bulanan (Grouped by Date) -->
                <div class="bg-white border rounded-xl shadow-sm overflow-hidden flex flex-col">
                    <div class="px-5 py-4 border-b bg-gray-50 flex items-center justify-between font-semibold">
                        <span class="text-gray-900 flex items-center">Rekap Harian (Pendapatan)</span>
                    </div>
                    <div class="overflow-x-auto p-0 flex-1">
                        <table class="w-full text-sm text-left">
                            <thead class="text-gray-500 bg-white border-b">
                                <tr>
                                    <th class="px-4 py-2 font-medium">Tanggal</th>
                                    <th class="px-4 py-2 font-medium text-center">Trx</th>
                                    <th class="px-4 py-2 font-medium text-right">Omzet</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="trx in transactions" :key="trx.date" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900 flex items-center">
                                        <CalendarCheckIcon class="h-3 w-3 mr-2 text-gray-400" />
                                        {{ trx.date }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-600">{{ trx.total_transactions }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-green-600">
                                        {{ formatRupiah(trx.daily_revenue) }}
                                    </td>
                                </tr>
                                <tr v-if="transactions.length === 0">
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-500 text-xs">Data transaksi bulan ini masih kosong.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pengeluaran Bulanan (Grouped by Date) -->
                <div class="bg-white border rounded-xl shadow-sm overflow-hidden flex flex-col">
                    <div class="px-5 py-4 border-b bg-gray-50 flex items-center justify-between font-semibold">
                        <span class="text-gray-900 flex items-center">Rekap Harian (Pengeluaran)</span>
                    </div>
                    <div class="overflow-x-auto p-0 flex-1">
                        <table class="w-full text-sm text-left">
                            <thead class="text-gray-500 bg-white border-b">
                                <tr>
                                    <th class="px-4 py-2 font-medium">Tanggal</th>
                                    <th class="px-4 py-2 font-medium text-right">Total Keluar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="exp in expenses" :key="exp.date" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900 flex items-center">
                                        <CalendarCheckIcon class="h-3 w-3 mr-2 text-gray-400" />
                                        {{ exp.date }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-red-600">
                                        {{ formatRupiah(exp.daily_expense) }}
                                    </td>
                                </tr>
                                <tr v-if="expenses.length === 0">
                                    <td colspan="2" class="px-4 py-8 text-center text-gray-500 text-xs">Belum ada rekap pengeluaran bulan ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
