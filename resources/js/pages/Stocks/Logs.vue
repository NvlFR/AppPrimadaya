<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    HistoryIcon,
    ArrowLeftIcon,
    ArrowUpIcon,
    ArrowDownIcon,
    FilterIcon,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface StockLog {
    id: number;
    stock_name: string;
    user_name: string;
    type: 'masuk' | 'keluar';
    qty: number | string;
    qty_before: number | string;
    qty_after: number | string;
    notes: string | null;
    created_at: string;
}

const props = defineProps<{
    logs: {
        data: StockLog[];
        links: any[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    stocks: { id: number; name: string }[];
    filters: { stock_id?: string };
}>();

// Filter state
const selectedStockId = ref(props.filters.stock_id || '');

// Debounce filter
let filterTimeout: any;
watch(selectedStockId, () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(route('stocks.logs'), {
            stock_id: selectedStockId.value,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});
</script>

<template>
    <AppLayout :breadcrumbs="[
        { title: 'Dashboard', href: route('dashboard') },
        { title: 'Manajemen Stok', href: route('stocks.index') },
        { title: 'Riwayat Keluar-Masuk', href: route('stocks.logs') }
    ]">
        <Head title="Riwayat Stok Barang" />

        <div class="px-4 py-6 md:px-8 space-y-6 max-w-7xl mx-auto">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                <div class="flex items-center space-x-4">
                    <Link :href="route('stocks.index')">
                        <Button variant="outline" size="icon" class="h-8 w-8 rounded-full">
                            <ArrowLeftIcon class="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                            <HistoryIcon class="h-6 w-6 text-primary" /> Riwayat Keluar-Masuk Stok
                        </h1>
                        <p class="text-sm text-gray-500">Log pergerakan semua barang inventaris operasional.</p>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="flex flex-wrap gap-4 bg-white p-4 rounded-xl border shadow-sm items-center">
                <FilterIcon class="h-4 w-4 text-gray-400 flex-shrink-0" />
                <div class="flex items-center gap-2">
                    <Label class="text-sm text-gray-500 font-medium whitespace-nowrap">Filter Barang:</Label>
                    <select
                        v-model="selectedStockId"
                        class="flex h-9 w-[250px] rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    >
                        <option value="">Semua Barang</option>
                        <option v-for="stock in stocks" :key="stock.id" :value="stock.id.toString()">
                            {{ stock.name }}
                        </option>
                    </select>
                </div>
                <div v-if="selectedStockId" class="ml-auto">
                    <Button variant="ghost" size="sm" class="text-gray-500 h-8" @click="selectedStockId = ''">
                        Hapus Filter
                    </Button>
                </div>
            </div>

            <!-- Table Log -->
            <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead class="bg-gray-50 text-gray-600 font-medium border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-3">Waktu</th>
                                <th class="px-6 py-3">Nama Barang</th>
                                <th class="px-6 py-3">Tipe Perubahan</th>
                                <th class="px-6 py-3 text-center">Jumlah</th>
                                <th class="px-6 py-3 text-center">Stok Sebelum</th>
                                <th class="px-6 py-3 text-center">Stok Sesudah</th>
                                <th class="px-6 py-3">Dicatat Oleh</th>
                                <th class="px-6 py-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="log in logs.data"
                                :key="log.id"
                                class="hover:bg-gray-50 transition"
                            >
                                <td class="px-6 py-4 text-xs text-gray-500 whitespace-nowrap">{{ log.created_at }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">{{ log.stock_name }}</td>
                                <td class="px-6 py-4">
                                    <Badge
                                        :class="log.type === 'masuk'
                                            ? 'bg-green-100 text-green-800 border-green-200'
                                            : 'bg-orange-100 text-orange-800 border-orange-200'"
                                        variant="outline"
                                        class="flex items-center gap-1.5 w-fit font-semibold"
                                    >
                                        <ArrowUpIcon v-if="log.type === 'masuk'" class="h-3 w-3" />
                                        <ArrowDownIcon v-else class="h-3 w-3" />
                                        {{ log.type === 'masuk' ? 'Stok Masuk' : 'Stok Keluar' }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="font-bold text-base"
                                        :class="log.type === 'masuk' ? 'text-green-600' : 'text-orange-600'"
                                    >
                                        {{ log.type === 'masuk' ? '+' : '-' }}{{ log.qty }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center text-gray-500 text-sm font-mono">{{ log.qty_before }}</td>
                                <td class="px-6 py-4 text-center text-gray-900 font-bold font-mono">{{ log.qty_after }}</td>
                                <td class="px-6 py-4 text-gray-600 text-sm whitespace-nowrap">{{ log.user_name }}</td>
                                <td class="px-6 py-4 text-gray-500 text-xs italic max-w-[200px] truncate">
                                    {{ log.notes || '-' }}
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="logs.data.length === 0">
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <HistoryIcon class="h-10 w-10 text-gray-200 mx-auto mb-3" />
                                    <p class="font-medium text-gray-500">Belum ada riwayat pergerakan stok.</p>
                                    <p class="text-sm text-gray-400 mt-1">Log akan muncul ketika ada perubahan stok masuk atau keluar.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div
                class="flex flex-col sm:flex-row justify-between items-center bg-white px-4 py-3 rounded-xl border shadow-sm gap-4"
                v-if="logs.total > 0"
            >
                <div class="text-sm text-gray-500">
                    Menampilkan
                    <span class="font-medium text-gray-900">{{ logs.from }}</span>
                    -
                    <span class="font-medium text-gray-900">{{ logs.to }}</span>
                    dari
                    <span class="font-medium text-gray-900">{{ logs.total }}</span>
                    log perubahan
                </div>
                <div class="flex space-x-2" v-if="logs.last_page > 1">
                    <Button
                        v-if="logs.links[0]?.url"
                        variant="outline"
                        size="sm"
                        @click="router.get(logs.links[0].url)"
                        :disabled="!logs.links[0]?.url"
                    >
                        &laquo; Prev
                    </Button>
                    <span class="flex items-center px-3 text-sm text-gray-600 font-medium">
                        Hal. {{ logs.current_page }} / {{ logs.last_page }}
                    </span>
                    <Button
                        v-if="logs.links[logs.links.length - 1]?.url"
                        variant="outline"
                        size="sm"
                        @click="router.get(logs.links[logs.links.length - 1].url)"
                        :disabled="!logs.links[logs.links.length - 1]?.url"
                    >
                        Next &raquo;
                    </Button>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
