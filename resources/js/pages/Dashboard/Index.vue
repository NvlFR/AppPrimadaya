<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Wallet, CreditCard, Clock, Activity, TrendingUp, TrendingDown, PieChart, AlertTriangle, PlusIcon, CheckCircle, CheckCircle2, CircleDollarSign, AlertCircle } from 'lucide-vue-next';
import { computed, ref, defineAsyncComponent } from 'vue';

const VueApexCharts = defineAsyncComponent(() => import('vue3-apexcharts'));
import { useFormatRupiah } from '@/composables/useFormatRupiah';

const props = defineProps<{
    stats: {
        today_revenue: number;
        today_transactions: number;
        today_paid: number;
        today_unpaid: number;
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
    category_sales: Array<{
        label: string;
        category: string;
        revenue: number;
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

const openCreateModal = () => {
    router.visit(route('transactions.create'));
};

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
// ===============================
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

        <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900">Dashboard</h2>
                    <p class="text-sm text-gray-500 mt-1">Ringkasan aktivitas dan performa percetakan hari ini.</p>
                </div>
                <div class="flex gap-3">
                    <!-- <Link :href="route('transactions.create')" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none ring-offset-background bg-primary text-primary-foreground hover:bg-primary/90 h-10 py-2 px-4 shadow-sm">
                        <ShoppingCart class="mr-2 h-4 w-4" />
                        Transaksi Baru
                    </Link> -->
                </div>
            </div>

            <!-- Metric Cards -->
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
                <Card class="hover:shadow-md transition-shadow border">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-gray-500">Penjualan Hari Ini</CardTitle>
                        <div class="h-8 w-8 bg-blue-50 flex items-center justify-center rounded-lg text-primary">
                            <Wallet class="h-4 w-4" />
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="text-2xl font-bold leading-tight text-gray-900">
                            <span>{{ formatRupiah(stats.today_revenue) }}</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-xs text-muted-foreground">
                                <span class="text-green-600 font-medium">{{ stats.today_transactions }}</span> transaksi berhasil
                            </p>
                            <Badge :class="revenueGrowthColor" class="gap-1 border text-[11px] font-semibold">
                                <component :is="revenueGrowthIcon" class="h-3 w-3" />
                                {{ revenueGrowthLabel }} vs kemarin
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <Card class="hover:shadow-md transition-shadow border">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-gray-500">Pesanan Pending</CardTitle>
                        <div class="h-8 w-8 bg-yellow-50 flex items-center justify-center rounded-lg text-yellow-600">
                            <Clock class="h-4 w-4" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="break-words text-2xl font-bold leading-tight text-gray-900">{{ stats.pending_orders }} <span class="text-base font-normal text-gray-500">pesanan</span></div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Segera proses untuk hari ini
                        </p>
                    </CardContent>
                </Card>

                <!-- Card: Total Sudah Bayar (Lunas) -->
                <Card class="hover:shadow-md transition-shadow border">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-gray-500">Uang Diterima</CardTitle>
                        <div class="h-8 w-8 bg-emerald-50 flex items-center justify-center rounded-lg text-emerald-600">
                            <CheckCircle2 class="h-4 w-4" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="break-words text-2xl font-bold leading-tight text-emerald-700">{{ formatRupiah(stats.today_paid) }}</div>
                        <p class="text-xs text-muted-foreground mt-1 text-emerald-600 font-medium">
                            Total uang masuk (Cash/DP)
                        </p>
                    </CardContent>
                </Card>

                <!-- Card: Total Belum/Kurang Bayar -->
                <Card class="hover:shadow-md transition-shadow border">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-gray-500">Sisa Piutang</CardTitle>
                        <div class="h-8 w-8 bg-orange-50 flex items-center justify-center rounded-lg text-orange-500">
                            <AlertCircle class="h-4 w-4" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="break-words text-2xl font-bold leading-tight text-orange-600">{{ formatRupiah(stats.today_unpaid) }}</div>
                        <p class="text-xs text-muted-foreground mt-1 text-orange-500 font-medium">
                            Sisa piutang hari ini
                        </p>
                    </CardContent>
                </Card>

                <Card v-if="$page.props.auth.role === 'admin'" class="hover:shadow-md transition-shadow border">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-gray-500">Omset Bulan Ini</CardTitle>
                        <div class="h-8 w-8 bg-green-50 flex items-center justify-center rounded-lg text-green-600">
                            <TrendingUp class="h-4 w-4" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="break-words text-2xl font-bold leading-tight text-gray-900">{{ formatRupiah(stats.monthly_revenue) }}</div>
                        <p class="text-xs text-muted-foreground mt-1 text-green-600">
                            Pemasukan kotor berjalan
                        </p>
                    </CardContent>
                </Card>

                <Card v-if="$page.props.auth.role === 'admin'" class="hover:shadow-md transition-shadow border">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-gray-500">Pengeluaran Bulan Ini</CardTitle>
                        <div class="h-8 w-8 bg-red-50 flex items-center justify-center rounded-lg text-red-600">
                            <CreditCard class="h-4 w-4" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="break-words text-2xl font-bold leading-tight text-gray-900">{{ formatRupiah(stats.monthly_expenses) }}</div>
                        <p class="text-xs text-muted-foreground mt-1 text-red-500 font-medium">
                            Beban operasional berjalan
                        </p>
                    </CardContent>
                </Card>

                <Card v-if="$page.props.auth.role === 'admin'" class="hover:shadow-md transition-shadow border" :class="netProfitTone">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Laba Bersih</CardTitle>
                        <div class="h-8 w-8 rounded-lg flex items-center justify-center bg-white/70">
                            <component :is="netProfitIcon" class="h-4 w-4" />
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="break-words text-2xl font-bold leading-tight" :class="netProfitPositive ? 'text-emerald-700' : 'text-red-700'">
                            {{ formatRupiah(stats.net_profit) }}
                        </div>
                        <p class="text-xs font-medium" :class="netProfitPositive ? 'text-emerald-700' : 'text-red-700'">
                            {{ netProfitPositive ? 'Profit bulan ini masih aman.' : 'Laba bersih negatif, perlu perhatian.' }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 items-stretch" :class="$page.props.auth.role === 'admin' ? 'lg:grid-cols-7' : 'lg:grid-cols-2'">
                <!-- Chart Section -->
                <Card v-if="$page.props.auth.role === 'admin'" class="lg:col-span-4 hover:shadow-md transition-shadow border">
                    <CardHeader>
                        <CardTitle class="text-lg text-gray-800">Grafik Penjualan 7 Hari Terakhir</CardTitle>
                    </CardHeader>
                    <CardContent class="pl-2">
                        <div v-if="sales_chart.length > 0">
                            <div class="w-full h-[300px]">
                                <VueApexCharts :options="chartOptions" :series="chartSeries" type="area" height="300" />
                            </div>
                        </div>
                        <div v-else class="h-[300px] flex items-center justify-center border-2 border-dashed border-gray-200 rounded-lg text-gray-500 text-sm">
                            Belum ada data penjualan.
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="$page.props.auth.role === 'admin'" class="lg:col-span-3 hover:shadow-md transition-shadow border">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-lg text-gray-800">Distribusi Omzet</CardTitle>
                        <PieChart class="h-4 w-4 text-gray-400" />
                    </CardHeader>
                    <CardContent>
                        <div v-if="category_sales.length > 0">
                            <div class="w-full h-[300px]">
                                <VueApexCharts :options="categoryChartOptions" :series="categoryChartSeries" type="pie" height="300" />
                            </div>
                        </div>
                        <div v-else class="h-[300px] flex flex-col items-center justify-center border-2 border-dashed border-gray-200 rounded-lg text-gray-500 text-sm gap-2">
                            <PieChart class="h-8 w-8 text-gray-300" />
                            <span>Belum ada data kategori untuk ditampilkan.</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Antrian & Pesanan Aktif -->
                <Card class="hover:shadow-md transition-shadow border h-full" :class="$page.props.auth.role === 'admin' ? 'lg:col-span-7' : 'lg:col-span-1'">
                    <CardHeader class="flex flex-col space-y-2 pb-2">
                        <div class="flex items-center justify-between w-full">
                            <CardTitle class="text-lg text-gray-800">Antrian Aktif</CardTitle>
                            <Button as-child variant="outline" size="sm" class="h-8 text-xs">
                                <Link :href="route('transactions.index')">Kelola</Link>
                            </Button>
                        </div>
                        
                        <!-- Tabs -->
                        <div class="flex gap-2 border-b mt-2 overflow-x-auto pb-1 no-scrollbar">
                            <button @click="activeQueueTab = 'semua'" :class="activeQueueTab === 'semua' ? 'border-primary text-primary font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-2 py-1 border-b-2 text-sm transition-colors whitespace-nowrap">Semua</button>
                            <button @click="activeQueueTab = 'pending'" :class="activeQueueTab === 'pending' ? 'border-yellow-500 text-yellow-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-2 py-1 border-b-2 text-sm transition-colors whitespace-nowrap">Pending</button>
                            <button @click="activeQueueTab = 'diproses'" :class="activeQueueTab === 'diproses' ? 'border-blue-500 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-2 py-1 border-b-2 text-sm transition-colors whitespace-nowrap">Diproses</button>
                            <button @click="activeQueueTab = 'selesai'" :class="activeQueueTab === 'selesai' ? 'border-green-500 text-green-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-2 py-1 border-b-2 text-sm transition-colors whitespace-nowrap">Selesai</button>
                        </div>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="overflow-y-auto max-h-[480px] px-6 pb-6">
                            <div class="space-y-3 mt-2">
                            <div v-if="displayedQueue.length === 0" class="text-center py-8 text-gray-500 text-sm">
                                Tidak ada antrian berjalan saat ini.
                            </div>

                            <div v-for="(trx, index) in displayedQueue" :key="trx.id" class="flex flex-col sm:flex-row sm:items-center px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors border" :class="{ 'border-l-4 border-l-yellow-400': trx.status === 'pending', 'border-gray-100': trx.status !== 'pending' }">
                                <div class="flex justify-between items-start sm:items-center w-full">
                                    <div class="flex items-center gap-3 flex-1">
                                        <div class="h-8 w-8 text-xs font-bold bg-white border border-gray-200 rounded-full flex items-center justify-center shadow-sm text-gray-600 shrink-0">
                                            #{{ index + 1 }}
                                        </div>
                                        <div class="space-y-0.5">
                                            <p class="text-sm font-bold leading-none text-gray-900">{{ trx.customer_name }}</p>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] text-gray-400 font-mono">{{ trx.transaction_number }}</span>
                                                <span class="text-[10px] text-gray-400">• {{ trx.created_at }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Badges & Action -->
                                    <div class="flex items-center justify-end gap-2 sm:gap-3 mt-2 sm:mt-0 shrink-0">
                                        <span class="inline-flex justify-center text-[10px] items-center px-1 py-0.5 rounded-full border border-gray-200 font-medium w-[72px] shrink-0"
                                            :class="getStatusColor(trx.status)">
                                            {{ trx.status_label }}
                                        </span>
                                        
                                        <!-- Actions -->
                                        <template v-if="trx.status === 'pending'">
                                            <Button @click="updateOrderStatus(trx.id, 'diproses')" size="sm" variant="outline" class="w-[72px] shrink-0 h-7 text-xs bg-white text-blue-600 border-blue-200 hover:bg-blue-50">Proses</Button>
                                        </template>
                                        <template v-else-if="trx.status === 'diproses'">
                                            <Button @click="updateOrderStatus(trx.id, 'selesai')" size="sm" variant="outline" class="w-[72px] shrink-0 h-7 text-xs bg-white text-green-600 border-green-200 hover:bg-green-50">Selesai</Button>
                                        </template>
                                        <template v-else-if="trx.status === 'selesai'">
                                            <Button @click="updateOrderStatus(trx.id, 'diambil')" size="sm" variant="outline" class="w-[72px] shrink-0 h-7 text-xs bg-white text-gray-600 border-gray-200 hover:bg-gray-50">Diambil</Button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div v-if="remainingQueueCount > 0" class="text-center pt-2">
                                <Link :href="route('transactions.index', { status: activeQueueTab !== 'semua' ? activeQueueTab : '' })" class="text-xs text-gray-500 hover:text-primary transition-colors">
                                    + {{ remainingQueueCount }} antrian lainnya. Lihat semua.
                                </Link>
                            </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Transactions Table -->
                <Card :class="$page.props.auth.role === 'admin' ? 'lg:col-span-7' : 'lg:col-span-1'" class="hover:shadow-md transition-shadow border h-full">
                    <CardHeader class="flex flex-row justify-between items-center pb-2 w-full">
                        <CardTitle class="text-lg text-gray-800">Transaksi Terbaru</CardTitle>
                        <Button as-child variant="outline" size="sm" class="h-8 text-xs">
                            <Link :href="route('transactions.index')">Lihat Semua</Link>
                        </Button>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="overflow-y-auto max-h-[480px] px-6 pb-6">
                            <div class="space-y-4">
                            <div v-if="recent_transactions.length === 0" class="text-center py-8 text-gray-500 text-sm">
                                Belum ada transaksi hari ini.
                            </div>

                            <div v-for="trx in recent_transactions" :key="trx.id" class="flex items-center px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors">
                                <div class="mr-4 h-10 w-10 bg-white border border-gray-200 rounded-full flex items-center justify-center shadow-sm">
                                    <Activity class="h-4 w-4 text-primary" />
                                </div>
                                <div class="space-y-1 flex-1">
                                    <p class="text-sm font-semibold leading-none text-gray-900">{{ trx.customer_name }}</p>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="text-xs text-gray-500 font-mono">
                                            {{ trx.transaction_number }}
                                        </p>
                                    </div>
                                </div>
                                <div class="ml-auto flex flex-col items-end gap-1.5 shrink-0">
                                    <div class="font-bold text-sm text-gray-900">
                                        {{ formatRupiah(trx.total) }}
                                    </div>
                                    <span class="inline-flex justify-center text-[10px] items-center px-1 py-0.5 rounded-full border border-gray-200 font-medium w-[72px]"
                                        :class="getStatusColor(trx.status)">
                                        {{ trx.status_label }}
                                    </span>
                                </div>
                            </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
