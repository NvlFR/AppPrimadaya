<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { PlusIcon, PencilIcon, TrashIcon, TagIcon, Package } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useFormatRupiah } from '@/composables/useFormatRupiah';

interface Service {
    id: number;
    name: string;
    category: string;
    base_price: string | number;
    unit: string;
    has_matrix_pricing: boolean;
    is_active: boolean;
    description: string;
    prices_count: number;
}

const props = defineProps<{
    services: {
        data: Service[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    paper_sizes: { id: number; name: string }[];
    filters: { search?: string; category?: string };
}>();

// Filter States
const search = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category || '');

// Automatic search when typing
let searchTimeout: any;
watch([search, categoryFilter], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('services.index'), { search: search.value, category: categoryFilter.value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

// Modal States
const isModalOpen = ref(false);
const isEditMode = ref(false);
const editingId = ref<number | null>(null);

// Form Layanan
const form = useForm({
    name: '',
    category: 'print',
    base_price: 0,
    unit: 'lembar',
    has_matrix_pricing: false,
    is_active: true,
    description: '',
});

const openCreateModal = () => {
    isEditMode.value = false;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (service: Service) => {
    isEditMode.value = true;
    editingId.value = service.id;
    form.name = service.name;
    form.category = service.category;
    form.base_price = Number(service.base_price);
    form.unit = service.unit;
    form.has_matrix_pricing = !!service.has_matrix_pricing;
    form.is_active = !!service.is_active;
    form.description = service.description || '';
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const saveService = () => {
    if (isEditMode.value && editingId.value) {
        form.put(route('services.update', editingId.value), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('services.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

// Pricing Matrix Logic
const isMatrixModalOpen = ref(false);
const matrixForm = useForm({
    prices: [] as Array<{ paper_size_id: number | string | null; print_type: string; price: number }>,
});

const openMatrixModal = async (service: Service) => {
    editingId.value = service.id;
    isMatrixModalOpen.value = true;
    
    // Fetch current prices
    try {
        const response = await fetch(route('services.prices', service.id));
        const data = await response.json();
        
        if (data.length > 0) {
            matrixForm.prices = data.map((p: any) => ({
                paper_size_id: p.paper_size_id ? p.paper_size_id.toString() : null,
                print_type: p.print_type,
                price: Number(p.price)
            }));
        } else {
            matrixForm.prices = [{ paper_size_id: null, print_type: 'bw', price: Number(service.base_price) }];
        }
    } catch (error) {
        console.error("Gagal mengambil data harga:", error);
    }
};

const addMatrixRow = () => {
    matrixForm.prices.push({ paper_size_id: null, print_type: 'bw', price: Number(form.base_price) || 0 });
};

const removeMatrixRow = (index: number) => {
    matrixForm.prices.splice(index, 1);
};

const saveMatrixPrices = () => {
    if (!editingId.value) return;
    
    matrixForm.post(route('services.prices.store', editingId.value), {
        onSuccess: () => {
            isMatrixModalOpen.value = false;
        },
    });
};

// Delete Confirmation State
const isDeleteModalOpen = ref(false);
const serviceToDelete = ref<number | null>(null);

const confirmDeleteService = (id: number) => {
    serviceToDelete.value = id;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (serviceToDelete.value !== null) {
        router.delete(route('services.destroy', serviceToDelete.value), {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                serviceToDelete.value = null;
            }
        });
    }
};

// Fungsi untuk menutup matrix modal dengan benar
const closeMatrixModal = () => {
    isMatrixModalOpen.value = false;
};

const { formatRupiah } = useFormatRupiah();
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: route('dashboard') }, { title: 'Layanan', href: route('services.index') }]">
        <template #header-actions>
            <Button @click="openCreateModal" size="sm" class="bg-blue-600 hover:bg-blue-700">
                <PlusIcon class="h-4 w-4 mr-2" /> Tambah Layanan
            </Button>
        </template>
        <Head title="Layanan" />

        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <div>
                 <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <Package class="h-6 w-6 text-primary" />Layanan
                    </h1>
                <!-- <p class="text-sm text-gray-500">Kelola daftar layanan dan matriks harga cetak.</p> -->
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-4 bg-white p-4 rounded-xl border shadow-sm sm:flex-row">
                <div class="flex-1 min-w-0">
                    <Input v-model="search" type="search" placeholder="Cari layanan..." class="max-w-md" />
                </div>
                <select v-model="categoryFilter" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 sm:w-[180px]">
                    <option value="">Semua Kategori</option>
                    <option value="print">Print Dokumen</option>
                    <option value="banner">Banner / Spanduk</option>
                    <option value="foto">Cetak Foto</option>
                    <option value="fotocopy">Fotocopy</option>
                    <option value="laminasi">Laminasi / Jilid</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </div>

            <div class="mobile-data-list">
                <div v-for="item in services.data" :key="`service-mobile-${item.id}`" class="mobile-data-card space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-base font-semibold text-gray-900 break-words">{{ item.name }}</p>
                            <p class="mt-1 text-sm capitalize text-gray-500">{{ item.category }}</p>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-2">
                            <Badge :variant="item.is_active ? 'default' : 'secondary'">
                                {{ item.is_active ? 'Aktif' : 'Non-Aktif' }}
                            </Badge>
                            <Badge v-if="item.has_matrix_pricing" variant="outline" class="text-xs border-blue-200 text-blue-700 bg-blue-50">Matrix</Badge>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">Harga Dasar</p>
                            <p class="mt-1 font-medium text-gray-900">{{ formatRupiah(item.base_price) }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">Satuan</p>
                            <p class="mt-1 font-medium text-gray-900">{{ item.unit }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 pt-1">
                        <Button v-if="item.has_matrix_pricing" variant="outline" size="sm" class="h-8 shadow-sm border-amber-200 text-amber-700 hover:bg-amber-50" @click="openMatrixModal(item)">
                            <TagIcon class="h-3 w-3 mr-1" /> Prices
                        </Button>
                        <Button variant="outline" size="sm" class="h-8 shadow-sm" @click="openEditModal(item)">
                            <PencilIcon class="h-3 w-3 mr-1" /> Edit
                        </Button>
                        <Button variant="destructive" size="sm" class="h-8 shadow-sm" @click="confirmDeleteService(item.id)">
                            <TrashIcon class="h-3 w-3 mr-1" /> Hapus
                        </Button>
                    </div>
                </div>

                <div v-if="services.data.length === 0" class="mobile-data-card py-8 text-center text-sm text-gray-500">
                    Tidak ada data layanan ditemukan.
                </div>
            </div>

            <!-- Table -->
            <div class="data-table-shell hidden md:block">
                <div class="data-table-scroll">
                <table class="data-table">
                    <thead class="bg-gray-50 text-gray-600 font-medium">
                        <tr>
                            <th class="px-6 py-3 border-b">Nama Layanan</th>
                            <th class="px-6 py-3 border-b">Kategori</th>
                            <th class="px-6 py-3 border-b">Harga Dasar</th>
                            <th class="px-6 py-3 border-b">Satuan</th>
                            <th class="px-6 py-3 border-b text-center">Status</th>
                            <th class="px-6 py-3 border-b text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in services.data" :key="item.id" class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ item.name }}</td>
                            <td class="px-6 py-4 capitalize">{{ item.category }}</td>
                            <td class="px-6 py-4">{{ formatRupiah(item.base_price) }}</td>
                            <td class="px-6 py-4">{{ item.unit }}</td>
                            <td class="px-6 py-4 text-center">
                                <Badge :variant="item.is_active ? 'default' : 'secondary'">
                                    {{ item.is_active ? 'Aktif' : 'Non-Aktif' }}
                                </Badge>
                                <Badge v-if="item.has_matrix_pricing" variant="outline" class="ml-2 text-xs border-blue-200 text-blue-700 bg-blue-50">Matrix</Badge>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <Button v-if="item.has_matrix_pricing" variant="outline" size="sm" class="h-8 shadow-sm border-amber-200 text-amber-700 hover:bg-amber-50" @click="openMatrixModal(item)">
                                    <TagIcon class="h-3 w-3 mr-1" /> Prices
                                </Button>
                                <Button variant="outline" size="sm" class="h-8 shadow-sm" @click="openEditModal(item)">
                                    <PencilIcon class="h-3 w-3 mr-1" /> Edit
                                </Button>
                                <Button variant="destructive" size="sm" class="h-8 shadow-sm" @click="confirmDeleteService(item.id)">
                                    <TrashIcon class="h-3 w-3" />
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="services.data.length === 0">
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">Tidak ada data layanan ditemukan.</td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="flex justify-between items-center" v-if="services.last_page > 1">
                <Button
                    variant="outline" size="sm"
                    :disabled="!services.links?.[0]?.url"
                    @click="services.links?.[0]?.url && router.get(services.links[0].url)"
                >&larr; Sebelumnya</Button>
                <div class="text-sm text-gray-500">Halaman {{ services.current_page }} dari {{ services.last_page }}</div>
                <Button
                    variant="outline" size="sm"
                    :disabled="!services.links?.[services.links.length - 1]?.url"
                    @click="services.links?.[services.links.length - 1]?.url && router.get(services.links[services.links.length - 1].url)"
                >Berikutnya &rarr;</Button>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <Dialog :open="isModalOpen" @update:open="val => { if (!val) closeModal(); }">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>{{ isEditMode ? 'Edit Layanan' : 'Tambah Layanan Baru' }}</DialogTitle>
                </DialogHeader>
                
                <form @submit.prevent="saveService" class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="name">Nama Layanan <span class="text-red-500">*</span></Label>
                        <Input id="name" v-model="form.name" required placeholder="Cth: Print Dokumen A4" />
                        <span class="text-xs text-red-500" v-if="form.errors.name">{{ form.errors.name }}</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="category">Kategori <span class="text-red-500">*</span></Label>
                            <select id="category" v-model="form.category" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm block focus:outline-none focus:ring-1 focus:ring-blue-600">
                                <option value="print">Print Dokumen</option>
                                <option value="banner">Banner / Spanduk</option>
                                <option value="foto">Cetak Foto</option>
                                <option value="fotocopy">Fotocopy</option>
                                <option value="laminasi">Laminasi / Jilid</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="unit">Satuan <span class="text-red-500">*</span></Label>
                            <Input id="unit" v-model="form.unit" required placeholder="Cth: lembar, meter, pcs" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="base_price">Harga Dasar (Rp) <span class="text-red-500">*</span></Label>
                        <Input id="base_price" type="number" v-model="form.base_price" required min="0" />
                        <span class="text-xs text-red-500" v-if="form.errors.base_price">{{ form.errors.base_price }}</span>
                    </div>

                    <div class="space-y-2">
                        <Label for="description">Deskripsi Tambahan</Label>
                        <textarea id="description" v-model="form.description" class="flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-600" placeholder="Keterangan singkat tentang layanan ini"></textarea>
                    </div>

                    <div class="flex items-center space-x-2 pt-2">
                        <input type="checkbox" id="matrix_pricing" v-model="form.has_matrix_pricing" class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                        <Label for="matrix_pricing" class="font-normal cursor-pointer">Gunakan Pricing Matrix (Harga beda per ukuran/warna)</Label>
                    </div>

                    <div class="flex items-center space-x-2">
                        <input type="checkbox" id="status_active" v-model="form.is_active" class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                        <Label for="status_active" class="font-normal cursor-pointer">Berstatus Aktif</Label>
                    </div>

                    <DialogFooter class="pt-4">
                        <Button type="button" variant="outline" @click="closeModal">Batal</Button>
                        <Button type="submit" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700">Simpan Data</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Matrix Pricing Modal -->
        <Dialog :open="isMatrixModalOpen" @update:open="val => { if (!val) closeMatrixModal(); }">
            <DialogContent class="sm:max-w-[700px]">
                <DialogHeader>
                    <DialogTitle>Pricing Matrix: {{ services.data.find(s => s.id === editingId)?.name }}</DialogTitle>
                </DialogHeader>
                
                <div class="py-4 space-y-4">
                    <p class="text-sm text-gray-500">Atur harga spesifik berdasarkan kombinasi ukuran kertas dan tipe cetak.</p>
                    
                    <div class="space-y-3">
                        <div v-for="(row, index) in matrixForm.prices" :key="index" class="flex flex-col sm:flex-row items-end sm:items-center gap-3 bg-gray-50 p-3 rounded-lg border">
                            <div class="flex-1 w-full">
                                <Label class="text-[10px] uppercase font-bold text-gray-400 mb-1">Ukuran Kertas</Label>
                                <select v-model="row.paper_size_id" class="flex h-8 w-full rounded-md border border-input bg-white px-3 py-1 text-xs shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-600">
                                    <option :value="null">Ukuran Standar / NA</option>
                                    <option v-for="ps in paper_sizes" :key="ps.id" :value="ps.id.toString()">{{ ps.name }}</option>
                                </select>
                            </div>
                            <div class="flex-1 w-full">
                                <Label class="text-[10px] uppercase font-bold text-gray-400 mb-1">Tipe Cetak</Label>
                                <select v-model="row.print_type" class="flex h-8 w-full rounded-md border border-input bg-white px-3 py-1 text-xs shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-600">
                                    <option value="bw">Hitam Putih</option>
                                    <option value="color">Warna Full</option>
                                    <option value="na">N/A (Lainnya)</option>
                                </select>
                            </div>
                            <div class="w-full sm:w-32">
                                <Label class="text-[10px] uppercase font-bold text-gray-400 mb-1">Harga (Rp)</Label>
                                <Input type="number" v-model="row.price" class="h-8 text-xs font-bold" />
                            </div>
                            <div class="pt-0 sm:pt-5 w-full sm:w-auto text-right">
                                <Button variant="ghost" size="icon" @click="removeMatrixRow(index)" class="h-8 w-8 text-red-500 hover:bg-red-50">
                                    <TrashIcon class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <Button variant="outline" @click="addMatrixRow" class="w-full border-dashed border-2 hover:border-blue-500 hover:text-blue-600">
                        <PlusIcon class="h-4 w-4 mr-2" /> Tambah Kombinasi Harga
                    </Button>
                </div>

                <DialogFooter class="pt-4 border-t">
                    <Button type="button" variant="outline" @click="closeMatrixModal">Batal</Button>
                    <Button type="button" @click="saveMatrixPrices" :disabled="matrixForm.processing" class="bg-amber-600 hover:bg-amber-700 text-white">
                        Simpan Matriks Harga
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        <!-- Delete Confirmation Modal -->
        <Dialog :open="isDeleteModalOpen" @update:open="val => { if (!val) isDeleteModalOpen = false; }">
            <DialogContent class="sm:max-w-[400px]">
                <DialogHeader>
                    <DialogTitle>Hapus Layanan</DialogTitle>
                </DialogHeader>
                <div class="py-4">
                    <p class="text-sm text-gray-500">
                        Apakah Anda yakin ingin menghapus layanan ini? Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="isDeleteModalOpen = false">Batal</Button>
                    <Button type="button" variant="destructive" @click="executeDelete" class="bg-red-600 hover:bg-red-700">Ya, Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
