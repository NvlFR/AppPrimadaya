<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Search, Filter, Eye, Clock, CheckCircle2, Package, ArrowRight, CheckCheck, TimerReset } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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
    filters: { search?: string; status?: string; per_page?: string };
    status_options: Record<string, string>;
}>();

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const perPageFilter = ref(props.filters.per_page || '12');

const page = usePage();
const isAdmin = computed(() => (page.props.auth as any)?.role === 'admin');
const selectedOrderIds = ref<number[]>([]);
const isBulkStatusDialogOpen = ref(false);
const bulkStatusTarget = ref<'diproses' | 'selesai' | null>(null);

const isOrderSelected = (id: number) => selectedOrderIds.value.includes(id);
const toggleOrderSelection = (id: number, checked: boolean | string) => {
    const nextChecked = checked === true || checked === 'indeterminate';
    if (nextChecked) {
        if (!isOrderSelected(id)) {
            selectedOrderIds.value = [...selectedOrderIds.value, id];
        }
        return;
    }

    selectedOrderIds.value = selectedOrderIds.value.filter(orderId => orderId !== id);
};

const clearSelectedOrders = () => {
    selectedOrderIds.value = [];
};

const openBulkStatusDialog = (status: 'diproses' | 'selesai') => {
    if (selectedOrderIds.value.length === 0) return;
    bulkStatusTarget.value = status;
    isBulkStatusDialogOpen.value = true;
};

const executeBulkStatusUpdate = () => {
    if (!bulkStatusTarget.value || selectedOrderIds.value.length === 0) return;

    router.patch(route('orders.bulk-status'), {
        transaction_ids: selectedOrderIds.value,
        status: bulkStatusTarget.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isBulkStatusDialogOpen.value = false;
            bulkStatusTarget.value = null;
            clearSelectedOrders();
            router.get(route('orders.index'), {
                search: search.value,
                status: statusFilter.value,
                per_page: perPageFilter.value,
            }, {
                preserveState: true,
                replace: true,
            });
        },
    });
};

let searchTimeout: any;
watch([search, statusFilter, perPageFilter], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('orders.index'), {
            search: search.value,
            status: statusFilter.value,
            per_page: perPageFilter.value,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

watch([search, statusFilter, perPageFilter], () => {
    clearSelectedOrders();
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

        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <Package class="h-6 w-6 text-primary" /> Tracking Pesanan
                    </h1>
                    <p class="text-sm text-gray-500">Pantau status pengerjaan dokumen dan pesanan pelanggan.</p>
                </div>
            </div>

                        <!-- Filters -->
            <div class="flex flex-col gap-4 rounded-xl border bg-white p-4 shadow-sm xl:flex-row xl:items-center">
                <div class="relative flex-1 w-full">
                    <Search class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                    <Input v-model="search" type="search" placeholder="Cari No. Transaksi (TRX-xxx)..." class="pl-10 h-10" />
                </div>
                <div class="flex items-center gap-2 w-full xl:w-auto">
                    <Filter class="h-4 w-4 text-gray-400" />
                    <select v-model="statusFilter" class="flex h-10 w-full xl:w-[200px] rounded-md border border-input bg-white px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                        <option value="">Semua Status</option>
                        <option v-for="(label, key) in status_options" :key="key" :value="key">
                            {{ label }}
                        </option>
                    </select>
                </div>
                <div class="flex items-center gap-2 w-full xl:w-auto">
                    <Filter class="h-4 w-4 text-gray-400" />
                    <select v-model="perPageFilter" class="flex h-10 w-full xl:w-[160px] rounded-md border border-input bg-white px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                        <option value="12">Tampilkan 12</option>
                        <option value="24">Tampilkan 24</option>
                        <option value="48">Tampilkan 48</option>
                    </select>
                </div>
            </div>

            <!-- Dashboard Mini Stats (Optional) -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 2xl:grid-cols-4">
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

            <div v-if="selectedOrderIds.length > 0" class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between bg-slate-900 text-white rounded-2xl px-4 py-3 shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 rounded-full bg-white/10 flex items-center justify-center">
                        <CheckCheck class="h-4 w-4" />
                    </div>
                    <div>
                        <p class="font-semibold">{{ selectedOrderIds.length }} pesanan dipilih</p>
                        <p class="text-xs text-slate-300">Gunakan aksi massal untuk mempercepat pemrosesan.</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" class="border-white/20 bg-white/5 text-white hover:bg-white/10 hover:text-white" @click="clearSelectedOrders">
                        <TimerReset class="mr-2 h-4 w-4" />
                        Batal
                    </Button>
                    <Button class="bg-blue-600 hover:bg-blue-700" @click="openBulkStatusDialog('diproses')">
                        <Clock class="mr-2 h-4 w-4" />
                        Tandai Diproses
                    </Button>
                    <Button class="bg-emerald-600 hover:bg-emerald-700" @click="openBulkStatusDialog('selesai')">
                        <CheckCircle2 class="mr-2 h-4 w-4" />
                        Tandai Selesai
                    </Button>
                </div>
            </div>



            <!-- Order Grid -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 2xl:grid-cols-3">
                <div v-for="order in orders.data" :key="order.id" class="group relative bg-white border rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden"
                    :class="isOrderSelected(order.id) ? 'ring-2 ring-primary border-primary' : ''">
                    <!-- Progress Bar (Simulation based on status) -->
                    <div class="absolute top-0 left-0 h-1 bg-primary" :style="{ width: order.status === 'selesai' || order.status === 'diambil' ? '100%' : '50%' }"></div>
                    
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-start gap-4">
                            <Checkbox
                                v-if="isAdmin"
                                :model-value="isOrderSelected(order.id)"
                                @update:model-value="checked => toggleOrderSelection(order.id, checked)"
                                class="mt-1"
                            />
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
            <div class="mt-8" v-if="orders.last_page > 1">
                <!-- Mobile: Prev | Hal X / Y | Next -->
                <div class="flex items-center justify-between gap-3 md:hidden">
                    <Button
                        variant="outline"
                        size="sm"
                        class="h-10 rounded-xl flex-1"
                        :disabled="!orders.links[0]?.url"
                        @click="orders.links[0]?.url && router.get(orders.links[0].url)"
                    >
                        ← Sebelumnya
                    </Button>
                    <span class="shrink-0 text-sm font-semibold text-gray-600 whitespace-nowrap">
                        {{ orders.current_page }} / {{ orders.last_page }}
                    </span>
                    <Button
                        variant="outline"
                        size="sm"
                        class="h-10 rounded-xl flex-1"
                        :disabled="!orders.links[orders.links.length - 1]?.url"
                        @click="orders.links[orders.links.length - 1]?.url && router.get(orders.links[orders.links.length - 1].url)"
                    >
                        Berikutnya →
                    </Button>
                </div>

                <!-- Desktop: Full numbered pagination -->
                <div class="hidden md:flex justify-center">
                    <div class="flex items-center gap-1 bg-white p-2 rounded-full border shadow-sm flex-wrap justify-center">
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
        </div>

        <Dialog :open="isBulkStatusDialogOpen" @update:open="val => { if (!val) isBulkStatusDialogOpen = false; }">
            <DialogContent class="sm:max-w-[420px]">
                <DialogHeader>
                    <DialogTitle>
                        {{ bulkStatusTarget === 'selesai' ? 'Tandai Pesanan Selesai' : 'Tandai Pesanan Diproses' }}
                    </DialogTitle>
                    <DialogDescription>
                        Aksi ini akan memperbarui {{ selectedOrderIds.length }} pesanan yang dipilih.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 pt-2">
                    <Button variant="outline" @click="isBulkStatusDialogOpen = false">Batal</Button>
                    <Button @click="executeBulkStatusUpdate" :class="bulkStatusTarget === 'selesai' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-blue-600 hover:bg-blue-700'">
                        Lanjutkan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
