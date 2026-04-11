<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { 
    PlusIcon, 
    ArrowDownUpIcon, 
    AlertTriangleIcon,
    HistoryIcon,
    Box
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Stock {
    id: number;
    name: string;
    category: string;
    unit: string;
    current_qty: number | string;
    min_qty: number | string;
    is_low_stock: boolean;
    notes: string | null;
}

const props = defineProps<{
    stocks: {
        data: Stock[];
        links: any[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    low_stock_count: number;
    filters: { 
        search?: string;
        category?: string;
    };
}>();

// Filter States
const search = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category || '');

// Automatic search
let searchTimeout: any;
const triggerSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('stocks.index'), { 
            search: search.value, 
            category: categoryFilter.value 
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

watch([search, categoryFilter], triggerSearch);

// Modal Create Item
const isCreateModalOpen = ref(false);
const formCreate = useForm({
    name: '',
    category: 'kertas',
    unit: 'pcs',
    current_qty: 0,
    min_qty: 10,
    notes: '',
});

const openCreateModal = () => {
    formCreate.reset();
    isCreateModalOpen.value = true;
};

const saveNewItem = () => {
    formCreate.post(route('stocks.store'), {
        onSuccess: () => { isCreateModalOpen.value = false; }
    });
};

// Modal Update Stock (Masuk/Keluar)
const isUpdateModalOpen = ref(false);
const selectedStock = ref<Stock | null>(null);
const formUpdate = useForm({
    type: 'masuk',
    qty: 0,
    reason: '',
    reference_transaction_number: '',
    notes: '',
});

const openUpdateModal = (stock: Stock) => {
    selectedStock.value = stock;
    formUpdate.reset();
    formUpdate.type = 'masuk';
    formUpdate.qty = 0;
    formUpdate.reason = '';
    formUpdate.reference_transaction_number = '';
    isUpdateModalOpen.value = true;
};

const submitUpdateStock = () => {
    if (selectedStock.value) {
        formUpdate.patch(route('stocks.update', selectedStock.value.id), {
            onSuccess: () => { isUpdateModalOpen.value = false; }
        });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: route('dashboard') }, { title: 'Manage Stok', href: route('stocks.index') }]">
        <template #header-actions>
            <Button @click="openCreateModal" size="sm" class="bg-blue-600 hover:bg-blue-700">
                <PlusIcon class="h-4 w-4 mr-2" /> Item Baru
            </Button>
        </template>
        <Head title="Manajemen Stok Barang" />

        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2"><Box class="h-6 w-6 text-primary"/>Manajeman Stok</h1>
                    <!-- <p class="text-sm text-gray-500">Kelola stok barang dan pantau pergerakan inventaris toko.</p> -->
                </div>
                <Link :href="route('stocks.logs')" class="w-full sm:w-auto">
                    <Button variant="outline" class="w-full border-gray-300 bg-white text-gray-700 hover:bg-gray-50 sm:w-auto">
                        <HistoryIcon class="h-4 w-4 mr-2" /> Lihat Riwayat Keluar-Masuk
                    </Button>
                </Link>
            </div>

            <div v-if="low_stock_count > 0" class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 flex items-start space-x-3 shadow-sm">
                <AlertTriangleIcon class="h-5 w-5 text-red-600 flex-shrink-0 mt-0.5" />
                <div>
                    <h3 class="font-bold">Peringatan Stok Tipis!</h3>
                    <p class="text-sm">Terdapat {{ low_stock_count }} item barang yang jumlah stoknya berada di bawah batas minimum (Restock Alert). Segera periksa dan lakukan pengadaan barang.</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-4 bg-white p-4 rounded-xl border shadow-sm items-center">
                <div class="flex-1 min-w-0">
                    <Input v-model="search" type="search" placeholder="Cari nama barang..." />
                </div>
                <select v-model="categoryFilter" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring sm:w-[180px]">
                    <option value="">Semua Kategori</option>
                    <option value="kertas">Kertas</option>
                    <option value="tinta">Tinta</option>
                    <option value="bahan">Bahan Baku</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </div>

            <div class="mobile-data-list">
                <div v-for="item in stocks.data" :key="`stock-mobile-${item.id}`" class="mobile-data-card space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-base font-semibold text-gray-900 break-words">{{ item.name }}</p>
                            <p class="mt-1 text-sm capitalize text-gray-500">{{ item.category }}</p>
                            <p class="mt-2 text-xs text-gray-500 break-words">{{ item.notes || 'Tidak ada catatan' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold" :class="item.is_low_stock ? 'text-red-600' : 'text-gray-900'">{{ item.current_qty }}</p>
                            <p class="text-xs text-gray-500">{{ item.unit }}</p>
                            <Badge v-if="item.is_low_stock" variant="destructive" class="mt-2 text-[10px] px-1 py-0 h-4">Perlu Restock</Badge>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">Batas Minimum</p>
                            <p class="mt-1 font-medium text-gray-900">{{ item.min_qty }} {{ item.unit }}</p>
                        </div>
                    </div>

                    <div class="pt-1">
                        <Button variant="outline" size="sm" class="h-8 shadow-sm border-blue-200 text-blue-700 hover:bg-blue-50" @click="openUpdateModal(item)">
                            <ArrowDownUpIcon class="h-3 w-3 mr-2" /> Keluar / Masuk
                        </Button>
                    </div>
                </div>

                <div v-if="stocks.data.length === 0" class="mobile-data-card py-12 text-center text-sm text-gray-500">
                    <p class="font-medium">Tidak ada barang tercatat di stok.</p>
                </div>
            </div>

            <!-- Table -->
            <div class="data-table-shell hidden md:block">
                <div class="data-table-scroll">
                <table class="data-table">
                    <thead class="bg-gray-50 text-gray-600 font-medium border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-3">Nama Barang</th>
                            <th class="px-6 py-3">Kategori</th>
                            <th class="px-6 py-3 text-center">Stok Saat Ini</th>
                            <th class="px-6 py-3 text-center">Batas Minimum</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in stocks.data" :key="item.id" class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-900">{{ item.name }}</p>
                                <p class="text-xs text-gray-500 mt-1 truncate max-w-xs">{{ item.notes || 'Tidak ada catatan' }}</p>
                            </td>
                            <td class="px-6 py-4 capitalize text-gray-600">{{ item.category }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="font-bold text-lg mr-1" :class="item.is_low_stock ? 'text-red-600' : 'text-gray-900'">{{ item.current_qty }}</span>
                                <span class="text-gray-500 text-xs">{{ item.unit }}</span>
                                <div v-if="item.is_low_stock" class="mt-1">
                                    <Badge variant="destructive" class="text-[10px] px-1 py-0 h-4">Perlu Restock</Badge>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-gray-500">{{ item.min_qty }} {{ item.unit }}</td>
                            <td class="px-6 py-4 text-right">
                                <Button variant="outline" size="sm" class="h-8 shadow-sm border-blue-200 text-blue-700 hover:bg-blue-50" @click="openUpdateModal(item)">
                                    <ArrowDownUpIcon class="h-3 w-3 mr-2" /> Keluar / Masuk
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="stocks.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <p class="font-medium">Tidak ada barang tercatat di stok.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="flex justify-between items-center bg-white px-4 py-3 rounded-xl border shadow-sm" v-if="stocks.total > 0">
                <div class="text-sm text-gray-500">
                    Menampilkan <span class="font-medium text-gray-900">{{ stocks.from }}</span> - <span class="font-medium text-gray-900">{{ stocks.to }}</span> dari <span class="font-medium text-gray-900">{{ stocks.total }}</span> item
                </div>
                <div class="flex space-x-2" v-if="stocks.last_page > 1">
                    <Button 
                        v-if="stocks.links?.[0]?.url" variant="outline" size="sm"
                        @click="router.get(stocks.links[0].url)" :disabled="!stocks.links[0].url">Prev</Button>
                    <Button 
                        v-if="stocks.links?.[stocks.links.length - 1]?.url" variant="outline" size="sm"
                        @click="router.get(stocks.links[stocks.links.length - 1].url)" :disabled="!stocks.links[stocks.links.length - 1].url">Next</Button>
                </div>
            </div>
        </div>

        <!-- Create Item Modal -->
        <Dialog :open="isCreateModalOpen" @update:open="val => { if (!val) isCreateModalOpen = false; }">
            <DialogContent class="sm:max-w-[450px]">
                <DialogHeader>
                    <DialogTitle>Tambah Item Stok Baru</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="saveNewItem" class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="name">Nama Barang</Label>
                        <Input id="name" v-model="formCreate.name" required placeholder="Cth: Kertas A4 HVS 80gsm SIDU" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="category">Kategori</Label>
                            <select id="category" v-model="formCreate.category" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus:ring-1 focus:ring-blue-600">
                                <option value="kertas">Kertas</option>
                                <option value="tinta">Tinta</option>
                                <option value="bahan">Bahan Baku</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="unit">Satuan</Label>
                            <Input id="unit" v-model="formCreate.unit" required placeholder="Cth: Rim, Botol, Pcs" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="current_qty">Stok Awal</Label>
                            <Input id="current_qty" type="number" step="0.01" v-model="formCreate.current_qty" required min="0" />
                        </div>
                        <div class="space-y-2">
                            <Label for="min_qty">Batas Minimum (Alert)</Label>
                            <Input id="min_qty" type="number" step="0.01" v-model="formCreate.min_qty" required min="0" />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="notes">Catatan Tambahan</Label>
                        <Input id="notes" v-model="formCreate.notes" placeholder="Opsional" />
                    </div>
                    <DialogFooter class="pt-4">
                        <Button type="button" variant="outline" @click="isCreateModalOpen = false">Batal</Button>
                        <Button type="submit" :disabled="formCreate.processing" class="bg-blue-600">Simpan Barang</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Update QTY Modal -->
        <Dialog :open="isUpdateModalOpen" @update:open="val => { if (!val) isUpdateModalOpen = false; }">
            <DialogContent class="sm:max-w-[400px]">
                <DialogHeader>
                    <DialogTitle>Update Stok Barang</DialogTitle>
                </DialogHeader>
                <div v-if="selectedStock" class="bg-gray-50 border rounded-md p-3 mb-2 flex justify-between items-center">
                    <div>
                        <p class="font-bold text-gray-900">{{ selectedStock.name }}</p>
                        <p class="text-xs text-gray-500">Stok saat ini: {{ selectedStock.current_qty }} {{ selectedStock.unit }}</p>
                    </div>
                </div>
                <form @submit.prevent="submitUpdateStock" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 mt-2 sm:grid-cols-2">
                        <div 
                            @click="formUpdate.type = 'masuk'"
                            class="border rounded-md p-3 text-center cursor-pointer transition-all"
                            :class="formUpdate.type === 'masuk' ? 'bg-green-50 border-green-500 text-green-700 ring-1 ring-green-500' : 'hover:bg-gray-50'"
                        >
                            <p class="font-bold text-sm">Masuk</p>
                            <p class="text-[10px] opacity-70">Penambahan Stok</p>
                        </div>
                        <div 
                            @click="formUpdate.type = 'keluar'"
                            class="border rounded-md p-3 text-center cursor-pointer transition-all"
                            :class="formUpdate.type === 'keluar' ? 'bg-orange-50 border-orange-500 text-orange-700 ring-1 ring-orange-500' : 'hover:bg-gray-50'"
                        >
                            <p class="font-bold text-sm">Keluar</p>
                            <p class="text-[10px] opacity-70">Pemotongan Stok</p>
                        </div>
                    </div>

                    <div class="space-y-2 mt-4">
                        <Label for="update_qty">Jumlah {{ formUpdate.type === 'masuk' ? 'Masuk' : 'Keluar' }}</Label>
                        <div class="flex items-center space-x-2">
                            <Input id="update_qty" type="number" step="0.01" v-model="formUpdate.qty" required min="0.01" class="text-lg font-medium" />
                            <span class="text-gray-500 font-medium">{{ selectedStock?.unit }}</span>
                        </div>
                        <span class="text-xs text-red-500" v-if="formUpdate.errors.qty">{{ formUpdate.errors.qty }}</span>
                    </div>

                    <div v-if="formUpdate.type === 'keluar'" class="space-y-4 rounded-xl border border-orange-200 bg-orange-50/70 p-4">
                        <div class="space-y-2">
                            <Label for="reason">Alasan Stok Keluar</Label>
                            <select id="reason" v-model="formUpdate.reason" class="flex h-9 w-full rounded-md border border-input bg-white px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus:ring-1 focus:ring-orange-500">
                                <option value="">Pilih alasan</option>
                                <option value="rusak">Rusak</option>
                                <option value="kadaluarsa">Kadaluarsa</option>
                                <option value="salah_input">Salah Input</option>
                                <option value="koreksi">Koreksi</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                            <span class="text-xs text-red-500" v-if="formUpdate.errors.reason">{{ formUpdate.errors.reason }}</span>
                        </div>
                        <div class="space-y-2">
                            <Label for="reference_transaction_number">Nomor Transaksi Referensi <span class="text-gray-400">(opsional)</span></Label>
                            <Input
                                id="reference_transaction_number"
                                v-model="formUpdate.reference_transaction_number"
                                placeholder="TRX-20260410-0001"
                                class="uppercase"
                            />
                            <span class="text-xs text-red-500" v-if="formUpdate.errors.reference_transaction_number">{{ formUpdate.errors.reference_transaction_number }}</span>
                            <p class="text-xs text-gray-500">Isi jika stok keluar terkait transaksi tertentu supaya riwayatnya bisa dilacak.</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="update_notes">Keterangan Aktivitas</Label>
                        <Input id="update_notes" v-model="formUpdate.notes" placeholder="Cth: Restock dari supplier / Dipakai produksi nota TRX-0001" required />
                    </div>

                    <DialogFooter class="pt-4">
                        <Button type="button" variant="outline" @click="isUpdateModalOpen = false">Batal</Button>
                        <Button type="submit" :disabled="formUpdate.processing" :class="formUpdate.type === 'masuk' ? 'bg-green-600 hover:bg-green-700' : 'bg-orange-600 hover:bg-orange-700'">
                            Simpan Perubahan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
