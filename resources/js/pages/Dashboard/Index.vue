<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { 
    Wallet, CreditCard, Clock, Activity, TrendingUp, TrendingDown, 
    PieChart, AlertTriangle, PlusIcon, CheckCircle, CheckCircle2, 
    CircleDollarSign, AlertCircle, Users, ShoppingBag, ArrowRight,
    ArrowUpRight, ArrowDownRight, ReceiptText
} from 'lucide-vue-next';
import { computed, ref, defineAsyncComponent } from 'vue';

const VueApexCharts = defineAsyncComponent(() => import('vue3-apexcharts'));
import { useFormatRupiah } from '@/composables/useFormatRupiah';

const props = defineProps<{
    paid_transactions: Array<any>;
    unpaid_transactions: Array<any>;
    monthly_paid_transactions: Array<any>;
    monthly_unpaid_transactions: Array<any>;
    stats: {
        today_revenue: number;
        today_transactions: number;
        today_paid: number;
        today_unpaid: number;
        monthly_paid: number;
        monthly_unpaid: number;
        monthly_revenue: number;
        monthly_expenses: number;
        pending_orders: number;
        net_profit: number;
        revenue_growth: number;
    };
    sales_chart: Array<{
        date: string;
        label: string;
        revenue: number;
        count: number;
    }>;
    payment_methods: Array<any>;
    top_services: Array<any>;
    category_sales: Array<{
        label: string;
        category: string;
        revenue: number;
    }>;
    low_stock_alerts: Array<{
        id: number;
        name: string;
        current_qty: number;
        min_qty: number;
        unit: string;
    }>;
    recent_transactions: Array<{
        id: number;
        transaction_number: string;
        customer_name: string;
        total: number;
        status: string;
        status_label: string;
        created_at: string;
    }>;
    active_orders: Array<{
        id: number;
        transaction_number: string;
        customer_name: string;
        status: string;
        status_label: string;
        created_at: string;
    }>;
    recent_expenses: Array<{
        id: number;
        description: string;
        amount: number;
        expense_date: string;
        category: string;
    }>;
    top_customers: Array<{
        name: string;
        total_spent: number;
        transaction_count: number;
    }>;
}>();

const { formatRupiah } = useFormatRupiah();

const getStatusColor = (status: string) => {
    switch (status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'diproses': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'selesai': return 'bg-green-100 text-green-800 border-green-200';
        case 'diambil': return 'bg-gray-100 text-gray-800 border-gray-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const revenueGrowthPositive = computed(() => props.stats.revenue_growth >= 0);
const revenueGrowthIcon = computed(() => revenueGrowthPositive.value ? TrendingUp : TrendingDown);
const revenueGrowthColor = computed(() => revenueGrowthPositive.value ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-red-700 bg-red-50 border-red-200');
const netProfitPositive = computed(() => props.stats.net_profit >= 0);
const netProfitTone = computed(() => netProfitPositive.value ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-red-700 bg-red-50 border-red-200');
const netProfitIcon = computed(() => netProfitPositive.value ? TrendingUp : AlertTriangle);

// Setup untuk chart grafik omzet 7 hari
const chartOptions = computed(() => ({
    chart: {
        type: 'area' as const,
        fontFamily: 'inherit',
        height: 300,
        toolbar: { show: false },
        zoom: { enabled: false },
    },
    colors: ['#2563EB'],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth' as const, width: 2 },
    grid: {
        borderColor: '#e5e7eb',
        strokeDashArray: 4,
    },
    xaxis: {
        categories: props.sales_chart.map(item => item.label),
        labels: { style: { colors: '#64748b' } }
    },
    yaxis: {
        labels: {
            formatter: (value: number) => `Rp ${value / 1000}k`,
            style: { colors: '#64748b' }
        }
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.05,
            stops: [0, 90, 100]
        }
    },
    tooltip: {
        y: { formatter: (value: number) => formatRupiah(value) }
    }
}));

const chartSeries = computed(() => [{
    name: 'Penjualan',
    data: props.sales_chart.map(item => item.revenue)
}]);

const categoryChartOptions = computed(() => ({
    chart: {
        type: 'pie' as const,
        fontFamily: 'inherit',
        toolbar: { show: false },
    },
    labels: props.category_sales.map(item => item.label),
    colors: ['#0EA5E9', '#22C55E', '#F59E0B', '#A855F7', '#EF4444', '#14B8A6'],
    legend: {
        position: 'bottom' as const,
        fontSize: '12px',
        labels: { colors: '#475569' },
        markers: { size: 5, shape: 'circle' as const },
    },
    dataLabels: {
        enabled: true,
        formatter: (value: number) => `${Math.round(value)}%`,
        style: { fontSize: '12px', fontWeight: 600, colors: ['#fff'] },
    },
    tooltip: {
        y: { formatter: (value: number) => formatRupiah(value) }
    },
    stroke: { width: 0 },
}));

const categoryChartSeries = computed(() => props.category_sales.map(item => item.revenue));

const revenueGrowthLabel = computed(() => {
    const prefix = revenueGrowthPositive.value ? '+' : '-';
    return `${prefix}${Math.abs(props.stats.revenue_growth).toFixed(1)}%`;
});

// ========= QUEUE LOGIC =========
const activeQueueTab = ref<'semua' | 'pending' | 'diproses' | 'selesai'>('semua');

const filteredQueue = computed(() => {
    let filtered = props.active_orders;
    if (activeQueueTab.value !== 'semua') {
        filtered = props.active_orders.filter(trx => trx.status === activeQueueTab.value);
    }
    return filtered;
});

const displayedQueue = computed(() => {
    return filteredQueue.value.slice(0, 10);
});

const remainingQueueCount = computed(() => {
    return filteredQueue.value.length > 10 ? filteredQueue.value.length - 10 : 0;
});

const updateOrderStatus = (id: number, newStatus: string) => {
    router.patch(route('transactions.status', id), {
        status: newStatus
    }, {
        preserveScroll: true,
    });
};

// Chart: Metode Pembayaran
const paymentChartOptions = computed(() => ({
    chart: { type: 'donut' as const, fontFamily: 'inherit' },
    labels: props.payment_methods.map(m => m.label),
    colors: ['#0ea5e9', '#10b981', '#f59e0b', '#8b5cf6'],
    plotOptions: { pie: { donut: { size: '70%', labels: { show: true, total: { show: true, formatter: () => props.payment_methods.reduce((a, b) => a + b.count, 0) } } } } },
    dataLabels: { enabled: false },
    legend: { position: 'bottom' as const },
    stroke: { width: 0 }
}));

const paymentChartSeries = computed(() => props.payment_methods.map(m => m.count));

// Chart: Top Services
const serviceChartOptions = computed(() => ({
    chart: { type: 'bar' as const, fontFamily: 'inherit', toolbar: { show: false } },
    plotOptions: { bar: { borderRadius: 4, horizontal: true, distributed: true, barHeight: '60%' } },
    colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
    dataLabels: { enabled: true, formatter: (val: any) => val + 'x', style: { fontSize: '10px' } },
    xaxis: { categories: props.top_services.map(s => s.name), labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
    grid: { show: false },
    legend: { show: false },
}));

const serviceChartSeries = computed(() => [{
    name: 'Jumlah Pesanan',
    data: props.top_services.map(s => s.total_qty)
}]);

const transactionPeriod = ref<'today' | 'month'>('today');

const todayGrandTotal = computed(() => props.stats.today_paid + props.stats.today_unpaid);
const monthlyGrandTotal = computed(() => props.stats.monthly_revenue);

const monthlyMetricHints = {
    revenue: 'Total semua transaksi bulan ini yang sudah masuk proses bisnis. Order dengan status pending tidak ikut dihitung.',
    profit: 'Omzet bulanan dikurangi total pengeluaran operasional yang dicatat pada bulan yang sama.',
    paid: 'Akumulasi uang yang sudah diterima bulan ini, termasuk DP dan pelunasan transaksi.',
    unpaid: 'Total sisa tagihan dari transaksi bulan ini yang belum lunas atau masih berstatus DP.',
    expenses: 'Semua pengeluaran yang dicatat pada menu Pengeluaran dengan tanggal di bulan berjalan.',
};
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: route('dashboard') }]">
        <template #header-actions>
             <Button as-child size="sm" class="bg-blue-600 hover:bg-blue-700 shadow-sm">
                <Link :href="route('transactions.create')">
                    <PlusIcon class="h-4 w-4 mr-2" />Transaksi Baru
                </Link>
            </Button>
        </template>
        <Head title="Dashboard" />

        <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 py-6 sm:px-6 lg:px-8 bg-gray-50/30">
            <!-- Dashboard Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h2 class="text-3xl font-extrabold tracking-tight text-gray-900">Dashboard Utama</h2>
                </div>
                
                <!-- Floating Quick Actions -->
                <div class="flex flex-wrap items-center gap-2">
                    <Button as-child variant="outline" class="bg-white shadow-sm">
                        <Link :href="route('customers.index')">
                            <Users class="h-4 w-4 mr-2 text-gray-400" /> Pelanggan
                        </Link>
                    </Button>
                    <Button v-if="$page.props.auth.role === 'admin'" as-child variant="outline" class="bg-white shadow-sm">
                        <Link :href="route('expenses.index')">
                            <CircleDollarSign class="h-4 w-4 mr-2 text-gray-400" /> Pengeluaran
                        </Link>
                    </Button>
                    <Button v-if="$page.props.auth.role === 'admin'" as-child variant="outline" class="bg-white shadow-sm">
                        <Link :href="route('reports.index')">
                            <TrendingUp class="h-4 w-4 mr-2 text-gray-400" /> Laporan
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Urgent Alerts (Stock) -->
            <div v-if="low_stock_alerts.length > 0" class="animate-in fade-in slide-in-from-top-4 duration-500">
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl shadow-sm flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="h-10 w-10 bg-red-100 rounded-full flex items-center justify-center">
                            <AlertTriangle class="h-6 w-6 text-red-600" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-red-800">PERHATIAN: STOK KRITIS!</h3>
                            <p class="text-xs text-red-600 font-medium">Ada {{ low_stock_alerts.length }} item yang hampir habis.</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <div v-for="stock in low_stock_alerts.slice(0, 3)" :key="stock.id" class="hidden md:block px-3 py-1 bg-white border border-red-100 rounded-full text-[10px] font-bold text-gray-700">
                            {{ stock.name }}: {{ stock.current_qty }}
                        </div>
                        <Button as-child size="sm" variant="ghost" class="text-red-700 font-bold hover:bg-red-100">
                            <Link :href="route('stocks.index')">Detail <ArrowRight class="ml-1 h-3 w-3" /></Link>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- SECTION 1: MONITOR HARI INI (OPERASIONAL & WORKFLOW) -->
            <div class="space-y-4">
                <div class="flex items-center justify-between px-1">
                    <div class="flex items-center gap-2">
                        <div class="h-8 w-8 bg-orange-500 rounded-lg flex items-center justify-center shadow-sm">
                            <Activity class="w-4 h-4 text-white" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 tracking-tight">Monitor Hari Ini</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge variant="outline" class="bg-white border-orange-100 text-orange-600 text-[10px] font-bold px-3 py-1">
                            {{ new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long' }) }}
                        </Badge>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <!-- Today Revenue -->
                    <Card class="border-0 shadow-sm bg-white hover:shadow-md transition-all group overflow-hidden relative">
                        <div class="absolute top-0 right-0 w-16 h-16 bg-blue-500/5 rounded-bl-full -mr-4 -mt-4 group-hover:scale-110 transition-transform"></div>
                        <CardContent class="p-4 flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Omset (Selesai)</p>
                                <h4 class="text-xl font-black text-gray-900">{{ formatRupiah(stats.today_revenue) }}</h4>
                            </div>
                            <div class="h-10 w-10 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 shadow-inner group-hover:rotate-12 transition-transform">
                                <Wallet class="h-5 w-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Today Paid -->
                    <Card class="border-0 shadow-sm bg-white hover:shadow-md transition-all group overflow-hidden relative">
                         <div class="absolute top-0 right-0 w-16 h-16 bg-emerald-500/5 rounded-bl-full -mr-4 -mt-4 group-hover:scale-110 transition-transform"></div>
                        <CardContent class="p-4 flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest mb-1">Sudah Lunas</p>
                                <h4 class="text-xl font-black text-emerald-600">{{ formatRupiah(stats.today_paid) }}</h4>
                            </div>
                            <div class="h-10 w-10 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 shadow-inner group-hover:scale-110 transition-transform">
                                <CheckCircle2 class="h-5 w-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Today Unpaid -->
                    <Card class="border-0 shadow-sm bg-white hover:shadow-md transition-all group overflow-hidden relative">
                         <div class="absolute top-0 right-0 w-16 h-16 bg-orange-500/5 rounded-bl-full -mr-4 -mt-4 group-hover:scale-110 transition-transform"></div>
                        <CardContent class="p-4 flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-[10px] font-bold text-orange-500 uppercase tracking-widest mb-1">Belum Lunas</p>
                                <h4 class="text-xl font-black text-orange-600">{{ formatRupiah(stats.today_unpaid) }}</h4>
                            </div>
                            <div class="h-10 w-10 bg-orange-50 rounded-xl flex items-center justify-center text-orange-600 shadow-inner group-hover:-rotate-12 transition-transform">
                                <AlertCircle class="h-5 w-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Today Transactions -->
                    <Card class="border-0 shadow-sm bg-white hover:shadow-md transition-all group">
                        <CardContent class="p-4 flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Total Order</p>
                                <h4 class="text-xl font-black text-gray-900">{{ stats.today_transactions }} <span class="text-xs font-normal text-gray-400">trx</span></h4>
                            </div>
                            <div class="h-10 w-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 shadow-inner">
                                <ShoppingBag class="h-5 w-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Pending Orders -->
                    <Card class="border-0 shadow-sm bg-white hover:shadow-md transition-all">
                        <CardContent class="p-4 flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Antrian Pending</p>
                                <h4 class="text-xl font-black text-gray-900">{{ stats.pending_orders }} <span class="text-xs font-normal text-gray-400">antrian</span></h4>
                            </div>
                            <div class="h-10 w-10 bg-yellow-50 rounded-xl flex items-center justify-center text-yellow-600 shadow-inner animate-pulse">
                                <Clock class="h-5 w-5" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-7 gap-6">
                    <!-- Active Production Queue -->
                    <Card class="lg:col-span-5 border-0 shadow-sm flex flex-col group/queue h-[520px] bg-white">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-4 border-b">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-blue-50 rounded-lg"><Activity class="w-5 h-5 text-blue-600" /></div>
                                <CardTitle class="text-base font-bold text-gray-900">Antrian Aktif</CardTitle>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex p-1 bg-gray-100 rounded-lg">
                                    <button v-for="tab in ['semua', 'pending', 'diproses', 'selesai']" :key="tab" @click="activeQueueTab = tab as any" 
                                        :class="activeQueueTab === tab ? 'bg-white shadow-sm text-blue-600 font-bold' : 'text-gray-500 hover:text-gray-700'"
                                        class="px-3 py-1 text-[10px] rounded-md transition-all capitalize"
                                    >
                                        {{ tab }}
                                    </button>
                                </div>
                                <Button as-child variant="ghost" size="icon" class="h-8 w-8"><Link :href="route('transactions.index')"><ArrowUpRight class="h-4 w-4" /></Link></Button>
                            </div>
                        </CardHeader>
                        <CardContent class="p-0 overflow-hidden flex-1 flex flex-col">
                            <div class="overflow-y-auto px-6 pb-6 flex-1 custom-scrollbar">
                                <div v-if="displayedQueue.length === 0" class="flex flex-col items-center justify-center h-full py-20 text-gray-400">
                                    <ReceiptText class="h-12 w-12 opacity-10 mb-2" />
                                    <p class="text-sm italic text-center">Belum ada kerjaan di kategori ini.<br>Waktunya cari orderan baru!</p>
                                </div>
                                <div class="space-y-3 mt-4">
                                    <div v-for="(trx, index) in displayedQueue" :key="trx.id" class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50/30 transition-all group/item shadow-sm bg-white">
                                        <div class="flex items-center gap-4 flex-1">
                                            <div class="h-8 w-8 text-[10px] font-bold bg-gray-50 border rounded-lg flex items-center justify-center text-gray-400 shrink-0">#{{ index + 1 }}</div>
                                            <div class="space-y-0.5">
                                                <p class="text-sm font-bold text-gray-900">{{ trx.customer_name }}</p>
                                                <p class="text-[10px] text-gray-400 font-mono">{{ trx.transaction_number }} • <span class="font-bold text-blue-500 uppercase">{{ trx.created_at }}</span></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <Badge variant="outline" class="text-[10px] font-bold py-0.5 px-2 rounded-full uppercase" :class="getStatusColor(trx.status)">{{ trx.status_label }}</Badge>
                                            <div class="flex gap-1 opacity-0 group-hover/item:opacity-100 transition-opacity">
                                                <Button v-if="trx.status === 'pending'" @click="updateOrderStatus(trx.id, 'diproses')" size="sm" class="h-7 text-[9px] bg-blue-600">Proses</Button>
                                                <Button v-if="trx.status === 'diproses'" @click="updateOrderStatus(trx.id, 'selesai')" size="sm" class="h-7 text-[9px] bg-emerald-600 font-bold">Selesai</Button>
                                                <Button v-if="trx.status === 'selesai'" @click="updateOrderStatus(trx.id, 'diambil')" size="sm" class="h-7 text-[9px] bg-gray-600">Ambil</Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Sidebar Stats (Top Pelanggan + Low Stock) -->
                    <div class="lg:col-span-2 flex flex-col gap-6">
                        <Card class="border-0 shadow-sm bg-white overflow-hidden group">
                            <CardHeader class="pb-2 border-b flex flex-row items-center justify-between bg-blue-50/30">
                                <CardTitle class="text-xs font-bold text-gray-600 uppercase flex items-center gap-2">
                                    <Users class="h-4 w-4 text-blue-500" />
                                    Loyal Customers
                                </CardTitle>
                                <ArrowUpRight class="h-3 w-3 text-gray-300 group-hover:text-blue-500 transition-colors" />
                            </CardHeader>
                            <CardContent class="p-4">
                                <div class="space-y-4">
                                    <div v-for="(cust, idx) in top_customers" :key="idx" class="flex items-center justify-between group/cust">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="h-7 w-7 rounded-full bg-blue-50 flex items-center justify-center text-[10px] font-bold text-blue-600 shrink-0 group-hover/cust:bg-blue-600 group-hover/cust:text-white transition-all shadow-sm">{{ idx + 1 }}</div>
                                            <p class="text-xs font-bold text-gray-700 truncate">{{ cust.name }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[10px] font-bold text-blue-600">{{ cust.transaction_count }}x Order</p>
                                            <p class="text-[8px] text-gray-400 italic">{{ formatRupiah(cust.total_spent) }}</p>
                                        </div>
                                    </div>
                                    <div v-if="!top_customers.length" class="text-center py-4 text-[10px] text-gray-400 italic">Belum ada data pelanggan.</div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card v-if="low_stock_alerts.length" class="border-0 shadow-sm bg-red-50/50 relative overflow-hidden">
                            <div class="absolute -right-4 -bottom-4 opacity-10">
                                <AlertTriangle class="h-20 w-20 text-red-600" />
                            </div>
                            <CardHeader class="pb-2 border-b border-red-100 flex flex-row items-center justify-between relative z-10">
                                <CardTitle class="text-xs font-bold text-red-700 uppercase flex items-center gap-2">
                                    <AlertTriangle class="h-4 w-4 animate-bounce" />
                                    Stok Kritis
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="p-4 relative z-10">
                                <div class="space-y-3">
                                    <div v-for="stock in low_stock_alerts" :key="stock.id" class="flex items-center justify-between bg-white/50 p-2 rounded-lg border border-red-100">
                                        <p class="text-xs font-bold text-gray-700 truncate flex-1">{{ stock.name }}</p>
                                        <Badge variant="destructive" class="text-[9px] py-0 px-2 h-4 shadow-sm">{{ stock.current_qty }} {{ stock.unit }}</Badge>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: RINGKASAN STRATEGIS (BULANAN) -->
            <div class="space-y-4">
                <div class="flex items-center justify-between px-1">
                    <div class="flex items-center gap-2">
                        <div class="h-8 w-8 bg-blue-600 rounded-lg flex items-center justify-center shadow-sm">
                            <TrendingUp class="w-4 h-4 text-white" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 tracking-tight">Performa Bisnis Bulan Ini</h3>
                    </div>
                    <Badge variant="secondary" class="bg-blue-50 border-blue-100 text-blue-700 text-[10px] font-bold px-3 py-1 uppercase tracking-widest">{{ new Date().toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) }}</Badge>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <!-- Grand Total Monthly -->
                    <Card class="border-0 shadow-md bg-white border-l-4 border-l-indigo-600 group hover:shadow-lg transition-all duration-300">
                        <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                            <CardTitle class="flex items-center gap-1.5 text-[10px] font-bold text-indigo-600 uppercase tracking-widest">
                                Omzet Bulanan
                                <TooltipProvider :delay-duration="100">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <button type="button" class="rounded-full text-indigo-300 transition-colors hover:text-indigo-600 focus:outline-none">
                                                <AlertCircle class="h-3.5 w-3.5" />
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent side="top" class="max-w-[260px] border border-indigo-100 bg-white text-xs text-gray-700 shadow-xl">
                                            {{ monthlyMetricHints.revenue }}
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </CardTitle>
                            <TrendingUp class="h-4 w-4 text-indigo-400" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-black text-gray-900">{{ formatRupiah(monthlyGrandTotal) }}</div>
                            <p class="mt-2 text-[10px] font-medium text-gray-400 italic">Akumulasi transaksi non-pending bulan ini</p>
                        </CardContent>
                    </Card>

                    <!-- Net Profit -->
                    <Card v-if="$page.props.auth.role === 'admin'" class="border-0 shadow-md bg-gradient-to-br from-emerald-500 to-teal-600 text-white group hover:scale-[1.02] transition-all duration-300">
                        <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                            <CardTitle class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest opacity-80">
                                Estimasi Laba Bersih
                                <TooltipProvider :delay-duration="100">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <button type="button" class="rounded-full text-white/60 transition-colors hover:text-white focus:outline-none">
                                                <AlertCircle class="h-3.5 w-3.5" />
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent side="top" class="max-w-[260px] border border-emerald-100 bg-white text-xs text-gray-700 shadow-xl">
                                            {{ monthlyMetricHints.profit }}
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </CardTitle>
                            <Activity class="h-4 w-4 opacity-50" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-black">{{ formatRupiah(stats.net_profit) }}</div>
                            <p class="mt-2 text-[10px] font-bold opacity-90 bg-white/20 px-2 py-0.5 rounded-full w-fit backdrop-blur-md">
                                {{ netProfitPositive ? '📈 Profit Stabil' : '⚠️ Cek Pengeluaran' }}
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Monthly Paid -->
                    <Card class="bg-white border border-gray-100 shadow-sm group hover:shadow-md hover:scale-[1.02] transition-all duration-300">
                        <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                            <CardTitle class="flex items-center gap-1.5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                                Uang Masuk
                                <TooltipProvider :delay-duration="100">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <button type="button" class="rounded-full text-gray-300 transition-colors hover:text-emerald-600 focus:outline-none">
                                                <AlertCircle class="h-3.5 w-3.5" />
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent side="top" class="max-w-[260px] border border-emerald-100 bg-white text-xs text-gray-700 shadow-xl">
                                            {{ monthlyMetricHints.paid }}
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </CardTitle>
                            <div class="p-1.5 bg-emerald-50 rounded-lg text-emerald-600 shadow-sm"><CheckCircle2 class="h-3 w-3" /></div>
                        </CardHeader>
                        <CardContent>
                            <div class="text-xl font-black text-gray-900">{{ formatRupiah(stats.monthly_paid) }}</div>
                            <p class="mt-1 text-[9px] text-emerald-600 font-bold flex items-center gap-1">
                                <span class="h-1 w-1 bg-emerald-500 rounded-full animate-pulse"></span>
                                Termasuk DP & pelunasan
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Monthly Unpaid -->
                    <Card class="bg-white border border-gray-100 shadow-sm group hover:shadow-md hover:scale-[1.02] transition-all duration-300">
                        <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                            <CardTitle class="flex items-center gap-1.5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                                Piutang Berjalan
                                <TooltipProvider :delay-duration="100">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <button type="button" class="rounded-full text-gray-300 transition-colors hover:text-orange-600 focus:outline-none">
                                                <AlertCircle class="h-3.5 w-3.5" />
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent side="top" class="max-w-[260px] border border-orange-100 bg-white text-xs text-gray-700 shadow-xl">
                                            {{ monthlyMetricHints.unpaid }}
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </CardTitle>
                            <div class="p-1.5 bg-orange-50 rounded-lg text-orange-600 shadow-sm"><AlertCircle class="h-3 w-3" /></div>
                        </CardHeader>
                        <CardContent>
                            <div class="text-xl font-black text-orange-600">{{ formatRupiah(stats.monthly_unpaid) }}</div>
                            <p class="mt-1 text-[9px] text-orange-500 font-bold flex items-center gap-1">
                                Tagihan Belum Cair
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Expenses -->
                    <Card v-if="$page.props.auth.role === 'admin'" class="bg-white border border-gray-100 shadow-sm group hover:shadow-md hover:scale-[1.02] transition-all duration-300">
                        <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                            <CardTitle class="flex items-center gap-1.5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                                Operasional
                                <TooltipProvider :delay-duration="100">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <button type="button" class="rounded-full text-gray-300 transition-colors hover:text-red-600 focus:outline-none">
                                                <AlertCircle class="h-3.5 w-3.5" />
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent side="top" class="max-w-[260px] border border-red-100 bg-white text-xs text-gray-700 shadow-xl">
                                            {{ monthlyMetricHints.expenses }}
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </CardTitle>
                            <div class="p-1.5 bg-red-50 rounded-lg text-red-600 shadow-sm"><CreditCard class="h-3 w-3" /></div>
                        </CardHeader>
                        <CardContent>
                            <div class="text-xl font-black text-red-600">{{ formatRupiah(stats.monthly_expenses) }}</div>
                            <p class="mt-1 text-[9px] text-red-500 font-bold italic">Total Pengeluaran</p>
                        </CardContent>
                    </Card>
                </div>
            </div>


            <!-- SECTION 3: DETAIL TRANSAKSI & KEUANGAN (BULANAN) -->
            <div class="space-y-4">
                <div class="flex items-center justify-between px-1">
                    <div class="flex items-center gap-2">
                        <div class="h-8 w-8 bg-emerald-500 rounded-lg flex items-center justify-center shadow-sm">
                            <Wallet class="w-4 h-4 text-white" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 tracking-tight">Audit Keuangan Bulanan</h3>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Monthly Paid Transactions List -->
                    <Card class="border-0 shadow-sm h-[500px] flex flex-col bg-white overflow-hidden">
                        <CardHeader class="pb-3 border-b flex flex-row items-center justify-between bg-emerald-50/50">
                            <CardTitle class="text-sm font-bold text-emerald-700 flex items-center gap-2">
                                <div class="p-1.5 bg-emerald-100 rounded-lg shadow-sm"><CheckCircle2 class="w-3 h-3" /></div>
                                Transaksi Lunas (Bulan Ini)
                            </CardTitle>
                            <Badge class="bg-emerald-600 text-white border-0">{{ formatRupiah(stats.monthly_paid) }}</Badge>
                        </CardHeader>
                        <CardContent class="p-0 flex-1 overflow-hidden flex flex-col">
                            <div class="divide-y overflow-y-auto h-full custom-scrollbar">
                                <div v-for="t in monthly_paid_transactions" :key="t.id" class="p-4 hover:bg-emerald-50/30 transition-colors border-l-4 border-l-transparent hover:border-l-emerald-500">
                                    <div class="flex justify-between items-center">
                                        <div class="min-w-0 flex-1 pr-2">
                                            <p class="text-sm font-bold text-gray-900 truncate">{{ t.customer?.name || 'Pelanggan Umum' }}</p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <p class="text-[10px] text-gray-500 font-mono">{{ t.transaction_number }}</p>
                                                <span class="text-[8px] text-gray-300">•</span>
                                                <p class="text-[10px] text-gray-400">{{ new Date(t.created_at).toLocaleDateString('id-ID') }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-black text-emerald-700">{{ formatRupiah(t.total) }}</p>
                                            <Badge variant="outline" class="text-[8px] h-3.5 bg-emerald-50 text-emerald-600 border-emerald-100 px-1 font-bold uppercase">{{ t.payment_method || 'CASH' }}</Badge>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="!monthly_paid_transactions.length" class="flex flex-col items-center justify-center h-full py-20 text-gray-400">
                                    <ReceiptText class="h-10 w-10 opacity-10 mb-2" />
                                    <p class="text-xs italic">Belum ada transaksi lunas bulan ini.</p>
                                </div>
                            </div>
                            <div class="p-3 border-t bg-gray-50/50 text-center">
                                <Button as-child variant="link" size="sm" class="text-[10px] font-bold text-blue-600 uppercase">
                                    <Link :href="route('transactions.index', { status: 'lunas', period: 'month' })">Lihat Semua Transaksi Lunas →</Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Monthly Unpaid Transactions List (Piutang) -->
                    <Card class="border-0 shadow-sm h-[500px] flex flex-col bg-white overflow-hidden">
                        <CardHeader class="pb-3 border-b flex flex-row items-center justify-between bg-orange-50/50">
                            <CardTitle class="text-sm font-bold text-orange-700 flex items-center gap-2">
                                <div class="p-1.5 bg-orange-100 rounded-lg shadow-sm"><AlertCircle class="w-3 h-3" /></div>
                                Piutang Aktif (Bulan Ini)
                            </CardTitle>
                            <Badge variant="destructive" class="bg-orange-600 text-white border-0">{{ formatRupiah(stats.monthly_unpaid) }}</Badge>
                        </CardHeader>
                        <CardContent class="p-0 flex-1 overflow-hidden flex flex-col">
                            <div class="divide-y overflow-y-auto h-full custom-scrollbar">
                                <div v-for="t in monthly_unpaid_transactions" :key="t.id" class="p-4 hover:bg-orange-50/30 transition-colors border-l-4 border-l-transparent hover:border-l-orange-500">
                                    <div class="flex justify-between items-center">
                                        <div class="min-w-0 flex-1 pr-2">
                                            <p class="text-sm font-bold text-gray-900 truncate">{{ t.customer?.name || 'Pelanggan Umum' }}</p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <p class="text-[10px] text-gray-500 font-mono">{{ t.transaction_number }}</p>
                                                <span class="text-[8px] text-gray-300">•</span>
                                                <p class="text-[10px] text-gray-400">{{ new Date(t.created_at).toLocaleDateString('id-ID') }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-black text-red-600">{{ formatRupiah(t.remaining_amount) }}</p>
                                            <p class="text-[9px] text-gray-400 font-bold uppercase tracking-tighter">Sisa Bayar</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="!monthly_unpaid_transactions.length" class="flex flex-col items-center justify-center h-full py-20 text-emerald-600">
                                    <CheckCircle2 class="h-10 w-10 opacity-20 mb-2" />
                                    <p class="text-xs font-bold italic">Luar biasa! Tidak ada piutang bulan ini. ✅</p>
                                </div>
                            </div>
                            <div class="p-3 border-t bg-gray-50/50 text-center">
                                <Button as-child variant="link" size="sm" class="text-[10px] font-bold text-orange-600 uppercase">
                                    <Link :href="route('transactions.index', { payment_status: 'belum_bayar', period: 'month' })">Kelola Semua Piutang →</Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- SECTION 4: ANALITIK & INSIGHTS (ADMIN ONLY) -->
            <div v-if="$page.props.auth.role === 'admin'" class="space-y-4">
                <div class="flex items-center gap-2 px-1">
                    <div class="h-8 w-8 bg-purple-600 rounded-lg flex items-center justify-center shadow-sm">
                        <PieChart class="w-4 h-4 text-white" />
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 tracking-tight">Analisa & Insight</h3>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-7 gap-6">
                    <!-- Sales Trend Chart -->
                    <Card class="lg:col-span-5 border-0 shadow-sm overflow-hidden bg-white">
                        <CardHeader class="pb-2 flex flex-row items-center justify-between border-b">
                            <div>
                                <CardTitle class="text-base font-bold text-gray-800">Tren Pendapatan (7 Hari Terakhir)</CardTitle>
                                <p class="text-[10px] text-gray-500">Visualisasi performa omset harian</p>
                            </div>
                            <Badge variant="outline" class="bg-blue-50 text-blue-600 border-blue-100 text-[10px] font-bold uppercase">Live Statistics</Badge>
                        </CardHeader>
                        <CardContent class="pt-6 pl-2">
                            <div class="w-full h-[320px]">
                                <VueApexCharts :options="chartOptions" :series="chartSeries" type="area" height="320" />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Top Products Summary -->
                    <Card class="lg:col-span-2 border-0 shadow-sm flex flex-col bg-white">
                        <CardHeader class="pb-2 border-b">
                            <CardTitle class="text-base font-bold text-gray-800">Layanan Favorit</CardTitle>
                            <p class="text-[10px] text-gray-500">Berdasarkan volume pesanan bulan ini</p>
                        </CardHeader>
                        <CardContent class="p-4 flex-1">
                            <div class="space-y-4">
                                <div v-for="(service, idx) in top_services.slice(0, 5)" :key="idx" class="space-y-1">
                                    <div class="flex justify-between text-xs font-bold">
                                        <span class="text-gray-700 truncate w-2/3">{{ service.name }}</span>
                                        <span class="text-blue-600">{{ service.total_qty }}x</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-blue-600 h-full rounded-full transition-all duration-1000" :style="{ width: (service.total_qty / top_services[0].total_qty * 100) + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-8 pt-6 border-t border-dashed border-gray-200">
                                <div class="flex items-center justify-between mb-4">
                                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Metode Pembayaran</p>
                                </div>
                                <div class="h-[140px] flex items-center justify-center">
                                    <VueApexCharts :options="paymentChartOptions" :series="paymentChartSeries" type="donut" height="180" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                
                <!-- Financial Audit Detail Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Recent Expenses -->
                    <Card class="border-0 shadow-sm h-[400px] flex flex-col bg-white">
                        <CardHeader class="pb-3 border-b flex flex-row items-center justify-between">
                            <CardTitle class="text-sm font-bold text-red-700 flex items-center gap-2">
                                <div class="p-1.5 bg-red-100 rounded-lg shadow-sm"><ArrowDownRight class="w-3 h-3" /></div>
                                Pengeluaran Terbaru
                            </CardTitle>
                            <Button as-child variant="link" size="sm" class="text-[10px] font-bold text-gray-400 p-0 h-auto uppercase tracking-tighter">
                                <Link :href="route('expenses.index')">Detail Pengeluaran →</Link>
                            </Button>
                        </CardHeader>
                        <CardContent class="p-0 flex-1 overflow-hidden">
                            <div class="divide-y overflow-y-auto h-full custom-scrollbar">
                                <div v-for="expense in recent_expenses" :key="expense.id" class="p-4 hover:bg-red-50/30 transition-colors">
                                    <div class="flex justify-between items-start">
                                        <div class="min-w-0 flex-1 pr-2">
                                            <p class="text-xs font-bold text-gray-900 truncate">{{ expense.description }}</p>
                                            <p class="text-[10px] text-gray-500 mt-1">{{ expense.category }} • {{ expense.expense_date }}</p>
                                        </div>
                                        <p class="text-xs font-black text-red-600">{{ formatRupiah(expense.amount) }}</p>
                                    </div>
                                </div>
                                <div v-if="!recent_expenses.length" class="flex items-center justify-center h-full py-10 text-gray-400 text-[10px] italic">Belum ada data pengeluaran.</div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Today's Paid Verification (Optional small list) -->
                    <Card class="border-0 shadow-sm h-[400px] flex flex-col bg-white">
                        <CardHeader class="pb-3 border-b flex flex-row items-center justify-between">
                            <CardTitle class="text-sm font-bold text-blue-700 flex items-center gap-2">
                                <div class="p-1.5 bg-blue-100 rounded-lg shadow-sm"><CheckCircle2 class="w-3 h-3" /></div>
                                Verifikasi Bayar (Hari Ini)
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-0 flex-1 overflow-hidden">
                            <div class="divide-y overflow-y-auto h-full custom-scrollbar">
                                <div v-for="t in paid_transactions" :key="t.id" class="p-4 hover:bg-blue-50/30 transition-colors">
                                    <div class="flex justify-between items-center">
                                        <div class="min-w-0 flex-1 pr-2">
                                            <p class="text-xs font-bold text-gray-900 truncate">{{ t.customer?.name || 'Pelanggan Umum' }}</p>
                                            <p class="text-[10px] text-gray-500 mt-0.5 font-mono">{{ t.transaction_number }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs font-bold text-blue-700">{{ formatRupiah(t.total) }}</p>
                                            <Badge variant="outline" class="text-[8px] h-3.5 bg-blue-50 text-blue-600 border-blue-100 px-1 font-bold">{{ t.payment_method || 'CASH' }}</Badge>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="!paid_transactions.length" class="flex items-center justify-center h-full py-10 text-gray-400 text-[10px] italic font-medium">Belum ada pembayaran hari ini.</div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Activity History (Footer) -->
            <Card class="border-0 shadow-sm overflow-hidden mt-8 opacity-80 hover:opacity-100 transition-opacity">
                <CardHeader class="p-4 bg-gray-100 flex flex-row justify-between items-center">
                    <div class="flex items-center gap-2">
                        <ReceiptText class="h-4 w-4 text-gray-500" />
                        <CardTitle class="text-xs font-bold text-gray-600 uppercase tracking-widest">History 5 Transaksi Terakhir</CardTitle>
                    </div>
                    <Button as-child variant="link" size="sm" class="h-auto p-0 text-[10px] font-bold text-blue-600"><Link :href="route('transactions.index')">History Lengkap →</Link></Button>
                </CardHeader>
                <CardContent class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div v-for="trx in recent_transactions" :key="trx.id" class="flex flex-col gap-1 p-3 bg-white rounded-xl border border-gray-100 shadow-xs">
                            <p class="text-[10px] font-black text-gray-900 truncate">{{ trx.customer_name }}</p>
                            <p class="text-[9px] text-gray-400 font-mono">{{ trx.transaction_number }}</p>
                            <div class="flex justify-between items-center mt-2 pt-2 border-t border-dashed">
                                <span class="text-[10px] font-bold">{{ formatRupiah(trx.total) }}</span>
                                <Badge variant="outline" class="text-[8px] h-3.5 px-1 font-bold" :class="getStatusColor(trx.status)">{{ trx.status_label }}</Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>
