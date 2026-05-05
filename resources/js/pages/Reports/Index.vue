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
    ShoppingCartIcon,
    FileBarChart,
    WalletIcon,
    HistoryIcon,
    Activity
} from 'lucide-vue-next';
import { computed, ref, watch, defineAsyncComponent } from 'vue';
import { useFormatRupiah } from '@/composables/useFormatRupiah';

const VueApexCharts = defineAsyncComponent(() => import('vue3-apexcharts'));

const props = defineProps<{
    type: 'daily' | 'monthly';
    date: string;
    month: string;
    summary: {
        revenue: number;
        paid: number;
        unpaid: number;
        expenses: number;
        profit: number;
        transactions_count: number;
        status_counts?: Record<string, number>;
        growth: {
            revenue: number;
            transactions: number;
        };
    };
    transactions: any[];
    expenses: any[];
    top_services: any[];
    payment_methods: any[];
    hourly_data?: any[];
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

const profitMargin = computed(() => {
    if (props.summary.revenue === 0) return 0;
    return (props.summary.profit / props.summary.revenue) * 100;
});

// Chart Data for Monthly View
const chartOptions = computed(() => ({
    chart: {
        type: 'area' as const,
        fontFamily: 'inherit',
        toolbar: { show: false },
        zoom: { enabled: false }
    },
    colors: ['#2563EB', '#10B981', '#EF4444'], // Blue for Revenue, Emerald for Paid, Red for Expenses
    stroke: { curve: 'smooth' as const, width: 2 },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.3,
            opacityTo: 0.05,
            stops: [0, 90, 100]
        }
    },
    dataLabels: { enabled: false },
    grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
    xaxis: {
        categories: sortedTransactions.value.map(t => {
            const d = new Date(t.date);
            return d.getDate().toString();
        }),
        labels: { style: { colors: '#64748b', fontSize: '10px' } }
    },
    yaxis: {
        labels: {
            formatter: (val: number) => `Rp ${val / 1000}k`,
            style: { colors: '#64748b', fontSize: '10px' }
        }
    },
    legend: { position: 'top' as const, horizontalAlign: 'right' as const },
    tooltip: { y: { formatter: (val: number) => formatRupiah(val) } }
}));

// Hourly Chart Data (Daily)
const hourlyChartOptions = computed(() => ({
    chart: {
        type: 'bar' as const,
        fontFamily: 'inherit',
        toolbar: { show: false }
    },
    colors: ['#6366F1'],
    plotOptions: {
        bar: {
            borderRadius: 4,
            columnWidth: '60%',
        }
    },
    dataLabels: { enabled: false },
    xaxis: {
        categories: Array.from({ length: 24 }, (_, i) => `${i}:00`),
        labels: { style: { colors: '#64748b', fontSize: '10px' } }
    },
    yaxis: {
        show: false
    },
    grid: { show: false },
    tooltip: {
        y: {
            formatter: (val: number) => `${val} Pesanan`
        }
    }
}));

const hourlySeries = computed(() => {
    const data = Array(24).fill(0);
    props.hourly_data?.forEach(h => {
        data[h.hour] = h.count;
    });
    return [{ name: 'Pesanan', data }];
});

// Reactive sorted data for chart
const sortedTransactions = computed(() => [...props.transactions].sort((a, b) => a.date.localeCompare(b.date)));
const sortedExpenses = computed(() => [...props.expenses].sort((a, b) => a.date.localeCompare(b.date)));

const chartSeries = computed(() => {
    if (props.type !== 'monthly') return [];
    
    // Map expenses to dates for easy lookup
    const expenseMap = new Map(sortedExpenses.value.map(e => [e.date, e.daily_expense]));

    return [
        {
            name: 'Total Omzet',
            data: sortedTransactions.value.map(t => Number(t.daily_revenue))
        },
        {
            name: 'Uang Masuk (Lunas)',
            data: sortedTransactions.value.map(t => Number(t.daily_paid))
        },
        {
            name: 'Pengeluaran',
            data: sortedTransactions.value.map(t => expenseMap.get(t.date) ? Number(expenseMap.get(t.date)) : 0)
        }
    ];
});

// Chart Data for Payment Methods (Donut)
const paymentChartOptions = computed(() => ({
    chart: { type: 'donut' as const, fontFamily: 'inherit' },
    labels: props.payment_methods.map(p => p.method.toUpperCase()),
    colors: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6'],
    plotOptions: {
        pie: {
            donut: {
                size: '70%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total',
                        formatter: () => formatRupiah(props.summary.revenue)
                    }
                }
            }
        }
    },
    dataLabels: { enabled: false },
    legend: { position: 'bottom' as const },
    stroke: { width: 0 }
}));

const paymentSeries = computed(() => props.payment_methods.map(p => Number(p.revenue)));

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

        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2"><FileBarChart class="h-6 w-6 text-primary"/>Laporan Keuangan</h1>
                    <!-- <p class="text-sm text-gray-500">Analisa omzet, pengeluaran operasional, dan laba bersih cetak.</p> -->
                </div>
                <Button @click="exportData" variant="outline" class="border-blue-200 text-blue-700 hover:bg-blue-50">
                    <DownloadIcon class="h-4 w-4 mr-2" /> Export CSV
                </Button>
            </div>

            <!-- Filters -->
            <div class="bg-white p-3 rounded-2xl border border-gray-100 shadow-sm flex w-full flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex bg-gray-100 p-1 rounded-xl">
                        <button 
                            @click="selectedType = 'daily'" 
                            class="px-5 py-1.5 text-xs font-bold rounded-lg transition-all"
                            :class="selectedType === 'daily' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500 hover:text-gray-900'"
                        >
                            Harian
                        </button>
                        <button 
                            @click="selectedType = 'monthly'" 
                            class="px-5 py-1.5 text-xs font-bold rounded-lg transition-all"
                            :class="selectedType === 'monthly' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500 hover:text-gray-900'"
                        >
                            Bulanan
                        </button>
                    </div>

                    <div class="h-6 w-px bg-gray-200 hidden sm:block"></div>

                    <div v-if="selectedType === 'daily'" class="flex items-center space-x-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tanggal:</span>
                        <Input type="date" v-model="selectedDate" @change="applyFilter" class="h-9 w-[160px] text-xs border-gray-100 rounded-xl bg-gray-50/50" />
                    </div>

                    <div v-if="selectedType === 'monthly'" class="flex items-center space-x-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Bulan:</span>
                        <Input type="month" v-model="selectedMonth" @change="applyFilter" class="h-9 w-[160px] text-xs border-gray-100 rounded-xl bg-gray-50/50" />
                    </div>
                </div>

                <div class="flex items-center gap-2 text-[10px] font-bold text-gray-400">
                    <span class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span>
                    Live Reporting Data
                </div>
            </div>

            <!-- Summary Cards Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between px-1">
                    <div class="flex items-center gap-2">
                        <DollarSignIcon class="h-4 w-4 text-gray-400" />
                        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest">Ringkasan Finansial</h2>
                    </div>
                    <div v-if="summary.growth" class="text-[10px] font-bold text-gray-400">
                        vs {{ selectedType === 'daily' ? 'Kemarin' : 'Bulan Lalu' }}
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Omzet -->
                <div class="bg-white border-0 shadow-sm rounded-2xl p-5 group hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:scale-110 transition-transform">
                        <TrendingUpIcon class="h-12 w-12 text-blue-600" />
                    </div>
                    <div class="relative z-10 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Omzet</span>
                            <div class="p-1.5 bg-blue-50 text-blue-600 rounded-lg"><TrendingUpIcon class="h-3.5 w-3.5" /></div>
                        </div>
                        <div class="text-2xl font-black text-gray-900">{{ formatRupiah(summary.revenue) }}</div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5 text-[9px] font-bold text-blue-600 bg-blue-50 w-fit px-2 py-0.5 rounded-full">
                                {{ summary.transactions_count }} Pesanan
                            </div>
                            <div v-if="summary.growth.revenue !== 0" 
                                class="flex items-center gap-0.5 text-[10px] font-bold"
                                :class="summary.growth.revenue > 0 ? 'text-emerald-600' : 'text-red-600'"
                            >
                                <TrendingUpIcon v-if="summary.growth.revenue > 0" class="h-3 w-3" />
                                <TrendingDownIcon v-else class="h-3 w-3" />
                                {{ Math.abs(summary.growth.revenue).toFixed(1) }}%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lunas -->
                <div class="bg-white border-0 shadow-sm rounded-2xl p-5 group hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:scale-110 transition-transform text-emerald-600">
                        <WalletIcon class="h-12 w-12" />
                    </div>
                    <div class="relative z-10 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Lunas</span>
                            <div class="p-1.5 bg-emerald-50 text-emerald-600 rounded-lg"><WalletIcon class="h-3.5 w-3.5" /></div>
                        </div>
                        <div class="text-2xl font-black text-emerald-600">{{ formatRupiah(summary.paid) }}</div>
                        <div class="text-[9px] font-bold text-emerald-500 italic">Uang sudah ditangan</div>
                    </div>
                </div>

                <!-- Piutang -->
                <div class="bg-white border-0 shadow-sm rounded-2xl p-5 group hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:scale-110 transition-transform text-amber-600">
                        <HistoryIcon class="h-12 w-12" />
                    </div>
                    <div class="relative z-10 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Piutang Pelanggan</span>
                            <div class="p-1.5 bg-amber-50 text-amber-600 rounded-lg"><HistoryIcon class="h-3.5 w-3.5" /></div>
                        </div>
                        <div class="text-2xl font-black text-amber-600">{{ formatRupiah(summary.unpaid) }}</div>
                        <div class="text-[9px] font-bold text-amber-500 italic">Belum dibayar</div>
                    </div>
                </div>

                <!-- Pengeluaran -->
                <div class="bg-white border-0 shadow-sm rounded-2xl p-5 group hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:scale-110 transition-transform text-red-600">
                        <TrendingDownIcon class="h-12 w-12" />
                    </div>
                    <div class="relative z-10 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Pengeluaran</span>
                            <div class="p-1.5 bg-red-50 text-red-600 rounded-lg"><TrendingDownIcon class="h-3.5 w-3.5" /></div>
                        </div>
                        <div class="text-2xl font-black text-gray-900">{{ formatRupiah(summary.expenses) }}</div>
                        <div class="text-[9px] font-bold text-red-500 italic">Biaya Operasional</div>
                    </div>
                </div>

                <!-- Laba -->
                <div class="bg-gradient-to-br from-indigo-600 to-blue-700 border-0 shadow-lg rounded-2xl p-5 group hover:scale-[1.02] transition-all duration-300 text-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <DollarSignIcon class="h-16 w-16" />
                    </div>
                    <div class="relative z-10 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold opacity-80 uppercase tracking-widest">Estimasi Laba Bersih</span>
                            <div class="p-1.5 bg-white/20 rounded-lg backdrop-blur-md"><DollarSignIcon class="h-3.5 w-3.5" /></div>
                        </div>
                        <div class="text-2xl font-black">{{ formatRupiah(summary.profit) }}</div>
                        <div class="flex items-center gap-1.5 text-[9px] font-bold text-white w-fit px-2 py-0.5 rounded-full backdrop-blur-md"
                            :class="{
                                'bg-emerald-500/30': profitMargin >= 25,
                                'bg-amber-500/30': profitMargin >= 10 && profitMargin < 25,
                                'bg-red-500/30': profitMargin < 10
                            }">
                            <Activity class="h-3 w-3" />
                            Margin: {{ profitMargin.toFixed(1) }}%
                        </div>
                    </div>
                </div>
            </div>
            </div>
            
            <!-- Operational Status Breakdown -->
            <div v-if="summary.status_counts" class="space-y-3">
                <div class="flex items-center gap-2">
                    <Activity class="h-4 w-4 text-gray-400" />
                    <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest">Status Operasional {{ selectedType === 'daily' ? 'Hari Ini' : 'Bulan Ini' }}</h2>
                </div>
                <div class="flex flex-wrap gap-3">
                    <div class="px-4 py-2.5 bg-white border border-gray-100 rounded-2xl flex items-center gap-3 shadow-sm group hover:border-blue-200 transition-all duration-300">
                        <div class="w-2 h-2 rounded-full bg-blue-500 group-hover:animate-ping"></div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter group-hover:text-blue-600">Diproses:</span>
                        <span class="text-xs font-black text-blue-600">{{ summary.status_counts['diproses'] || 0 }}</span>
                    </div>
                    <div class="px-4 py-2.5 bg-white border border-gray-100 rounded-2xl flex items-center gap-3 shadow-sm group hover:border-emerald-200 transition-all duration-300">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 group-hover:animate-pulse"></div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter group-hover:text-emerald-600">Siap Ambil:</span>
                        <span class="text-xs font-black text-emerald-600">{{ summary.status_counts['selesai'] || 0 }}</span>
                    </div>
                    <div class="px-4 py-2.5 bg-white border border-gray-100 rounded-2xl flex items-center gap-3 shadow-sm group hover:border-gray-300 transition-all duration-300">
                        <div class="w-2 h-2 rounded-full bg-gray-400"></div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter group-hover:text-gray-600">Diambil:</span>
                        <span class="text-xs font-black text-gray-600">{{ summary.status_counts['diambil'] || 0 }}</span>
                    </div>
                </div>
            </div>

            <!-- Visual Trend (Only for Monthly) -->
            <div v-if="selectedType === 'monthly'" class="bg-white border border-gray-100 shadow-xl shadow-gray-100/50 rounded-[2rem] overflow-hidden p-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                    <div>
                        <h3 class="text-base font-black text-gray-900">Grafik Performa Harian</h3>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Komparasi Metrik Utama Bisnis</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-1.5">
                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            <span class="text-[10px] font-bold text-gray-600">Total Omzet</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-3 h-3 bg-emerald-500 rounded-full"></div>
                            <span class="text-[10px] font-bold text-gray-600">Uang Masuk</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                            <span class="text-[10px] font-bold text-gray-600">Pengeluaran</span>
                        </div>
                    </div>
                </div>
                <div class="h-[300px] w-full">
                    <VueApexCharts :options="chartOptions" :series="chartSeries" height="300" />
                </div>
            </div>

            <!-- Hourly Analysis (Only for Daily) -->
            <div v-if="selectedType === 'daily'" class="bg-white border border-gray-100 shadow-sm rounded-[2rem] p-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">Peak Hour Analysis</h3>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Kepadatan Pesanan per Jam</p>
                    </div>
                    <div class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full text-[10px] font-black uppercase">
                        {{ hourlySeries[0].data.reduce((a, b) => a + b, 0) }} Total Transaksi
                    </div>
                </div>
                <div class="h-[120px] w-full">
                    <VueApexCharts :options="hourlyChartOptions" :series="hourlySeries" height="120" type="bar" />
                </div>
            </div>

            <!-- Analytics Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Payment Methods Donut -->
                <div class="bg-white border border-gray-100 shadow-sm rounded-[2rem] p-8 flex flex-col h-full">
                    <div class="mb-8">
                        <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">Metode Pembayaran</h3>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Distribusi nominal</p>
                    </div>
                    <div class="flex-1 flex items-center justify-center">
                        <div v-if="payment_methods.length > 0" class="w-full max-w-[250px]">
                            <VueApexCharts :options="paymentChartOptions" :series="paymentSeries" type="donut" />
                        </div>
                        <div v-else class="text-center py-10 text-xs text-gray-400 italic">Data belum tersedia</div>
                    </div>
                </div>

                <!-- Top Services Leaderboard -->
                <div class="lg:col-span-2 bg-white border border-gray-100 shadow-sm rounded-[2rem] p-8 h-full flex flex-col">
                    <div class="mb-8 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">Layanan Terlaris</h3>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Berdasarkan total pendapatan</p>
                        </div>
                        <div class="p-2 bg-indigo-50 text-indigo-600 rounded-xl"><Activity class="h-4 w-4" /></div>
                    </div>
                    
                    <div class="flex-1 space-y-6">
                        <div v-for="(svc, index) in top_services" :key="`top-svc-${index}`" class="flex items-center gap-5 group">
                            <div class="flex-none w-10 h-10 rounded-2xl bg-gray-50 flex items-center justify-center text-xs font-black text-gray-400 group-hover:bg-indigo-600 group-hover:text-white group-hover:scale-110 transition-all duration-300">
                                {{ index + 1 }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold text-gray-800 truncate">{{ svc.service_name }}</span>
                                    <span class="text-xs font-black text-indigo-600">{{ formatRupiah(svc.total_revenue) }}</span>
                                </div>
                                <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full bg-indigo-500 transition-all duration-1000" 
                                        :style="{ width: `${(svc.total_revenue / top_services[0].total_revenue) * 100}%` }"
                                    ></div>
                                </div>
                                <div class="flex justify-between mt-1">
                                    <span class="text-[9px] text-gray-400 font-medium">{{ svc.total_qty }} Pesanan</span>
                                    <span class="text-[9px] text-indigo-400 font-bold">{{ ((svc.total_revenue / summary.revenue) * 100).toFixed(1) }}% kontribusi</span>
                                </div>
                            </div>
                        </div>
                        <div v-if="top_services.length === 0" class="text-center py-20 text-xs text-gray-400 italic">Belum ada data layanan untuk periode ini.</div>
                    </div>
                </div>
            </div>

            <!-- Business Insights -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-indigo-900 text-white rounded-[2rem] p-8 relative overflow-hidden shadow-xl shadow-indigo-100">
                    <div class="absolute top-0 right-0 p-6 opacity-10">
                        <TrendingUpIcon class="h-24 w-24" />
                    </div>
                    <div class="relative z-10 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-white/20 rounded-xl backdrop-blur-md">
                                <Activity class="h-5 w-5 text-white" />
                            </div>
                            <h3 class="text-lg font-black uppercase tracking-wider">Insight Performa</h3>
                        </div>
                        <p class="text-indigo-100 text-sm font-medium leading-relaxed">
                            <template v-if="profitMargin >= 30">
                                Performa bisnis sangat luar biasa! Margin keuntungan Anda mencapai <b>{{ profitMargin.toFixed(1) }}%</b>. 
                                <span v-if="summary.growth.revenue > 0">Ditambah dengan pertumbuhan omzet sebesar <b>{{ summary.growth.revenue.toFixed(1) }}%</b>, bisnis Anda sedang dalam fase ekspansi yang sehat.</span>
                                <span v-else-if="summary.growth.revenue < 0">Meskipun omzet turun <b>{{ Math.abs(summary.growth.revenue).toFixed(1) }}%</b>, efisiensi Anda tetap menjaga margin tetap tinggi.</span>
                                Pertahankan strategi ini!
                            </template>
                            <template v-else-if="profitMargin >= 15">
                                Performa bisnis stabil. Margin keuntungan <b>{{ profitMargin.toFixed(1) }}%</b> berada di rata-rata industri. 
                                <span v-if="summary.growth.revenue > 0">Pertumbuhan omzet <b>{{ summary.growth.revenue.toFixed(1) }}%</b> menunjukkan minat pelanggan yang meningkat.</span>
                                <span v-else-if="summary.growth.revenue < 0">Waspada penurunan omzet <b>{{ Math.abs(summary.growth.revenue).toFixed(1) }}%</b>, coba evaluasi strategi pemasaran Anda.</span>
                                Anda bisa mencoba meningkatkan volume transaksi untuk memaksimalkan laba.
                            </template>
                            <template v-else>
                                Perhatian diperlukan. Margin keuntungan Anda (<b>{{ profitMargin.toFixed(1) }}%</b>) cukup rendah. 
                                <span v-if="summary.growth.revenue < 0">Penurunan omzet <b>{{ Math.abs(summary.growth.revenue).toFixed(1) }}%</b> bersamaan dengan margin rendah mengindikasikan perlunya efisiensi biaya mendesak.</span>
                                Cek kembali biaya pengeluaran operasional atau pertimbangkan penyesuaian harga layanan.
                            </template>
                        </p>
                    </div>
                </div>

                <div class="bg-white border border-gray-100 rounded-[2rem] p-8 shadow-sm group hover:shadow-md transition-all">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-amber-50 rounded-xl">
                                <WalletIcon class="h-5 w-5 text-amber-600" />
                            </div>
                            <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest">Kesehatan Arus Kas</h3>
                        </div>
                        <div class="space-y-4">
                            <div class="flex justify-between items-end">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-tighter">Rasio Pembayaran Lunas</span>
                                <span class="text-sm font-black text-emerald-600">{{ ((summary.paid / summary.revenue) * 100 || 0).toFixed(1) }}%</span>
                            </div>
                            <div class="w-full h-3 bg-gray-50 rounded-full overflow-hidden border border-gray-100">
                                <div 
                                    class="h-full bg-emerald-500 rounded-full transition-all duration-1000 shadow-[0_0_10px_rgba(16,185,129,0.3)]"
                                    :style="{ width: `${(summary.paid / summary.revenue) * 100 || 0}%` }"
                                ></div>
                            </div>
                            <p class="text-[10px] text-gray-500 font-medium leading-relaxed italic">
                                <span v-if="summary.unpaid > 0" class="text-amber-600 font-bold">
                                    Terdapat {{ formatRupiah(summary.unpaid) }} piutang yang belum tertagih. 
                                    Pastikan pelanggan melakukan pelunasan saat pengambilan pesanan.
                                </span>
                                <span v-else class="text-emerald-600 font-bold">
                                    Selamat! Seluruh transaksi telah terbayar lunas. Arus kas Anda sangat aman.
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details Section -->
            <div class="space-y-6">
                <!-- Daily Details -->
                <template v-if="selectedType === 'daily'">
                    <div class="grid grid-cols-1 gap-6">
                        <!-- Transactions Table -->
                        <div class="bg-white border border-gray-100 shadow-sm rounded-2xl overflow-hidden">
                            <div class="px-6 py-4 border-b bg-gray-50 flex items-center justify-between">
                                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-widest flex items-center gap-2">
                                    <ShoppingCartIcon class="h-4 w-4 text-blue-500" />
                                    Daftar Transaksi Hari Ini
                                </h3>
                                <Badge variant="outline" class="bg-blue-50 text-blue-700 border-blue-200 text-[10px] font-bold uppercase">Valid Orders</Badge>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-white border-b border-gray-100">
                                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">No. Nota</th>
                                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Pelanggan</th>
                                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Jam</th>
                                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-center">Status</th>
                                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="trx in transactions" :key="`trx-${trx.id}`" class="hover:bg-gray-50/80 transition-all group">
                                            <td class="px-6 py-5 text-xs font-black text-blue-600 group-hover:translate-x-1 transition-transform inline-block mt-4">{{ trx.transaction_number }}</td>
                                            <td class="px-6 py-5">
                                                <div class="text-xs font-bold text-gray-900">{{ trx.customer?.name || 'Pelanggan Umum' }}</div>
                                            </td>
                                            <td class="px-6 py-5 text-[10px] font-black text-gray-400 tracking-tighter">{{ trx.created_at }}</td>
                                            <td class="px-6 py-5">
                                                <div class="flex flex-col gap-1 items-center">
                                                    <Badge 
                                                        variant="outline" 
                                                        class="text-[8px] px-2 py-0 font-black uppercase border-0"
                                                        :class="{
                                                            'bg-emerald-100 text-emerald-700': trx.payment_status === 'lunas',
                                                            'bg-amber-100 text-amber-700': trx.payment_status === 'dp',
                                                            'bg-red-100 text-red-700': trx.payment_status === 'belum_bayar',
                                                        }"
                                                    >
                                                        {{ trx.payment_status.replace('_', ' ') }}
                                                    </Badge>
                                                    <span class="text-[8px] font-bold text-gray-400 uppercase tracking-widest">{{ trx.status }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5 text-sm font-black text-gray-900 text-right">{{ formatRupiah(trx.total) }}</td>
                                        </tr>
                                        <tr v-if="transactions.length === 0">
                                            <td colspan="5" class="px-6 py-10 text-center text-xs text-gray-400 italic">Belum ada transaksi tervalidasi hari ini.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Expenses Table -->
                        <div class="bg-white border border-gray-100 shadow-sm rounded-2xl overflow-hidden">
                            <div class="px-6 py-4 border-b bg-gray-50 flex items-center justify-between">
                                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-widest flex items-center gap-2">
                                    <TrendingDownIcon class="h-4 w-4 text-red-500" />
                                    Pengeluaran Operasional
                                </h3>
                                <Badge variant="outline" class="bg-red-50 text-red-700 border-red-200 text-[10px] font-bold uppercase">Operational Cost</Badge>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-white border-b border-gray-100">
                                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Kategori</th>
                                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Deskripsi</th>
                                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="exp in expenses" :key="`exp-${exp.id}`" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-5">
                                                <Badge variant="secondary" class="bg-red-50 text-red-600 border-0 text-[8px] font-black uppercase tracking-widest">{{ exp.category }}</Badge>
                                            </td>
                                            <td class="px-6 py-5 text-xs font-bold text-gray-900">{{ exp.description }}</td>
                                            <td class="px-6 py-5 text-sm font-black text-red-600 text-right">- {{ formatRupiah(exp.amount) }}</td>
                                        </tr>
                                        <tr v-if="expenses.length === 0">
                                            <td colspan="3" class="px-6 py-10 text-center text-xs text-gray-400 italic">Tidak ada catatan pengeluaran hari ini.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Monthly Details -->
                <template v-else>
                    <div class="grid grid-cols-1 gap-6">
                        <div class="bg-white border border-gray-100 shadow-sm rounded-2xl overflow-hidden">
                            <div class="px-6 py-4 border-b bg-gray-50 flex items-center justify-between">
                                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-widest flex items-center gap-2">
                                    <CalendarCheckIcon class="h-4 w-4 text-blue-500" />
                                    Rekapitulasi Harian
                                </h3>
                                <Badge variant="outline" class="bg-blue-50 text-blue-700 border-blue-200 text-[10px] font-bold uppercase">{{ month }}</Badge>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-white border-b border-gray-100">
                                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Tanggal</th>
                                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-center">Qty Pesanan</th>
                                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Total Omzet</th>
                                            <th class="px-6 py-4 text-[10px] font-black text-emerald-600 uppercase tracking-[0.2em] text-right">Uang Masuk</th>
                                            <th class="px-6 py-4 text-[10px] font-black text-amber-600 uppercase tracking-[0.2em] text-right">Piutang</th>
                                            <th class="px-6 py-4 text-[10px] font-black text-red-600 uppercase tracking-[0.2em] text-right">Pengeluaran</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="row in sortedTransactions" :key="`month-recap-${row.date}`" class="hover:bg-gray-50 transition-all group">
                                            <td class="px-6 py-5 text-xs font-black text-gray-900">{{ row.date }}</td>
                                            <td class="px-6 py-5 text-xs text-gray-500 text-center font-bold">{{ row.total_transactions }}</td>
                                            <td class="px-6 py-5 text-sm font-black text-gray-900 text-right group-hover:scale-105 transition-transform">{{ formatRupiah(row.daily_revenue) }}</td>
                                            <td class="px-6 py-5 text-xs font-bold text-emerald-600 text-right">{{ formatRupiah(row.daily_paid) }}</td>
                                            <td class="px-6 py-5 text-xs font-bold text-amber-600 text-right">{{ formatRupiah(row.daily_unpaid) }}</td>
                                            <td class="px-6 py-5 text-xs font-bold text-red-600 text-right">
                                                {{ expenses.find(e => e.date === row.date) ? formatRupiah(expenses.find(e => e.date === row.date).daily_expense) : '-' }}
                                            </td>
                                        </tr>
                                        <tr v-if="sortedTransactions.length === 0">
                                            <td colspan="6" class="px-6 py-10 text-center text-xs text-gray-400 italic">Belum ada data tervalidasi di bulan ini.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

        </div>
    </AppLayout>
</template>
