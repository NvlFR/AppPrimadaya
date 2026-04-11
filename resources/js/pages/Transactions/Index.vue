<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { PlusIcon, EyeIcon, PrinterIcon, Loader2, TrashIcon, TimerReset, CheckCheck } from 'lucide-vue-next';
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import { useFormatRupiah } from '@/composables/useFormatRupiah';

interface Transaction {
    id: number;
    transaction_number: string;
    customer_name: string;
    kasir_name: string;
    total: string | number;
    payment_method: string;
    status: string;
    status_label: string;
    created_at: string;
}

const props = defineProps<{
    transactions: {
        data: Transaction[];
        links: any[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search?: string;
        status?: string;
        date_from?: string;
        date_to?: string;
    };
}>();

const { formatRupiah } = useFormatRupiah();
const page = usePage();
const isAdmin = computed(() => (page.props.auth as any)?.role === 'admin');
const selectedTransactionIds = ref<number[]>([]);
const isBulkDeleteDialogOpen = ref(false);

const isTransactionSelected = (id: number) => selectedTransactionIds.value.includes(id);
const toggleTransactionSelection = (id: number, checked: boolean | string) => {
    const nextChecked = checked === true || checked === 'indeterminate';
    if (nextChecked) {
        if (!isTransactionSelected(id)) {
            selectedTransactionIds.value = [...selectedTransactionIds.value, id];
        }
        return;
    }

    selectedTransactionIds.value = selectedTransactionIds.value.filter(transactionId => transactionId !== id);
};

const clearSelectedTransactions = () => {
    selectedTransactionIds.value = [];
};

const openBulkDeleteDialog = () => {
    if (selectedTransactionIds.value.length === 0) return;
    isBulkDeleteDialogOpen.value = true;
};

const executeBulkDelete = () => {
    if (selectedTransactionIds.value.length === 0) return;

    router.delete(route('transactions.bulk-destroy'), {
        data: {
            transaction_ids: selectedTransactionIds.value,
        },
        preserveScroll: true,
        onSuccess: () => {
            isBulkDeleteDialogOpen.value = false;
            clearSelectedTransactions();
            router.get(route('transactions.index'), {
                search: search.value,
                status: statusFilter.value,
                date_from: dateFromFilter.value,
                date_to: dateToFilter.value,
            }, {
                preserveState: true,
                replace: true,
            });
        },
    });
};

// ============================================================
// Filter States
// ============================================================
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const dateFromFilter = ref(props.filters.date_from || '');
const dateToFilter = ref(props.filters.date_to || '');

// Auto-search dengan debounce — reset ke page 1 saat filter berubah
let searchTimeout: ReturnType<typeof setTimeout>;
const triggerSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        // Reset infinite scroll state saat filter berubah
        localData.value = [];
        currentPage.value = 1;
        clearSelectedTransactions();

        router.get(route('transactions.index'), {
            search: search.value,
            status: statusFilter.value,
            date_from: dateFromFilter.value,
            date_to: dateToFilter.value,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
};

watch([search, statusFilter, dateFromFilter, dateToFilter], triggerSearch);

// ============================================================
// Infinite Scroll — Intersection Observer (Issue #26)
// Strategi: simpan data yang sudah dimuat di localData,
// append halaman berikutnya saat sentinel masuk viewport.
// ============================================================
const localData = ref<Transaction[]>([...props.transactions.data]);
const currentPage = ref(props.transactions.current_page);
const isLoadingMore = ref(false);
const sentinelRef = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

/**
 * Memuat halaman berikutnya dari server dan append ke localData.
 * Menggunakan Inertia preserveState agar posisi scroll tidak berubah.
 */
const loadNextPage = () => {
    // Jangan muat jika sedang loading atau sudah di halaman terakhir
    if (isLoadingMore.value || currentPage.value >= props.transactions.last_page) return;

    isLoadingMore.value = true;
    const nextPage = currentPage.value + 1;

    router.get(route('transactions.index'), {
        search: search.value,
        status: statusFilter.value,
        date_from: dateFromFilter.value,
        date_to: dateToFilter.value,
        page: nextPage,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onSuccess: () => {
            // Append data baru ke list lokal
            localData.value = [...localData.value, ...props.transactions.data];
            currentPage.value = nextPage;
            isLoadingMore.value = false;
        },
        onError: () => {
            isLoadingMore.value = false;
        },
    });
};

// Saat props.transactions berubah (karena filter direction), sinkronkan ulang localData
watch(() => props.transactions.data, (newData) => {
    // Jika ini bukan append (page == 1 atau search baru), reset localData
    if (props.transactions.current_page === 1) {
        localData.value = [...newData];
        currentPage.value = 1;
    }
}, { deep: true });

// Setup Intersection Observer untuk memantau sentinel element di bawah tabel
onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        const sentinel = entries[0];
        if (sentinel.isIntersecting) {
            loadNextPage();
        }
    }, {
        rootMargin: '200px', // Pra-muat 200px sebelum sentinel terlihat
        threshold: 0.1,
    });

    if (sentinelRef.value) observer.observe(sentinelRef.value);
});

onUnmounted(() => {
    observer?.disconnect();
});

// ============================================================
// Utility helpers
// ============================================================
const getStatusColor = (status: string) => {
    switch (status) {
        case 'selesai':  return 'bg-green-100 text-green-800 border-green-200';
        case 'diambil':  return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'diproses': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'pending':
        default:         return 'bg-orange-100 text-orange-800 border-orange-200';
    }
};

const getPaymentLabel = (method: string) => {
    const map: Record<string, string> = {
        cash: 'Tunai',
        qris: 'QRIS',
        transfer: 'Transfer',
    };
    return map[method] ?? method.toUpperCase();
};

const downloadPdf = (id: number) => {
    window.open(route('transactions.pdf', id), '_blank');
};

// Hitung apakah masih ada data yang belum dimuat
const hasMore = () => currentPage.value < props.transactions.last_page;
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: route('dashboard') }, { title: 'Riwayat ', href: route('transactions.index') }]">
        <template #header-actions>
            <Link :href="route('transactions.create')">
                <Button size="sm" class="bg-blue-600 hover:bg-blue-700 shadow-sm">
                    <PlusIcon class="h-4 w-4 mr-2" /> Transaksi
                </Button>
            </Link>
        </template>
        <Head title="Riwayat Transaksi" />

        <div class="mx-auto flex w-full max-w-7xl flex-col gap-5 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Riwayat Transaksi</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Pantau semua transaksi pesanan beserta status pengerjaannya.</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 bg-white p-4 rounded-xl border shadow-sm items-center">
                <div class="flex-1 min-w-0">
                    <Input v-model="search" type="search" placeholder="Cari No. Transaksi (TRX-)..." />
                </div>
                <select
                    v-model="statusFilter"
                    class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring sm:w-[150px]"
                >
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="diproses">Diproses</option>
                    <option value="selesai">Selesai</option>
                    <option value="diambil">Diambil</option>
                </select>
                <div class="flex w-full items-center gap-2 sm:w-auto">
                    <span class="text-sm text-gray-500 whitespace-nowrap">Dari:</span>
                    <Input type="date" v-model="dateFromFilter" class="h-9 w-full text-sm sm:w-[140px]" />
                </div>
                <div class="flex w-full items-center gap-2 sm:w-auto">
                    <span class="text-sm text-gray-500 whitespace-nowrap">s/d:</span>
                    <Input type="date" v-model="dateToFilter" class="h-9 w-full text-sm sm:w-[140px]" />
                </div>
            </div>

            <div v-if="isAdmin && selectedTransactionIds.length > 0" class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between bg-slate-900 text-white rounded-2xl px-4 py-3 shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 rounded-full bg-white/10 flex items-center justify-center">
                        <CheckCheck class="h-4 w-4" />
                    </div>
                    <div>
                        <p class="font-semibold">{{ selectedTransactionIds.length }} transaksi dipilih</p>
                        <p class="text-xs text-slate-300">Admin bisa menghapus beberapa transaksi sekaligus.</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" class="border-white/20 bg-white/5 text-white hover:bg-white/10 hover:text-white" @click="clearSelectedTransactions">
                        <TimerReset class="mr-2 h-4 w-4" />
                        Batal
                    </Button>
                    <Button variant="destructive" class="bg-red-600 hover:bg-red-700" @click="openBulkDeleteDialog">
                        <TrashIcon class="mr-2 h-4 w-4" />
                        Hapus Terpilih
                    </Button>
                </div>
            </div>

            <!-- Summary Info -->
            <div v-if="transactions.total > 0" class="text-sm text-gray-500">
                Menampilkan <span class="font-semibold text-gray-800">{{ localData.length }}</span> dari <span class="font-semibold text-gray-800">{{ transactions.total }}</span> transaksi
            </div>

            <div class="mobile-data-list">
                <div v-for="item in localData" :key="`transaction-mobile-${item.id}`" class="mobile-data-card space-y-3" :class="isAdmin && isTransactionSelected(item.id) ? 'ring-2 ring-blue-200' : ''">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-xs font-bold text-gray-900">{{ item.transaction_number }}</p>
                            <p class="mt-1 text-sm font-medium text-gray-900 break-words">{{ item.customer_name }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ item.created_at }}</p>
                        </div>
                        <div v-if="isAdmin" class="shrink-0 pt-1">
                            <Checkbox
                                :model-value="isTransactionSelected(item.id)"
                                @update:model-value="checked => toggleTransactionSelection(item.id, checked)"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">Kasir</p>
                            <p class="mt-1 text-gray-600">{{ item.kasir_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">Metode</p>
                            <p class="mt-1 text-gray-600">{{ getPaymentLabel(item.payment_method) }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">Total Bayar</p>
                            <p class="mt-1 font-semibold text-gray-900">{{ formatRupiah(item.total) }}</p>
                        </div>
                        <Badge variant="outline" :class="getStatusColor(item.status)">
                            {{ item.status_label }}
                        </Badge>
                    </div>

                    <div class="flex flex-wrap gap-2 pt-1">
                        <Button variant="outline" size="sm" class="h-8 shadow-sm text-gray-600" title="Cetak PDF" @click="downloadPdf(item.id)">
                            <PrinterIcon class="h-3.5 w-3.5 mr-1" /> PDF
                        </Button>
                        <Link :href="route('transactions.show', item.id)">
                            <Button variant="outline" size="sm" class="h-8 shadow-sm text-blue-600 border-blue-200 hover:bg-blue-50">
                                <EyeIcon class="h-3.5 w-3.5 mr-1" /> Detail
                            </Button>
                        </Link>
                    </div>
                </div>

                <div v-if="localData.length === 0" class="mobile-data-card px-6 py-16 text-center text-gray-400">
                    <PrinterIcon class="h-10 w-10 mx-auto mb-3 text-gray-200" />
                    <p class="font-medium text-gray-600">Tidak ada transaksi ditemukan.</p>
                    <p class="text-sm mt-1">Coba sesuaikan filter pencarian di atas.</p>
                </div>
            </div>

            <!-- Table -->
            <div class="data-table-shell hidden md:block">
                <div class="data-table-scroll">
                    <table class="data-table">
                        <thead class="bg-gray-50 text-gray-600 font-medium border-b border-gray-100 whitespace-nowrap">
                            <tr>
                                <th v-if="isAdmin" class="px-5 py-3 w-12 text-center">
                                    <span class="sr-only">Pilih transaksi</span>
                                </th>
                                <th class="px-5 py-3">No. Transaksi</th>
                                <th class="px-5 py-3">Pelanggan</th>
                                <th class="px-5 py-3">Kasir</th>
                                <th class="px-5 py-3 text-right">Total Bayar</th>
                                <th class="px-5 py-3">Metode</th>
                                <th class="px-5 py-3 text-center">Status</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in localData" :key="item.id" class="hover:bg-gray-50 transition-colors"
                                :class="isAdmin && isTransactionSelected(item.id) ? 'bg-blue-50/50' : ''">
                                <td v-if="isAdmin" class="px-5 py-4 text-center align-middle">
                                    <Checkbox
                                        :model-value="isTransactionSelected(item.id)"
                                        @update:model-value="checked => toggleTransactionSelection(item.id, checked)"
                                    />
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-bold text-gray-900 font-mono text-xs">{{ item.transaction_number }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ item.created_at }}</p>
                                </td>
                                <td class="px-5 py-4 font-medium text-gray-800">{{ item.customer_name }}</td>
                                <td class="px-5 py-4 text-gray-500">{{ item.kasir_name }}</td>
                                <td class="px-5 py-4 text-right">
                                    <span class="font-semibold text-gray-900">{{ formatRupiah(item.total) }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-xs font-medium uppercase tracking-wide text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                        {{ getPaymentLabel(item.payment_method) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <Badge variant="outline" :class="getStatusColor(item.status)">
                                        {{ item.status_label }}
                                    </Badge>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button variant="outline" size="sm" class="h-8 shadow-sm text-gray-600" title="Cetak PDF" @click="downloadPdf(item.id)">
                                            <PrinterIcon class="h-3.5 w-3.5" />
                                        </Button>
                                        <Link :href="route('transactions.show', item.id)">
                                            <Button variant="outline" size="sm" class="h-8 shadow-sm text-blue-600 border-blue-200 hover:bg-blue-50">
                                                <EyeIcon class="h-3.5 w-3.5 mr-1" /> Detail
                                            </Button>
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="localData.length === 0">
                                <td :colspan="isAdmin ? 8 : 7" class="px-6 py-16 text-center text-gray-400">
                                    <PrinterIcon class="h-10 w-10 mx-auto mb-3 text-gray-200" />
                                    <p class="font-medium text-gray-600">Tidak ada transaksi ditemukan.</p>
                                    <p class="text-sm mt-1">Coba sesuaikan filter pencarian di atas.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sentinel element untuk Intersection Observer (Issue #26) -->
            <!-- Elemen kecil ini dipantau — saat terlihat di viewport, muat halaman berikutnya -->
            <div ref="sentinelRef" class="py-1">
                <!-- Loading spinner saat sedang muat data berikutnya -->
                <div v-if="isLoadingMore" class="flex items-center justify-center gap-2 py-4 text-gray-400">
                    <Loader2 class="h-5 w-5 animate-spin" />
                    <span class="text-sm">Memuat lebih banyak...</span>
                </div>
                <!-- Indikator akhir data -->
                <div
                    v-else-if="localData.length > 0 && !hasMore()"
                    class="text-center text-xs text-gray-300 py-4"
                >
                    — Semua data telah ditampilkan ({{ localData.length }} transaksi) —
                </div>
            </div>

            <Dialog :open="isBulkDeleteDialogOpen" @update:open="val => { if (!val) isBulkDeleteDialogOpen = false; }">
                <DialogContent class="sm:max-w-[420px]">
                    <DialogHeader>
                        <DialogTitle>Hapus Transaksi Terpilih</DialogTitle>
                        <DialogDescription>
                            Aksi ini akan menghapus {{ selectedTransactionIds.length }} transaksi secara permanen beserta file terkait.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter class="gap-2 pt-2">
                        <Button variant="outline" @click="isBulkDeleteDialogOpen = false">Batal</Button>
                        <Button variant="destructive" @click="executeBulkDelete">Ya, Hapus</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
