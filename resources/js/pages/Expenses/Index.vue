<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { PlusIcon, TrashIcon, WalletIcon } from 'lucide-vue-next';
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Skeleton } from '@/components/ui/skeleton';
import { useFormatRupiah } from '@/composables/useFormatRupiah';

interface Expense {
    id: number;
    category: string;
    category_label: string;
    description: string;
    amount: string | number;
    expense_date: string;
    user_name: string;
    notes: string | null;
}

const props = defineProps<{
    expenses: {
        data: Expense[];
        links: any[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    total_filtered: string | number;
    categories: Record<string, string>;
    filters: {
        category?: string;
        date_from?: string;
        date_to?: string;
    };
}>();

// ============================================================
// Filter States
// ============================================================
const categoryFilter = ref(props.filters.category || '');
const dateFromFilter = ref(props.filters.date_from || '');
const dateToFilter = ref(props.filters.date_to || '');

let searchTimeout: ReturnType<typeof setTimeout>;
const triggerSearch = () => {
    clearTimeout(searchTimeout);
    // Reset infinite scroll saat filter berubah
    localData.value = [];
    currentPage.value = 1;
    searchTimeout = setTimeout(() => {
        router.get(route('expenses.index'), {
            category: categoryFilter.value,
            date_from: dateFromFilter.value,
            date_to: dateToFilter.value,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

watch([categoryFilter, dateFromFilter, dateToFilter], triggerSearch);

const { formatRupiah } = useFormatRupiah();

// ============================================================
// Infinite Scroll — Intersection Observer (Issue #26)
// ============================================================
const localData = ref<Expense[]>([...props.expenses.data]);
const currentPage = ref(props.expenses.current_page);
const isLoadingMore = ref(false);
const sentinelRef = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

/**
 * Memuat halaman berikutnya dan append ke localData.
 */
const loadNextPage = () => {
    if (isLoadingMore.value || currentPage.value >= props.expenses.last_page) return;

    isLoadingMore.value = true;
    const nextPage = currentPage.value + 1;

    router.get(route('expenses.index'), {
        category: categoryFilter.value,
        date_from: dateFromFilter.value,
        date_to: dateToFilter.value,
        page: nextPage,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onSuccess: () => {
            localData.value = [...localData.value, ...props.expenses.data];
            currentPage.value = nextPage;
            isLoadingMore.value = false;
        },
        onError: () => { isLoadingMore.value = false; },
    });
};

// Sinkronkan ulang saat filter berubah (page 1 = reset)
watch(() => props.expenses.data, (newData) => {
    if (props.expenses.current_page === 1) {
        localData.value = [...newData];
        currentPage.value = 1;
    }
}, { deep: true });

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) loadNextPage();
    }, { rootMargin: '200px', threshold: 0.1 });

    if (sentinelRef.value) observer.observe(sentinelRef.value);
});

onUnmounted(() => observer?.disconnect());

const hasMore = () => currentPage.value < props.expenses.last_page;

// ============================================================
// Modal Catat Pengeluaran
// ============================================================
const isCreateModalOpen = ref(false);
const formCreate = useForm({
    category: 'bahan',
    description: '',
    amount: 0,
    expense_date: new Date().toISOString().split('T')[0],
    notes: '',
});

const openCreateModal = () => {
    formCreate.reset();
    formCreate.expense_date = new Date().toISOString().split('T')[0];
    isCreateModalOpen.value = true;
};

const saveExpense = () => {
    formCreate.post(route('expenses.store'), {
        onSuccess: () => { isCreateModalOpen.value = false; }
    });
};

// Delete Confirmation State
const isDeleteModalOpen = ref(false);
const expenseToDelete = ref<number | null>(null);

const confirmDeleteExpense = (id: number) => {
    expenseToDelete.value = id;
    isDeleteModalOpen.value = true;
};

const executeDeleteExpense = () => {
    if (expenseToDelete.value !== null) {
        router.delete(route('expenses.destroy', expenseToDelete.value), {
            preserveScroll: true,
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                expenseToDelete.value = null;
            }
        });
    }
};

const getCategoryColor = (category: string) => {
    switch(category) {
        case 'bahan': return 'bg-orange-100 text-orange-800 border-orange-200';
        case 'operasional': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'gaji': return 'bg-purple-100 text-purple-800 border-purple-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: route('dashboard') }, { title: 'Pengeluaran', href: route('expenses.index') }]">
        <Head title="Pengeluaran Operasional" />

        <div class="px-4 py-6 md:px-8 space-y-6 max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Catatan Pengeluaran</h1>
                    <p class="text-sm text-gray-500">Kelola administrasi pengeluaran kas operasional.</p>
                </div>
                <Button @click="openCreateModal" class="bg-blue-600 hover:bg-blue-700 shadow-sm">
                    <PlusIcon class="h-4 w-4 mr-2" /> Catat Pengeluaran
                </Button>
            </div>

            <!-- Summary Highlight -->
            <div class="bg-blue-600 rounded-xl p-6 text-white shadow-sm flex items-center justify-between border border-blue-700">
                <div class="flex items-center space-x-4">
                    <div class="bg-white/20 p-3 rounded-full">
                        <WalletIcon class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Total Estimasi Pengeluaran (Berdasarkan Filter)</p>
                        <p class="text-3xl font-bold">{{ formatRupiah(total_filtered) }}</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-4 bg-white p-4 rounded-xl border shadow-sm items-center">
                <select v-model="categoryFilter" class="flex h-9 w-[180px] rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="">Semua Kategori</option>
                    <option v-for="(label, key) in categories" :key="key" :value="key">
                        {{ label }}
                    </option>
                </select>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500">Dari:</span>
                    <Input type="date" v-model="dateFromFilter" class="w-[140px] h-9 text-sm" />
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500">Sampai:</span>
                    <Input type="date" v-model="dateToFilter" class="w-[140px] h-9 text-sm" />
                </div>
                <!-- Summary count -->
                <p v-if="expenses.total > 0" class="ml-auto text-sm text-gray-500">
                    Menampilkan <span class="font-semibold text-gray-800">{{ localData.length }}</span> dari <span class="font-semibold text-gray-800">{{ expenses.total }}</span> catatan
                </p>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border shadow-sm overflow-hidden whitespace-nowrap overflow-x-auto">
                <table class="data-table">
                    <thead class="bg-gray-50 text-gray-600 font-medium border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-3">Tanggal</th>
                            <th class="px-6 py-3">Kategori</th>
                            <th class="px-6 py-3 min-w-[250px]">Deskripsi</th>
                            <th class="px-6 py-3 text-right">Nominal (Rp)</th>
                            <th class="px-6 py-3">Dicatat Oleh</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in localData" :key="item.id" class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ item.expense_date }}</td>
                            <td class="px-6 py-4">
                                <Badge variant="outline" :class="getCategoryColor(item.category)">
                                    {{ item.category_label }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-[300px] whitespace-normal">
                                    <p class="text-sm font-medium text-gray-900 break-words line-clamp-2" :title="item.description">
                                        {{ item.description }}
                                    </p>
                                    <p v-if="item.notes" class="text-xs text-gray-500 break-words mt-1 italic">
                                        {{ item.notes }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-bold text-gray-900 text-right tabular-nums">{{ formatRupiah(item.amount) }}</td>
                            <td class="px-6 py-4 text-xs text-gray-500">{{ item.user_name }}</td>
                            <td class="px-6 py-4 text-right">
                                <Button variant="destructive" size="sm" class="h-8 shadow-sm" @click="confirmDeleteExpense(item.id)">
                                    <TrashIcon class="h-3 w-3" />
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="localData.length === 0 && !isLoadingMore">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <p class="font-medium">Tidak ada catatan pengeluaran.</p>
                                <p class="text-sm">Coba sesuaikan filter pencarian di atas.</p>
                            </td>
                        </tr>
                        <!-- Infinite Scroll Skeleton — Issue #26 -->
                        <template v-if="isLoadingMore">
                            <tr v-for="i in 3" :key="'skel-exp-'+i" class="animate-pulse">
                                <td class="px-6 py-4"><Skeleton class="h-4 w-20" /></td>
                                <td class="px-6 py-4"><Skeleton class="h-6 w-16 rounded-full" /></td>
                                <td class="px-6 py-4"><Skeleton class="h-4 w-40" /></td>
                                <td class="px-6 py-4"><Skeleton class="h-4 w-24 ml-auto" /></td>
                                <td class="px-6 py-4"><Skeleton class="h-4 w-12" /></td>
                                <td class="px-6 py-4 text-right"><Skeleton class="h-8 w-8 ml-auto" /></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Sentinel element untuk Intersection Observer (Issue #26) -->
            <div ref="sentinelRef" class="py-1">
                <div v-if="isLoadingMore" class="sr-only">
                    Memuat data pengeluaran...
                </div>
                <div
                    v-else-if="localData.length > 0 && !hasMore()"
                    class="text-center text-xs text-gray-300 py-4"
                >
                    — Semua data telah ditampilkan ({{ localData.length }} catatan) —
                </div>
            </div>
        </div>

        <!-- Add Expense Modal -->
        <Dialog :open="isCreateModalOpen" @update:open="val => { if (!val) isCreateModalOpen = false; }">
            <DialogContent class="sm:max-w-[450px]">
                <DialogHeader>
                    <DialogTitle>Catat Pengeluaran Baru</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="saveExpense" class="space-y-4 py-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="expense_date">Tanggal</Label>
                            <Input id="expense_date" type="date" v-model="formCreate.expense_date" required />
                        </div>
                        <div class="space-y-2">
                            <Label for="category">Kategori</Label>
                            <select id="category" v-model="formCreate.category" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus:ring-1 focus:ring-blue-600">
                                <option v-for="(label, key) in categories" :key="key" :value="key">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="amount">Nominal Pengeluaran (Rp) <span class="text-red-500">*</span></Label>
                        <Input id="amount" type="number" step="0.01" v-model="formCreate.amount" required min="1" max="999999999" class="text-lg font-medium" />
                        <p class="text-[10px] text-gray-400">Maks: Rp 999.999.999</p>
                        <span class="text-xs text-red-500" v-if="formCreate.errors.amount">{{ formCreate.errors.amount }}</span>
                    </div>

                    <div class="space-y-2">
                        <Label for="description">Deskripsi <span class="text-red-500">*</span></Label>
                        <Input id="description" v-model="formCreate.description" required placeholder="Cth: Beli tinta Epson dan Kertas HVS 10 Rim" />
                    </div>

                    <div class="space-y-2">
                        <Label for="notes">Catatan Tambahan (Opsional)</Label>
                        <textarea id="notes" v-model="formCreate.notes" class="flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-600" placeholder="Keterangan lain bila ada..."></textarea>
                    </div>

                    <DialogFooter class="pt-4">
                        <Button type="button" variant="outline" @click="isCreateModalOpen = false">Batal</Button>
                        <Button type="submit" :disabled="formCreate.processing" class="bg-blue-600 hover:bg-blue-700">Simpan Tagihan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
        <!-- Delete Confirmation Modal -->
        <Dialog :open="isDeleteModalOpen" @update:open="val => { if (!val) isDeleteModalOpen = false; }">
            <DialogContent class="sm:max-w-[400px]">
                <DialogHeader>
                    <DialogTitle>Hapus Pengeluaran</DialogTitle>
                </DialogHeader>
                <div class="py-4">
                    <p class="text-sm text-gray-500">
                        Apakah Anda yakin ingin menghapus catatan pengeluaran ini? Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="isDeleteModalOpen = false">Batal</Button>
                    <Button type="button" variant="destructive" @click="executeDeleteExpense" class="bg-red-600 hover:bg-red-700">Ya, Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
