<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { 
    PlusIcon, 
    TrashIcon, 
    WalletIcon, 
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

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

// Filter States
const categoryFilter = ref(props.filters.category || '');
const dateFromFilter = ref(props.filters.date_from || '');
const dateToFilter = ref(props.filters.date_to || '');

// Automatic search
let searchTimeout: any;
const triggerSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('expenses.index'), {  
            category: categoryFilter.value,
            date_from: dateFromFilter.value,
            date_to: dateToFilter.value
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

watch([categoryFilter, dateFromFilter, dateToFilter], triggerSearch);

const formatRupiah = (value: number | string) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(value));
};

// Modal States
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

const deleteExpense = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus catatan pengeluaran ini?')) {
        router.delete(route('expenses.destroy', id), {
            preserveScroll: true,
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
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border shadow-sm overflow-hidden whitespace-nowrap overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600 font-medium border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-3">Tanggal</th>
                            <th class="px-6 py-3">Kategori</th>
                            <th class="px-6 py-3">Deskripsi</th>
                            <th class="px-6 py-3 text-right">Nominal (Rp)</th>
                            <th class="px-6 py-3">Dicatat Oleh</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in expenses.data" :key="item.id" class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ item.expense_date }}</td>
                            <td class="px-6 py-4">
                                <Badge variant="outline" :class="getCategoryColor(item.category)">
                                    {{ item.category_label }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-gray-900 truncate max-w-sm" :title="item.description">{{ item.description }}</p>
                                <p v-if="item.notes" class="text-xs text-gray-500 truncate max-w-sm mt-1">{{ item.notes }}</p>
                            </td>
                            <td class="px-6 py-4 font-bold text-gray-900 text-right">{{ formatRupiah(item.amount) }}</td>
                            <td class="px-6 py-4 text-xs text-gray-500">{{ item.user_name }}</td>
                            <td class="px-6 py-4 text-right">
                                <Button variant="destructive" size="sm" class="h-8 shadow-sm" @click="deleteExpense(item.id)">
                                    <TrashIcon class="h-3 w-3" />
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="expenses.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <p class="font-medium">Tidak ada catatan pengeluaran.</p>
                                <p class="text-sm">Coba sesuaikan filter pencarian di atas.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-between items-center bg-white px-4 py-3 rounded-xl border shadow-sm" v-if="expenses.total > 0">
                <div class="text-sm text-gray-500">
                    Menampilkan <span class="font-medium text-gray-900">{{ expenses.from }}</span> - <span class="font-medium text-gray-900">{{ expenses.to }}</span> dari <span class="font-medium text-gray-900">{{ expenses.total }}</span> riwayat
                </div>
                <div class="flex space-x-2" v-if="expenses.last_page > 1">
                    <Button v-if="expenses.links[0].url" variant="outline" size="sm" @click="router.get(expenses.links[0].url)" :disabled="!expenses.links[0].url">Prev</Button>
                    <Button v-if="expenses.links[expenses.links.length - 1].url" variant="outline" size="sm" @click="router.get(expenses.links[expenses.links.length - 1].url)" :disabled="!expenses.links[expenses.links.length - 1].url">Next</Button>
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
                        <Input id="amount" type="number" step="0.01" v-model="formCreate.amount" required min="1" class="text-lg font-medium" />
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
    </AppLayout>
</template>
