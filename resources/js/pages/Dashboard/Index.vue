<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Wallet, CreditCard, Clock, Activity, TrendingUp, TrendingDown, ShoppingCart, PieChart, AlertTriangle } from 'lucide-vue-next';
import VueApexCharts from 'vue3-apexcharts';
import { computed } from 'vue';
import { useFormatRupiah } from '@/composables/useFormatRupiah';

const props = defineProps<{
    stats: {
        today_revenue: number;
        today_transactions: number;
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
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: route('dashboard') }]">
        <Head title="Dashboard" />

        <div class="px-6 py-6 md:px-8 space-y-8">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900">Dashboard</h2>
                    <p class="text-sm text-gray-500 mt-1">Ringkasan aktivitas dan performa percetakan hari ini.</p>
                </div>
                <div class="flex gap-3">
                    <Link :href="route('transactions.create')" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none ring-offset-background bg-primary text-primary-foreground hover:bg-primary/90 h-10 py-2 px-4 shadow-sm">
                        <ShoppingCart class="mr-2 h-4 w-4" />
                        Transaksi Baru
                    </Link>
                </div>
            </div>

            <!-- Metric Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                <Card class="hover:shadow-md transition-shadow border">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-gray-500">Penjualan Hari Ini</CardTitle>
                        <div class="h-8 w-8 bg-blue-50 flex items-center justify-center rounded-lg text-primary">
                            <Wallet class="h-4 w-4" />
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="text-2xl font-bold text-gray-900">{{ formatRupiah(stats.today_revenue) }}</div>
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
                        <div class="text-2xl font-bold text-gray-900">{{ stats.pending_orders }} <span class="text-base font-normal text-gray-500">pesanan</span></div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Segera proses untuk hari ini
                        </p>
                    </CardContent>
                </Card>

                <Card class="hover:shadow-md transition-shadow border">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-gray-500">Omset Bulan Ini</CardTitle>
                        <div class="h-8 w-8 bg-green-50 flex items-center justify-center rounded-lg text-green-600">
                            <TrendingUp class="h-4 w-4" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-gray-900">{{ formatRupiah(stats.monthly_revenue) }}</div>
                        <p class="text-xs text-muted-foreground mt-1 text-green-600">
                            Pemasukan kotor berjalan
                        </p>
                    </CardContent>
                </Card>

                <Card class="hover:shadow-md transition-shadow border">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-gray-500">Pengeluaran Bulan Ini</CardTitle>
                        <div class="h-8 w-8 bg-red-50 flex items-center justify-center rounded-lg text-red-600">
                            <CreditCard class="h-4 w-4" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-gray-900">{{ formatRupiah(stats.monthly_expenses) }}</div>
                        <p class="text-xs text-muted-foreground mt-1 text-red-500 font-medium">
                            Beban operasional berjalan
                        </p>
                    </CardContent>
                </Card>

                <Card class="hover:shadow-md transition-shadow border" :class="netProfitTone">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Laba Bersih</CardTitle>
                        <div class="h-8 w-8 rounded-lg flex items-center justify-center bg-white/70">
                            <component :is="netProfitIcon" class="h-4 w-4" />
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="text-2xl font-bold" :class="netProfitPositive ? 'text-emerald-700' : 'text-red-700'">
                            {{ formatRupiah(stats.net_profit) }}
                        </div>
                        <p class="text-xs font-medium" :class="netProfitPositive ? 'text-emerald-700' : 'text-red-700'">
                            {{ netProfitPositive ? 'Profit bulan ini masih aman.' : 'Laba bersih negatif, perlu perhatian.' }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-7">
                <!-- Chart Section -->
                <Card class="lg:col-span-4 hover:shadow-md transition-shadow border">
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

                <Card class="lg:col-span-3 hover:shadow-md transition-shadow border">
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

                <!-- Recent Transactions Table -->
                <Card class="lg:col-span-7 hover:shadow-md transition-shadow border">
                    <CardHeader class="flex flex-row justify-between items-center pb-2">
                        <CardTitle class="text-lg text-gray-800">Transaksi Terbaru</CardTitle>
                        <Link :href="route('transactions.index')" class="text-sm text-primary hover:underline font-medium">Lihat Semua</Link>
                    </CardHeader>
                    <CardContent>
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
                                        <p class="text-xs text-gray-500">
                                            {{ trx.transaction_number }}
                                        </p>
                                        <span class="inline-flex text-[10px] items-center px-2 py-0.5 rounded-full border border-gray-200 font-medium"
                                            :class="getStatusColor(trx.status)">
                                            {{ trx.status_label }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-auto font-bold text-sm text-gray-900">
                                    {{ formatRupiah(trx.total) }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
