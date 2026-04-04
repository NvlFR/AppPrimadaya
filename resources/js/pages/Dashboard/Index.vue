<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Wallet, CreditCard, Clock, Activity, TrendingUp, ShoppingCart } from 'lucide-vue-next';
import VueApexCharts from 'vue3-apexcharts';
import { computed } from 'vue';

const props = defineProps<{
    stats: {
        today_revenue: number;
        today_transactions: number;
        monthly_revenue: number;
        monthly_expenses: number;
        pending_orders: number;
        net_profit: number;
    };
    sales_chart: Array<{
        date: string;
        label: string;
        revenue: number;
        count: number;
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

const formatRupiah = (number: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(number);
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'diproses': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'selesai': return 'bg-green-100 text-green-800 border-green-200';
        case 'diambil': return 'bg-gray-100 text-gray-800 border-gray-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

// Setup untuk chart grafik
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
</script>

<template>
    <Head title="Dashboard" />

    <div class="px-6 py-6 md:px-8 space-y-8">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900">Dashboard</h2>
                <p class="text-sm text-gray-500 mt-1">Ringkasan aktivitas dan performa percetakan hari ini.</p>
            </div>
            <div class="mt-4 md:mt-0 flex gap-3">
                <Link :href="route('transactions.create')" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none ring-offset-background bg-primary text-primary-foreground hover:bg-primary/90 h-10 py-2 px-4 shadow-sm">
                    <ShoppingCart class="mr-2 h-4 w-4" />
                    Transaksi Baru
                </Link>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card class="hover:shadow-md transition-shadow">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium text-gray-500">Penjualan Hari Ini</CardTitle>
                    <div class="h-8 w-8 bg-blue-50 flex items-center justify-center rounded-lg text-primary">
                        <Wallet class="h-4 w-4" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-gray-900">{{ formatRupiah(stats.today_revenue) }}</div>
                    <p class="text-xs text-muted-foreground mt-1">
                        <span class="text-green-600 font-medium">{{ stats.today_transactions }}</span> transaksi berhasil
                    </p>
                </CardContent>
            </Card>

            <Card class="hover:shadow-md transition-shadow">
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

            <Card class="hover:shadow-md transition-shadow">
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

            <Card class="hover:shadow-md transition-shadow">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium text-gray-500">Pengeluaran Bulan Ini</CardTitle>
                    <div class="h-8 w-8 bg-red-50 flex items-center justify-center rounded-lg text-red-600">
                        <CreditCard class="h-4 w-4" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-gray-900">{{ formatRupiah(stats.monthly_expenses) }}</div>
                    <p class="text-xs text-muted-foreground mt-1 text-red-500 font-medium">
                        Laba Bersih: {{ formatRupiah(stats.net_profit) }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-7">
            <!-- Chart Section -->
            <Card class="lg:col-span-4 hover:shadow-md transition-shadow border">
                <CardHeader>
                    <CardTitle class="text-lg text-gray-800">Grafik Penjualan 7 Hari Terakhir</CardTitle>
                </CardHeader>
                <CardContent class="pl-2">
                    <div v-if="sales_chart.length > 0">
                        <!-- Komponen Chart jika apexchart berhasil ke load -->
                        <div class="w-full h-[300px]">
                            <VueApexCharts :options="chartOptions" :series="chartSeries" type="area" height="300" />
                        </div>
                    </div>
                    <div v-else class="h-[300px] flex items-center justify-center border-2 border-dashed border-gray-200 rounded-lg text-gray-500 text-sm">
                        Belum ada data penjualan.
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Transactions Table -->
            <Card class="col-span-1 lg:col-span-3 hover:shadow-md transition-shadow border">
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
                                <div class="flex items-center gap-2">
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
</template>
