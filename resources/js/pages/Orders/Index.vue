<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Search, Filter, Eye, Clock, CheckCircle2, Package, ArrowRight } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { useFormatRupiah } from '@/composables/useFormatRupiah';

interface Order {
    id: number;
    transaction_number: string;
    customer_name: string;
    total: number;
    status: string;
    status_label: string;
    created_at: string;
}

const props = defineProps<{
    orders: {
        data: Order[];
        links: any[];
        current_page: number;
        last_page: number;
        // Field tambahan dari array_merge di controller — jumlah order per status
        status_counts?: Record<string, number>;
    };
    filters: { search?: string; status?: string };
    status_options: Record<string, string>;
}>();

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

let searchTimeout: any;
watch([search, statusFilter], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('orders.index'), { search: search.value, status: statusFilter.value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const getStatusIcon = (status: string) => {
    switch(status) {
        case 'selesai': return CheckCircle2;
        case 'diambil': return Package;
        case 'diproses': return Clock;
        default: return Clock;
    }
};

const getStatusColor = (status: string) => {
    switch(status) {
        case 'selesai': return 'bg-green-100 text-green-800 border-green-200';
        case 'diambil': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'diproses': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'pending': default: return 'bg-orange-100 text-orange-800 border-orange-200';
    }
};

const { formatRupiah } = useFormatRupiah();
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: route('dashboard') }, { title: 'Tracking Pesanan', href: route('orders.index') }]">
        <Head title="Tracking Pesanan" />

        <div class="px-4 py-6 md:px-8 space-y-6 max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <Package class="h-6 w-6 text-primary" /> Tracking Pesanan
                    </h1>
                    <p class="text-sm text-gray-500">Pantau status pengerjaan dokumen dan pesanan pelanggan.</p>
                </div>
            </div>

            <!-- Dashboard Mini Stats (Optional) -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div v-for="(label, key) in status_options" :key="key" 
                    class="bg-white p-4 rounded-xl border shadow-sm flex items-center justify-between cursor-pointer hover:border-primary transition-colors"
                    :class="statusFilter === key ? 'border-primary ring-1 ring-primary' : ''"
                    @click="statusFilter = statusFilter === key ? '' : (key as string)"
                >
                    <div class="space-y-1">
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">{{ label }}</p>
                        <p class="text-lg font-bold text-gray-800">
                             {{ orders.status_counts?.[key] || 0 }} <span class="text-xs font-normal text-gray-400 italic">Pcs</span>
                        </p>
                    </div>
                    <component :is="getStatusIcon(key as string)" class="h-5 w-5" :class="getStatusColor(key as string).split(' ')[1]" />
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-4 bg-white p-4 rounded-xl border shadow-sm items-center">
                <div class="relative flex-1 w-full">
                    <Search class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                    <Input v-model="search" type="search" placeholder="Cari No. Transaksi (TRX-xxx)..." class="pl-10 h-10" />
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <Filter class="h-4 w-4 text-gray-400" />
                    <select v-model="statusFilter" class="flex h-10 w-full sm:w-[200px] rounded-md border border-input bg-white px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                        <option value="">Semua Status</option>
                        <option v-for="(label, key) in status_options" :key="key" :value="key">
                            {{ label }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Order Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="order in orders.data" :key="order.id" class="group relative bg-white border rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
                    <!-- Progress Bar (Simulation based on status) -->
                    <div class="absolute top-0 left-0 h-1 bg-primary" :style="{ width: order.status === 'selesai' || order.status === 'diambil' ? '100%' : '50%' }"></div>
                    
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-black text-gray-300 tracking-widest uppercase">{{ order.transaction_number }}</span>
                            <Badge :class="getStatusColor(order.status)" class="rounded-full px-3 py-0.5 font-bold shadow-sm">
                                {{ order.status_label }}
                            </Badge>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 group-hover:text-primary transition-colors">{{ order.customer_name }}</h3>
                            <p class="text-xs text-gray-400 flex items-center gap-1.5 mt-1">
                                <Clock class="h-3 w-3" /> {{ order.created_at }}
                            </p>
                        </div>

                        <div class="pt-4 border-t border-gray-50 flex items-center justify-between">
                            <div class="text-sm">
                                <p class="text-gray-400 text-[10px] uppercase font-bold tracking-wider">Nilai Pesanan</p>
                                <p class="font-black text-gray-900">{{ formatRupiah(order.total) }}</p>
                            </div>
                            <Button @click="router.get(route('transactions.show', order.id))" variant="ghost" size="sm" class="h-8 rounded-full bg-gray-100 hover:bg-primary hover:text-white transition-all group-hover:px-4">
                                <Eye class="h-4 w-4 mr-1" /> <span class="hidden group-hover:inline text-xs transition-all">Detail</span> <ArrowRight class="h-3 w-3 ml-1" />
                            </Button>
                        </div>
                    </div>
                </div>

                <div v-if="orders.data.length === 0" class="col-span-full py-16 bg-white rounded-2xl border border-dashed flex flex-col items-center justify-center text-gray-400">
                    <Package class="h-12 w-12 opacity-20 mb-3" />
                    <p class="font-medium text-lg">Tidak ada pesanan aktif.</p>
                    <p class="text-sm">Coba cari dengan kriteria lain atau buat transaksi baru.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div class="flex justify-center mt-8" v-if="orders.last_page > 1">
                <div class="flex items-center gap-2 bg-white p-2 rounded-full border shadow-sm">
                    <Button 
                        v-for="link in orders.links" 
                        :key="link.label"
                        variant="ghost"
                        size="sm"
                        class="h-8 min-w-[32px] rounded-full"
                        :class="link.active ? 'bg-primary text-white hover:bg-primary hover:text-white' : ''"
                        @click="link.url ? router.get(link.url) : null"
                        :disabled="!link.url"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
