<script setup lang="ts">
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

interface Customer {
    id: number;
    name: string;
    phone: string;
    address: string;
    transactions_count: number;
    created_at: string;
}

const props = defineProps<{
    customers: {
        data: Customer[];
        links: any[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    filters: { search?: string };
}>();

// Filter States
const search = ref(props.filters.search || '');

// Automatic search when typing
let searchTimeout: any;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('customers.index'), { search: search.value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

// Modal States
const isModalOpen = ref(false);
const isEditMode = ref(false);
const editingId = ref<number | null>(null);

// Form Pelanggan
const form = useForm({
    name: '',
    phone: '',
    address: '',
    notes: '',
});

const openCreateModal = () => {
    isEditMode.value = false;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (customer: Customer) => {
    isEditMode.value = true;
    editingId.value = customer.id;
    form.name = customer.name;
    form.phone = customer.phone || '';
    form.address = customer.address || '';
    // Catatan: route response awal di index nggak kirim 'notes', 
    // tapi form request support updatenya. Dikosongin dulu buat index edit, 
    // real-nya biasa nampilin di detail show.
    form.notes = ''; 
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const saveCustomer = () => {
    if (isEditMode.value && editingId.value) {
        form.put(route('customers.update', editingId.value), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('customers.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteCustomer = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus data pelanggan ini beserta semua riwayat hubungannya?')) {
        router.delete(route('customers.destroy', id));
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: route('dashboard') }, { title: 'Pelanggan', href: route('customers.index') }]">
        <Head title="Data Pelanggan" />

        <div class="px-4 py-6 md:px-8 space-y-6 max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Data Pelanggan</h1>
                    <p class="text-sm text-gray-500">Kelola informasi pelanggan dan riwayat pesanan (CRM).</p>
                </div>
                <Button @click="openCreateModal" class="bg-blue-600 hover:bg-blue-700">
                    <PlusIcon class="h-4 w-4 mr-2" /> Tambah Pelanggan
                </Button>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-4 bg-white p-4 rounded-xl border shadow-sm">
                <div class="flex-1">
                    <Input v-model="search" type="search" placeholder="Cari nama atau no. telepon..." class="max-w-md" />
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border shadow-sm overflow-hidden whitespace-nowrap overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600 font-medium">
                        <tr>
                            <th class="px-6 py-3 border-b">Nama Pelanggan</th>
                            <th class="px-6 py-3 border-b">No. Telepon / WA</th>
                            <th class="px-6 py-3 border-b">Total Transaksi</th>
                            <th class="px-6 py-3 border-b">Bergabung Sejak</th>
                            <th class="px-6 py-3 border-b text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in customers.data" :key="item.id" class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ item.name }}</td>
                            <td class="px-6 py-4">{{ item.phone || '-' }}</td>
                            <td class="px-6 py-4">
                                <Badge variant="secondary" class="bg-blue-50 text-blue-700 hover:bg-blue-100">
                                    {{ item.transactions_count }} Pesanan
                                </Badge>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ item.created_at }}</td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <Link :href="route('customers.show', item.id)">
                                    <Button variant="outline" size="sm" class="h-8 shadow-sm text-blue-600 border-blue-200 hover:bg-blue-50">
                                        <EyeIcon class="h-3 w-3 mr-1" /> Detail
                                    </Button>
                                </Link>
                                <Button variant="outline" size="sm" class="h-8 shadow-sm" @click="openEditModal(item)">
                                    <PencilIcon class="h-3 w-3" />
                                </Button>
                                <Button variant="destructive" size="sm" class="h-8 shadow-sm" @click="deleteCustomer(item.id)">
                                    <TrashIcon class="h-3 w-3" />
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="customers.data.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">Tidak ada data pelanggan ditemukan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-between items-center bg-white px-4 py-3 rounded-xl border shadow-sm" v-if="customers.total > 0">
                <div class="text-sm text-gray-500">
                    Menampilkan <span class="font-medium text-gray-900">{{ customers.from }}</span> - <span class="font-medium text-gray-900">{{ customers.to }}</span> dari <span class="font-medium text-gray-900">{{ customers.total }}</span> pelanggan
                </div>
                <div class="flex space-x-2" v-if="customers.last_page > 1">
                    <Button 
                        v-if="customers.links?.[0]?.url" 
                        variant="outline" 
                        size="sm"
                        @click="router.get(customers.links[0].url)" 
                        :disabled="!customers.links[0].url"
                    >
                        Prev
                    </Button>
                    <Button 
                        v-if="customers.links?.[customers.links.length - 1]?.url" 
                        variant="outline" 
                        size="sm"
                        @click="router.get(customers.links[customers.links.length - 1].url)"
                        :disabled="!customers.links[customers.links.length - 1].url"
                    >
                        Next
                    </Button>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <Dialog :open="isModalOpen" @update:open="val => { if (!val) closeModal(); }">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>{{ isEditMode ? 'Edit Pelanggan' : 'Tambah Pelanggan Baru' }}</DialogTitle>
                </DialogHeader>
                
                <form @submit.prevent="saveCustomer" class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="name">Nama Lengkap <span class="text-red-500">*</span></Label>
                        <Input id="name" v-model="form.name" required placeholder="Cth: PT Primadaya Sejahtera" />
                        <span class="text-xs text-red-500" v-if="form.errors.name">{{ form.errors.name }}</span>
                    </div>

                    <div class="space-y-2">
                        <Label for="phone">No. Handphone / WhatsApp</Label>
                        <Input id="phone" type="tel" v-model="form.phone" placeholder="Cth: 081234567890" />
                        <span class="text-xs text-red-500" v-if="form.errors.phone">{{ form.errors.phone }}</span>
                    </div>

                    <div class="space-y-2">
                        <Label for="address">Alamat Lengkap</Label>
                        <textarea id="address" v-model="form.address" class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-600" placeholder="Cth: Jl. Jendral Sudirman Kav. 12..."></textarea>
                    </div>

                    <DialogFooter class="pt-4">
                        <Button type="button" variant="outline" @click="closeModal">Batal</Button>
                        <Button type="submit" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700">Simpan Data</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
