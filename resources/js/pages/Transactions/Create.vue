<script setup lang="ts">
import { onKeyStroke } from '@vueuse/core';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useFormatRupiah } from '@/composables/useFormatRupiah';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
// Select masih dipakai untuk dropdown layanan & metode bayar — hanya combobox PELANGGAN yang custom (Issue #25)
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter
} from '@/components/ui/dialog';
import {
    Trash2, ShoppingCart, Plus, Save, User, FileText,
    RefreshCw, Zap, Keyboard, Loader2, AlertTriangle, UserPlus, Tag, Percent, Search, X
} from 'lucide-vue-next';

// ============================================================
// Props dari Inertia (data awal dari controller)
// ============================================================
const props = defineProps<{
    services: Array<any>;
    paper_sizes: Array<any>;
}>();

// ============================================================
// State Combobox Async Pelanggan (Issue #25)
// Menggantikan dropdown pelanggan yang load semua data sekaligus
// ============================================================
interface CustomerOption {
    id: number;
    name: string;
    phone: string;
}

const customerSearchQuery = ref('');
const customerSearchResults = ref<CustomerOption[]>([]);
const isSearchingCustomer = ref(false);
const isCustomerDropdownOpen = ref(false);
const selectedCustomerLabel = ref('Pelanggan Umum (Tidak Terafiliasi)');

// Debounce timer untuk mengurangi hit ke API
let customerSearchTimer: ReturnType<typeof setTimeout>;

/**
 * Mencari pelanggan secara asinkron ke endpoint /customers/search
 * dengan debounce 300ms agar tidak membebani server.
 */
const searchCustomers = (query: string) => {
    clearTimeout(customerSearchTimer);
    if (!query.trim()) {
        customerSearchResults.value = [];
        return;
    }
    isSearchingCustomer.value = true;
    customerSearchTimer = setTimeout(async () => {
        try {
            const response = await fetch(`/customers/search?q=${encodeURIComponent(query)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (response.ok) {
                customerSearchResults.value = await response.json();
            }
        } catch (error) {
            // Gagal fetch tidak menghentikan fungsi kasir, cukup log ke konsol
            console.error('Gagal mengambil data pelanggan:', error);
        } finally {
            isSearchingCustomer.value = false;
        }
    }, 300);
};

/**
 * Memilih pelanggan dari hasil pencarian dan menutup dropdown.
 */
const selectCustomer = (customer: CustomerOption) => {
    form.customer_id = customer.id.toString();
    selectedCustomerLabel.value = `${customer.name} — ${customer.phone}`;
    customerSearchQuery.value = '';
    customerSearchResults.value = [];
    isCustomerDropdownOpen.value = false;
};

/**
 * Mereset pilihan pelanggan ke Pelanggan Umum.
 */
const clearCustomer = () => {
    form.customer_id = 'none';
    selectedCustomerLabel.value = 'Pelanggan Umum (Tidak Terafiliasi)';
    customerSearchQuery.value = '';
    customerSearchResults.value = [];
    isCustomerDropdownOpen.value = false;
};

// Pantau perubahan query pencarian dan trigger search ke API
watch(customerSearchQuery, (newQuery) => {
    searchCustomers(newQuery);
});

// ============================================================
// Click-outside handler untuk menutup dropdown pelanggan
// Menggunakan native event listener agar tidak perlu library eksternal
// ============================================================
const customerComboboxRef = ref<HTMLElement | null>(null);

const handleClickOutside = (event: MouseEvent) => {
    if (customerComboboxRef.value && !customerComboboxRef.value.contains(event.target as Node)) {
        isCustomerDropdownOpen.value = false;
    }
};

onMounted(() => document.addEventListener('mousedown', handleClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside));



// ============================================================
// Form Data utama via Inertia
// ============================================================
const form = useForm({
    customer_id: 'none',
    items: [] as Array<{
        service_id: string;
        paper_size_id: string | null;
        print_type: string;
        qty: number;
        unit_price: number;
        item_notes: string;
        file: File | null;
    }>,
    discount_type: 'percent' as 'percent' | 'flat',
    discount_value: 0,
    payment_method: 'cash',
    amount_paid: 0,
    notes: '',
});

// ============================================================
// State internal kasir
// ============================================================
const selectedServiceId = ref('');

// -- State untuk Dialog Konfirmasi Submit (Issue #8) --
const showConfirmDialog = ref(false);

// -- State untuk Dialog Tambah Pelanggan Inline (Issue #10) --
const showAddCustomerDialog = ref(false);
const newCustomerForm = useForm({
    name: '',
    phone: '',
    address: '',
    notes: '',
});
const isAddingCustomer = ref(false);

// ============================================================
// Keyboard Shortcuts (Issue #4 — sudah ada, dipertahankan)
// ============================================================

// F2: Fokus ke dropdown pemilihan layanan
onKeyStroke('F2', (e) => {
    e.preventDefault();
    const trigger = document.querySelector('[data-search-trigger]') as HTMLElement;
    trigger?.focus();
});

// F4: Fokus ke input nominal bayar tunai
onKeyStroke('F4', (e) => {
    e.preventDefault();
    const paymentInput = document.querySelector('[data-payment-input]') as HTMLElement;
    paymentInput?.focus();
});

// F9: Buka dialog konfirmasi transaksi
onKeyStroke('F9', (e) => {
    e.preventDefault();
    openConfirmDialog();
});

// ============================================================
// Computed: Layanan Favorit (Pinned)
// ============================================================
const pinnedServices = computed(() => {
    const pinned = props.services.filter(s => s.is_pinned);
    return pinned.length > 0 ? pinned : props.services.slice(0, 6);
});

// ============================================================
// Auto-add item saat layanan dipilih dari dropdown
// ============================================================
watch(selectedServiceId, (newVal) => {
    if (newVal) {
        addItem();
    }
});

// ============================================================
// Fungsi Menambahkan Layanan ke Keranjang
// ============================================================
const addItem = (serviceId?: number) => {
    const idToAdd = serviceId || parseInt(selectedServiceId.value);
    if (!idToAdd) return;

    const service = props.services.find(s => s.id === idToAdd);
    if (!service) return;

    form.items.push({
        service_id: service.id.toString(),
        paper_size_id: null,
        print_type: service.has_matrix_pricing ? 'bw' : 'na',
        qty: 1,
        unit_price: service.base_price,
        item_notes: '',
        file: null,
    });

    selectedServiceId.value = ''; // reset dropdown
};

// ============================================================
// Fungsi Hapus Item dari Keranjang
// ============================================================
const isDeleteModalOpen = ref(false);
const itemIndexToDelete = ref<number | null>(null);

const confirmRemoveItem = (index: number) => {
    itemIndexToDelete.value = index;
    isDeleteModalOpen.value = true;
};

const executeRemoveItem = () => {
    if (itemIndexToDelete.value !== null) {
        form.items.splice(itemIndexToDelete.value, 1);
        isDeleteModalOpen.value = false;
        itemIndexToDelete.value = null;
    }
};

// ============================================================
// Fungsi Update Harga Dinamis (matrix pricing)
// ============================================================
const updateItemPrice = (index: number) => {
    const item = form.items[index];
    const service = props.services.find(s => s.id === parseInt(item.service_id));

    if (service && service.has_matrix_pricing) {
        const priceObj = service.prices.find((p: any) =>
            p.paper_size_id == item.paper_size_id && p.print_type === item.print_type
        );
        item.unit_price = priceObj ? parseFloat(priceObj.price) : parseFloat(service.base_price);
    }
};

// ============================================================
// Perhitungan Total Otomatis
// ============================================================
const subtotal = computed(() =>
    form.items.reduce((sum, item) => sum + (item.unit_price * item.qty), 0)
);

// Perhitungan diskon — mendukung persen & flat nominal (Issue #9)
const discountAmount = computed(() => {
    if (form.discount_type === 'percent') {
        return subtotal.value * (form.discount_value / 100);
    }
    // Flat nominal — pastikan tidak melebihi subtotal
    return Math.min(form.discount_value, subtotal.value);
});

const totalFinal = computed(() => subtotal.value - discountAmount.value);

// Kembalian — jika kurang bayar tampilkan selisih negatif (Issue #7)
const changeAmount = computed(() => form.amount_paid - totalFinal.value);

const isUnderpaid = computed(() =>
    form.payment_method === 'cash' && form.amount_paid > 0 && changeAmount.value < 0
);

const { formatRupiah } = useFormatRupiah();

// ============================================================
// Fungsi Reset Kasir
// ============================================================
const isResetModalOpen = ref(false);

const resetKasir = () => {
    if (form.items.length > 0) {
        isResetModalOpen.value = true;
        return;
    }
    executeResetKasir();
};

const executeResetKasir = () => {
    form.reset();
    form.items = [];
    form.payment_method = 'cash';
    form.discount_type = 'percent';
    form.discount_value = 0;
    form.amount_paid = 0;
    form.customer_id = 'none';
    selectedServiceId.value = '';
    
    // Reset state combobox pelanggan (Issue #25)
    customerSearchQuery.value = '';
    isCustomerComboboxOpen.value = false;
    
    isResetModalOpen.value = false;
};
    // Reset state combobox pelanggan (Issue #25)
    clearCustomer();
};

// ============================================================
// Dialog Konfirmasi Submit (Issue #8)
// ============================================================
const openConfirmDialog = () => {
    if (form.items.length === 0) {
        alert('Keranjang masih kosong!');
        return;
    }
    if (form.payment_method === 'cash' && isUnderpaid.value) {
        alert('Nominal pembayaran kurang dari total tagihan!');
        return;
    }
    showConfirmDialog.value = true;
};

// Proses Submit ke Server — dipanggil setelah konfirmasi
const submitTransaction = () => {
    showConfirmDialog.value = false;

    form.transform((data) => ({
        ...data,
        customer_id: data.customer_id === 'none' ? '' : data.customer_id,
        // Kirim discount_amount yang dihitung agar backend tidak perlu hitung ulang
        discount_amount: discountAmount.value,
    })).post(route('transactions.store'), {
        forceFormData: true,
        preserveScroll: true,
    });
};

// ============================================================
// Tambah Pelanggan Inline (Issue #10) — Diperbarui untuk async combobox (Issue #25)
// ============================================================
const submitNewCustomer = () => {
    isAddingCustomer.value = true;
    newCustomerForm.post(route('customers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // Setelah pelanggan baru berhasil dibuat, langsung pilih pelanggan tersebut
            // dengan melakukan search berdasarkan nama yang baru diisi
            const newName = newCustomerForm.name;
            showAddCustomerDialog.value = false;
            newCustomerForm.reset();
            // Trigger search agar pelanggan baru muncul dan dapat dipilih
            customerSearchQuery.value = newName;
            isCustomerDropdownOpen.value = true;
        },
        onFinish: () => {
            isAddingCustomer.value = false;
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="[
        { title: 'Dashboard', href: route('dashboard') },
        { title: 'Kasir Baru', href: route('transactions.create') }
    ]">
    <Head title="Kasir & Transaksi Baru" />

    <div class="px-4 py-6 md:px-8 max-w-[1600px] mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 flex items-center">
                    <ShoppingCart class="mr-3 h-6 w-6 text-primary" /> Transaksi Kasir
                </h2>
                <p class="text-sm text-gray-500 mt-1">Buat pesanan baru untuk pelanggan</p>
            </div>
            <Button variant="outline" class="text-gray-500" @click="resetKasir">
                <RefreshCw class="mr-2 h-4 w-4" /> Reset Kasir
            </Button>
        </div>

        <!-- Layout Kiri (Keranjang & Setup) - Kanan (Ringkasan & Bayar) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 h-full items-start">

            <!-- KOLOM KIRI: Keranjang Belanja -->
            <div class="lg:col-span-8 flex flex-col gap-6">

                <!-- 1. Block Informasi Pelanggan -->
                <Card class="border shadow-sm">
                    <CardHeader class="pb-4">
                        <CardTitle class="text-lg flex items-center"><User class="mr-2 h-5 w-5 text-gray-400" /> Informasi Pelanggan</CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Combobox Async Pelanggan — Issue #25 -->
                        <!-- Pencarian asinkron: tidak load semua data saat halaman dibuka -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <Label>Pilih Pelanggan (Tetap)</Label>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-6 text-xs text-primary hover:text-primary/80 px-2"
                                    @click="showAddCustomerDialog = true"
                                >
                                    <UserPlus class="h-3 w-3 mr-1" /> Daftarkan Baru
                                </Button>
                            </div>

                            <!-- Trigger combobox — menampilkan pelanggan terpilih -->
                            <div ref="customerComboboxRef" class="relative">
                                <button
                                    type="button"
                                    class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm ring-offset-background transition-colors focus:outline-none focus:ring-1 focus:ring-ring hover:bg-accent"
                                    :class="isCustomerDropdownOpen ? 'ring-1 ring-ring' : ''"
                                    @click="isCustomerDropdownOpen = !isCustomerDropdownOpen"
                                >
                                    <span :class="form.customer_id === 'none' ? 'text-muted-foreground' : 'text-foreground'">
                                        {{ selectedCustomerLabel }}
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <!-- Tombol clear jika ada pelanggan terpilih -->
                                        <button
                                            v-if="form.customer_id !== 'none'"
                                            type="button"
                                            class="rounded-full p-0.5 hover:bg-red-100 text-red-400 hover:text-red-600 transition-colors"
                                            @click.stop="clearCustomer"
                                        >
                                            <X class="h-3.5 w-3.5" />
                                        </button>
                                        <Search class="h-4 w-4 text-muted-foreground" />
                                    </div>
                                </button>

                                <!-- Dropdown results -->
                                <div
                                    v-if="isCustomerDropdownOpen"
                                    class="absolute z-50 mt-1 w-full rounded-md border bg-white shadow-lg"
                                >
                                    <!-- Input pencarian -->
                                    <div class="p-2 border-b">
                                        <div class="relative">
                                            <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground" />
                                            <input
                                                v-model="customerSearchQuery"
                                                type="text"
                                                placeholder="Ketik nama atau no. HP..."
                                                class="w-full h-8 pl-8 pr-3 text-sm rounded border border-input bg-transparent focus:outline-none focus:ring-1 focus:ring-ring"
                                                autofocus
                                                @keydown.escape="isCustomerDropdownOpen = false"
                                            />
                                        </div>
                                    </div>

                                    <!-- List hasil pencarian -->
                                    <div class="max-h-48 overflow-y-auto py-1">
                                        <!-- Opsi Pelanggan Umum selalu ada -->
                                        <button
                                            type="button"
                                            class="w-full flex items-center px-3 py-2 text-sm hover:bg-accent transition-colors text-left"
                                            :class="form.customer_id === 'none' ? 'bg-primary/5 text-primary font-medium' : 'text-gray-600'"
                                            @click="clearCustomer"
                                        >
                                            <User class="mr-2 h-3.5 w-3.5 shrink-0 text-gray-400" />
                                            Pelanggan Umum (Tidak Terafiliasi)
                                        </button>

                                        <!-- Loading state -->
                                        <div v-if="isSearchingCustomer" class="flex items-center justify-center py-4 text-gray-400">
                                            <Loader2 class="h-4 w-4 animate-spin mr-2" />
                                            <span class="text-xs">Mencari...</span>
                                        </div>

                                        <!-- Hasil pencarian -->
                                        <template v-else-if="customerSearchResults.length > 0">
                                            <button
                                                v-for="c in customerSearchResults"
                                                :key="c.id"
                                                type="button"
                                                class="w-full flex items-center px-3 py-2 text-sm hover:bg-accent transition-colors text-left"
                                                :class="form.customer_id === c.id.toString() ? 'bg-primary/5 text-primary font-medium' : ''"
                                                @click="selectCustomer(c)"
                                            >
                                                <User class="mr-2 h-3.5 w-3.5 shrink-0 text-primary/60" />
                                                <div>
                                                    <p class="font-medium leading-none">{{ c.name }}</p>
                                                    <p class="text-xs text-muted-foreground mt-0.5">{{ c.phone || 'Tanpa nomor HP' }}</p>
                                                </div>
                                            </button>
                                        </template>

                                        <!-- Empty state (setelah mengetik tapi tidak ada hasil) -->
                                        <div
                                            v-else-if="customerSearchQuery.trim() && !isSearchingCustomer"
                                            class="py-4 text-center text-xs text-gray-400"
                                        >
                                            Pelanggan "{{ customerSearchQuery }}" tidak ditemukan.
                                        </div>

                                        <!-- Prompt awal sebelum mengetik -->
                                        <div
                                            v-else-if="!customerSearchQuery.trim()"
                                            class="py-3 text-center text-xs text-gray-400"
                                        >
                                            Ketik nama atau no. HP untuk mencari...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label>Catatan Umum Pesanan</Label>
                            <Input v-model="form.notes" placeholder="Catatan untuk tim produksi..." />
                        </div>
                    </CardContent>
                </Card>

                <!-- 2. Block Keranjang Belanja -->
                <Card class="border shadow-sm min-h-[400px] flex flex-col">
                    <CardHeader class="bg-gray-50 rounded-t-xl border-b pb-4">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <CardTitle class="text-lg flex items-center"><FileText class="mr-2 h-5 w-5 text-gray-400" /> Keranjang Belanja</CardTitle>
                            <div class="flex items-center gap-2 w-full md:w-[400px]">
                                <div class="relative flex-1">
                                    <Select v-model="selectedServiceId">
                                        <SelectTrigger class="bg-white" data-search-trigger>
                                            <SelectValue placeholder="Pilih layanan (F2)..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="s in services" :key="s.id" :value="s.id.toString()">
                                                {{ s.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <kbd class="hidden md:flex absolute right-10 top-1/2 -translate-y-1/2 h-5 items-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium text-muted-foreground opacity-100 pointer-events-none">
                                        F2
                                    </kbd>
                                </div>
                                <Button @click="() => addItem()" :disabled="!selectedServiceId" size="icon" class="shrink-0 bg-primary hover:bg-primary/90 text-white">
                                    <Plus class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <!-- Layanan Favorit -->
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="text-[10px] font-bold text-gray-400 uppercase flex items-center mr-2"><Zap class="w-3 h-3 mr-1" /> Favorit:</span>
                            <Button
                                v-for="s in pinnedServices"
                                :key="s.id"
                                variant="outline"
                                size="sm"
                                class="h-8 text-xs bg-white border-dashed hover:border-primary hover:text-primary transition-all shadow-sm"
                                @click="addItem(s.id)"
                            >
                                {{ s.name }}
                            </Button>
                        </div>
                    </CardHeader>

                    <CardContent class="p-0 flex-1 relative overflow-auto">
                        <div v-if="form.items.length === 0" class="absolute inset-0 flex flex-col justify-center items-center text-center p-8 text-gray-500">
                            <ShoppingCart class="h-12 w-12 text-gray-200 mb-3" />
                            <p class="font-medium text-gray-600">Keranjang masih kosong.</p>
                            <p class="text-sm mt-1">Pilih layanan di atas untuk menambahkan item.</p>
                        </div>

                        <!-- Tampilan Mobile (Card List) -->
                        <div class="block md:hidden divide-y divide-gray-100">
                            <div v-for="(item, index) in form.items" :key="index" class="p-4 space-y-4 bg-white hover:bg-gray-50 transition-colors">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <p class="font-bold text-gray-900 text-lg">{{ services.find(s => s.id == item.service_id)?.name }}</p>
                                        <Input v-model="item.item_notes" placeholder="Catatan (Pake Spiral...)" class="h-8 text-xs mt-1 bg-white" />
                                    </div>
                                    <Button @click="confirmRemoveItem(index)" variant="ghost" size="icon" class="h-8 w-8 text-red-500 shrink-0">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>

                                <div v-if="services.find(s => s.id == item.service_id)?.has_matrix_pricing" class="grid grid-cols-2 gap-2">
                                    <Select v-model="item.paper_size_id" @update:modelValue="updateItemPrice(index)">
                                        <SelectTrigger class="h-9 text-xs bg-white"><SelectValue placeholder="Ukuran" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="ps in paper_sizes" :key="ps.id" :value="ps.id.toString()">Kertas {{ ps.name }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Select v-model="item.print_type" @update:modelValue="updateItemPrice(index)">
                                        <SelectTrigger class="h-9 text-xs bg-white"><SelectValue placeholder="Warna/BW" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="color">Warna</SelectItem>
                                            <SelectItem value="bw">BW</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="flex items-center justify-between gap-4">
                                    <div class="w-1/3">
                                        <Label class="text-[10px] text-gray-400 uppercase">Harga</Label>
                                        <Input v-model="item.unit_price" type="number" class="h-9 text-right bg-white mt-0.5" />
                                    </div>
                                    <div class="w-1/4">
                                        <Label class="text-[10px] text-gray-400 uppercase">Qty</Label>
                                        <Input v-model="item.qty" type="number" class="h-9 text-center bg-white mt-0.5" />
                                    </div>
                                    <div class="flex-1 text-right pt-4">
                                        <p class="text-xs text-gray-500">Subtotal</p>
                                        <p class="font-bold text-gray-900">{{ formatRupiah(item.unit_price * item.qty) }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 pt-1">
                                    <input type="file" :id="`file-mobile-${index}`" class="hidden" @change="(e: any) => item.file = e.target.files[0]">
                                    <label :for="`file-mobile-${index}`" class="flex-1 flex items-center justify-center gap-2 py-2 bg-blue-50 border border-blue-200 rounded-lg cursor-pointer hover:bg-blue-100 transition-colors">
                                        <Plus class="h-3 w-3 text-blue-600" />
                                        <span class="text-xs font-bold text-blue-700 uppercase">
                                            {{ item.file ? item.file.name.substring(0, 15) + '...' : 'Upload File Desain' }}
                                        </span>
                                    </label>
                                    <Button v-if="item.file" variant="ghost" size="icon" @click="item.file = null" class="h-9 w-9 text-red-500 border border-red-100">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Table Keranjang -->
                        <div class="hidden md:block w-full overflow-x-auto">
                            <table class="w-full text-sm text-left min-w-[800px]">
                                <thead class="text-xs text-gray-500 uppercase bg-white border-b border-gray-100">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Layanan</th>
                                        <th class="px-4 py-3 font-semibold w-40">Detail Ukuran / Tipe</th>
                                        <th class="px-4 py-3 font-semibold w-32">Harga Satuan</th>
                                        <th class="px-4 py-3 font-semibold w-24">Qty</th>
                                        <th class="px-4 py-3 font-semibold text-right w-32">Subtotal</th>
                                        <th class="px-4 py-3 w-10"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in form.items" :key="index" class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-4 align-top">
                                            <p class="font-medium text-gray-900 text-base mb-2">{{ services.find(s => s.id == item.service_id)?.name }}</p>
                                            <div class="space-y-2">
                                                <Input v-model="item.item_notes" placeholder="Catatan item (contoh: Pake Spiral)" class="h-8 text-xs bg-white" />
                                                <!-- Upload File per Item -->
                                                <div class="flex items-center gap-2">
                                                    <input type="file" :id="`file-${index}`" class="hidden" @change="(e: any) => item.file = e.target.files[0]">
                                                    <label :for="`file-${index}`" class="flex items-center gap-1.5 px-2 py-1 bg-blue-50 border border-blue-200 rounded cursor-pointer hover:bg-blue-100 transition-colors">
                                                        <Plus class="h-3 w-3 text-blue-600" />
                                                        <span class="text-[10px] font-bold text-blue-700 uppercase">
                                                            {{ item.file ? item.file.name.substring(0, 15) + '...' : 'Upload File' }}
                                                        </span>
                                                    </label>
                                                    <Button v-if="item.file" variant="ghost" size="icon" @click="item.file = null" class="h-6 w-6 text-red-500">
                                                        <Trash2 class="h-3 w-3" />
                                                    </Button>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Opsi Matrix Pricing -->
                                        <td class="px-4 py-4 align-top">
                                            <div v-if="services.find(s => s.id == item.service_id)?.has_matrix_pricing" class="flex flex-col gap-2">
                                                <Select v-model="item.paper_size_id" @update:modelValue="updateItemPrice(index)">
                                                    <SelectTrigger class="h-8 text-xs bg-white"><SelectValue placeholder="Pilih Ukuran" /></SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem v-for="ps in paper_sizes" :key="ps.id" :value="ps.id.toString()">Kertas {{ ps.name }}</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <Select v-model="item.print_type" @update:modelValue="updateItemPrice(index)">
                                                    <SelectTrigger class="h-8 text-xs bg-white"><SelectValue placeholder="Warna/BW" /></SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="color">Warna Full</SelectItem>
                                                        <SelectItem value="bw">Hitam Putih</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div v-else class="text-xs text-gray-400 italic py-2">Tidak ada opsi/standar</div>
                                        </td>

                                        <!-- Harga Satuan & QTY -->
                                        <td class="px-4 py-4 align-top">
                                            <Input v-model="item.unit_price" type="number" class="h-8 text-right bg-white" min="0" />
                                        </td>
                                        <td class="px-4 py-4 align-top">
                                            <Input v-model="item.qty" type="number" class="h-8 text-center bg-white" min="1" />
                                        </td>
                                        <td class="px-4 py-4 align-top text-right font-semibold text-gray-900 pt-5">
                                            {{ formatRupiah(item.unit_price * item.qty) }}
                                        </td>
                                        <td class="px-4 py-4 align-top text-right">
                                            <Button @click="removeItem(index)" variant="ghost" size="icon" class="h-8 w-8 text-red-500 hover:bg-red-50">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- KOLOM KANAN: Pembayaran & Ringkasan -->
            <div class="lg:col-span-4 sticky top-20">
                <Card class="border-2 border-primary/20 shadow-xl flex flex-col max-h-[calc(100vh-6rem)] overflow-y-auto bg-white rounded-xl">
                    <!-- Header Total -->
                    <div class="bg-primary px-6 py-4 flex items-center justify-between text-white">
                        <span class="font-semibold tracking-wide">TOTAL TAGIHAN</span>
                        <h2 class="text-3xl font-bold tracking-tight">{{ formatRupiah(totalFinal) }}</h2>
                    </div>

                    <CardContent class="p-6 space-y-6 flex-1">
                        <!-- Ringkasan Rincian -->
                        <div class="space-y-3 pb-6 border-b border-dashed border-gray-200">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500">Subtotal Item ({{ form.items.length }})</span>
                                <span class="font-medium text-gray-900">{{ formatRupiah(subtotal) }}</span>
                            </div>

                            <!-- Diskon dengan Toggle Persen / Flat (Issue #9) -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500">Diskon</span>
                                    <!-- Toggle persen / nominal -->
                                    <div class="flex items-center rounded-md border border-gray-200 overflow-hidden text-xs">
                                        <button
                                            type="button"
                                            class="flex items-center px-2 py-1 gap-1 transition-colors"
                                            :class="form.discount_type === 'percent' ? 'bg-primary text-white' : 'bg-white text-gray-500 hover:bg-gray-50'"
                                            @click="form.discount_type = 'percent'; form.discount_value = 0"
                                        >
                                            <Percent class="h-3 w-3" /> %
                                        </button>
                                        <button
                                            type="button"
                                            class="flex items-center px-2 py-1 gap-1 transition-colors"
                                            :class="form.discount_type === 'flat' ? 'bg-primary text-white' : 'bg-white text-gray-500 hover:bg-gray-50'"
                                            @click="form.discount_type = 'flat'; form.discount_value = 0"
                                        >
                                            <Tag class="h-3 w-3" /> Rp
                                        </button>
                                    </div>
                                </div>
                                <div class="relative">
                                    <div v-if="form.discount_type === 'flat'" class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 text-xs font-medium">Rp</div>
                                    <Input
                                        v-model="form.discount_value"
                                        type="number"
                                        class="h-8 text-right text-sm"
                                        :class="form.discount_type === 'flat' ? 'pl-8' : ''"
                                        :min="0"
                                        :max="form.discount_type === 'percent' ? 100 : subtotal"
                                        :placeholder="form.discount_type === 'percent' ? '0%' : '0'"
                                    />
                                </div>
                            </div>

                            <!-- Tampil potongan harga jika ada -->
                            <div v-if="discountAmount > 0" class="flex justify-between items-center text-sm text-green-600 font-medium pt-1">
                                <span>Potongan Harga</span>
                                <span>- {{ formatRupiah(discountAmount) }}</span>
                            </div>
                        </div>

                        <!-- Pembayaran Form -->
                        <div class="space-y-4 pt-2">
                            <div class="space-y-2">
                                <Label class="text-gray-700 font-semibold" for="payment_method">Metode Pembayaran</Label>
                                <Select
                                    :model-value="form.payment_method"
                                    @update:model-value="(val) => { 
                                        form.payment_method = val as string; 
                                        // Auto-fill amount_paid jika bukan cash
                                        form.amount_paid = val === 'cash' ? 0 : totalFinal; 
                                    }"
                                >
                                    <SelectTrigger class="bg-gray-50 border-gray-300">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="cash">Tunai (Cash)</SelectItem>
                                        <SelectItem value="qris">E-Wallet / QRIS</SelectItem>
                                        <SelectItem value="transfer">Transfer Bank</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Field Nominal Dibayarkan — Konsisten (Issue layout fix) -->
                            <div class="space-y-3 pt-1">
                                <Label class="text-gray-700 font-semibold">
                                    Nominal Dibayarkan {{ form.payment_method === 'cash' ? '(Tunai)' : `(${form.payment_method.toUpperCase()})` }}
                                </Label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-500 font-medium">Rp</div>
                                    <Input
                                        v-model="form.amount_paid"
                                        type="number"
                                        class="pl-10 text-lg font-bold h-12 border-primary/50"
                                        :class="{
                                            'focus-visible:ring-primary/50': form.payment_method === 'cash',
                                            'bg-gray-100 opacity-80 cursor-not-allowed': form.payment_method !== 'cash'
                                        }"
                                        :readonly="form.payment_method !== 'cash'"
                                        data-payment-input
                                    />
                                    <kbd v-if="form.payment_method === 'cash'" class="hidden md:flex absolute right-4 top-1/2 -translate-y-1/2 h-6 items-center gap-1 rounded border bg-muted px-2 font-mono text-xs font-medium text-muted-foreground opacity-100 pointer-events-none shadow-sm">
                                        F4
                                    </kbd>
                                </div>

                                <!-- Tombol cepat nominal uang (hanya muncul jika metode = cash) -->
                                <div v-if="form.payment_method === 'cash'" class="grid grid-cols-3 gap-1.5">
                                    <Button
                                        v-for="nominal in [5000, 10000, 20000, 50000, 100000, 200000]"
                                        :key="nominal"
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="h-8 text-xs font-medium hover:bg-primary/10 hover:border-primary/40"
                                        @click="form.amount_paid = nominal"
                                    >
                                        {{ formatRupiah(nominal).replace('Rp ', '') }}
                                    </Button>
                                </div>

                                <!-- Tampilan Kembalian (Issue #7) — HANYA UNTUK CASH -->
                                <div
                                    v-if="form.payment_method === 'cash'"
                                    class="flex justify-between items-center p-3 rounded-lg border transition-colors mt-1"
                                    :class="{
                                        'bg-green-50 border-green-200': changeAmount >= 0 && form.amount_paid > 0,
                                        'bg-red-50 border-red-200': isUnderpaid,
                                        'bg-gray-50 border-gray-100': form.amount_paid === 0
                                    }"
                                >
                                    <span class="text-sm font-medium" :class="{
                                        'text-green-700': changeAmount >= 0 && form.amount_paid > 0,
                                        'text-red-600': isUnderpaid,
                                        'text-gray-500': form.amount_paid === 0
                                    }">
                                        {{ isUnderpaid ? '⚠ Kurang Bayar:' : 'Kembalian:' }}
                                    </span>
                                    <span
                                        class="text-2xl font-bold"
                                        :class="{
                                            'text-green-600': changeAmount >= 0 && form.amount_paid > 0,
                                            'text-red-600': isUnderpaid,
                                            'text-gray-400': form.amount_paid === 0
                                        }"
                                    >
                                        {{ isUnderpaid ? formatRupiah(Math.abs(changeAmount)) : formatRupiah(changeAmount) }}
                                    </span>
                                </div>

                                <!-- Info non-cash -->
                                <div v-if="form.payment_method !== 'cash'" class="bg-blue-50 border border-blue-100 rounded-lg p-3 text-sm text-blue-700 flex items-center gap-2 mt-1">
                                    <AlertTriangle class="h-4 w-4 text-blue-400 shrink-0" />
                                    <span>Pastikan dana <strong>{{ form.payment_method.toUpperCase() }}</strong> sudah masuk.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Info Shortcut -->
                        <div class="hidden lg:flex items-center gap-3 text-[10px] text-gray-400 border-t pt-4">
                            <span class="flex items-center gap-1"><Keyboard class="w-3 h-3" /> Shortcut:</span>
                            <span><b>F2</b>: Cari</span>
                            <span><b>F4</b>: Bayar</span>
                            <span><b>F9</b>: Simpan</span>
                        </div>
                    </CardContent>

                    <!-- Tombol Submit -->
                    <div class="p-6 bg-gray-50 border-t border-gray-100 mt-auto">
                        <Button
                            @click="openConfirmDialog"
                            class="w-full h-14 text-base font-bold shadow-lg bg-primary hover:bg-primary/90"
                            :disabled="form.items.length === 0 || form.processing || isUnderpaid"
                        >
                            <div class="flex items-center justify-center gap-2">
                                <Loader2 v-if="form.processing" class="h-5 w-5 animate-spin" />
                                <Save v-else class="h-5 w-5" />
                                <span>{{ form.processing ? 'MEMPROSES...' : 'SIMPAN & CETAK NOTA' }}</span>
                                <kbd v-if="!form.processing" class="hidden md:inline-flex ml-2 h-5 items-center gap-1 rounded border border-primary-foreground/30 bg-primary-foreground/10 px-1.5 font-mono text-[10px] font-medium text-white opacity-90">F9</kbd>
                            </div>
                        </Button>
                    </div>
                </Card>
            </div>

        </div>
    </div>

    <!-- ============================================================ -->
    <!-- DIALOG KONFIRMASI SUBMIT TRANSAKSI (Issue #8)                -->
    <!-- ============================================================ -->
    <Dialog :open="showConfirmDialog" @update:open="showConfirmDialog = $event">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-lg">
                    <Save class="h-5 w-5 text-primary" />
                    Konfirmasi Transaksi
                </DialogTitle>
                <DialogDescription class="mt-1">
                    Pastikan semua detail pesanan dan pembayaran sudah benar sebelum disimpan.
                </DialogDescription>
            </DialogHeader>

            <!-- Ringkasan singkat transaksi -->
            <div class="my-4 space-y-3 text-sm">
                <div class="flex justify-between items-center py-2 border-b border-dashed">
                    <span class="text-gray-500">Total Item</span>
                    <span class="font-semibold">{{ form.items.length }} item</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-dashed">
                    <span class="text-gray-500">Subtotal</span>
                    <span class="font-medium">{{ formatRupiah(subtotal) }}</span>
                </div>
                <div v-if="discountAmount > 0" class="flex justify-between items-center py-2 border-b border-dashed text-green-600">
                    <span>Diskon</span>
                    <span class="font-medium">- {{ formatRupiah(discountAmount) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 bg-primary/5 rounded-lg px-3">
                    <span class="font-bold text-primary text-base">TOTAL TAGIHAN</span>
                    <span class="font-bold text-primary text-xl">{{ formatRupiah(totalFinal) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 px-3">
                    <span class="text-gray-500">Metode Bayar</span>
                    <span class="font-semibold uppercase">{{ form.payment_method }}</span>
                </div>
                <div v-if="form.payment_method === 'cash'" class="flex justify-between items-center py-2 px-3 bg-green-50 rounded-lg">
                    <span class="text-gray-600">Kembalian</span>
                    <span class="font-bold text-green-600 text-lg">{{ formatRupiah(changeAmount) }}</span>
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button variant="outline" @click="showConfirmDialog = false" class="flex-1">
                    Periksa Ulang
                </Button>
                <Button @click="submitTransaction" class="flex-1 bg-primary hover:bg-primary/90">
                    <Save class="h-4 w-4 mr-2" />
                    Ya, Simpan Transaksi
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- ============================================================ -->
    <!-- DIALOG TAMBAH PELANGGAN INLINE (Issue #10)                   -->
    <!-- ============================================================ -->
    <Dialog :open="showAddCustomerDialog" @update:open="showAddCustomerDialog = $event">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <UserPlus class="h-5 w-5 text-primary" />
                    Daftarkan Pelanggan Baru
                </DialogTitle>
                <DialogDescription>
                    Data pelanggan akan langsung tersedia di dropdown setelah disimpan.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 my-2">
                <div class="space-y-1.5">
                    <Label for="new_name">Nama Lengkap <span class="text-red-500">*</span></Label>
                    <Input
                        id="new_name"
                        v-model="newCustomerForm.name"
                        placeholder="Contoh: Budi Santoso"
                        :class="newCustomerForm.errors.name ? 'border-red-500' : ''"
                    />
                    <p v-if="newCustomerForm.errors.name" class="text-xs text-red-500">{{ newCustomerForm.errors.name }}</p>
                </div>
                <div class="space-y-1.5">
                    <Label for="new_phone">Nomor HP / WhatsApp</Label>
                    <Input id="new_phone" v-model="newCustomerForm.phone" placeholder="Contoh: 0812xxxx" />
                </div>
                <div class="space-y-1.5">
                    <Label for="new_address">Alamat</Label>
                    <Input id="new_address" v-model="newCustomerForm.address" placeholder="Opsional" />
                </div>
            </div>

            <!-- Error umum dari server -->
            <p v-if="newCustomerForm.hasErrors && !newCustomerForm.errors.name" class="text-xs text-red-500 -mt-2">
                Terjadi kesalahan. Periksa kembali data yang diisi.
            </p>

            <DialogFooter class="gap-2">
                <Button variant="outline" @click="showAddCustomerDialog = false; newCustomerForm.reset()" class="flex-1">
                    Batal
                </Button>
                <Button
                    @click="submitNewCustomer"
                    :disabled="isAddingCustomer || !newCustomerForm.name"
                    class="flex-1 bg-primary hover:bg-primary/90"
                >
                    <Loader2 v-if="isAddingCustomer" class="h-4 w-4 mr-2 animate-spin" />
                    <UserPlus v-else class="h-4 w-4 mr-2" />
                    {{ isAddingCustomer ? 'Menyimpan...' : 'Simpan Pelanggan' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

        <!-- Delete Item Confirmation Modal -->
        <Dialog :open="isDeleteModalOpen" @update:open="val => { if (!val) isDeleteModalOpen = false; }">
            <DialogContent class="sm:max-w-[400px]">
                <DialogHeader>
                    <DialogTitle>Hapus Item Keranjang</DialogTitle>
                </DialogHeader>
                <div class="py-4">
                    <p class="text-sm text-gray-500">
                        Apakah Anda yakin ingin menghapus layanan ini dari keranjang pesanan?
                    </p>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="isDeleteModalOpen = false">Batal</Button>
                    <Button type="button" variant="destructive" @click="executeRemoveItem" class="bg-red-600 hover:bg-red-700">Ya, Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Reset Kasir Confirmation Modal -->
        <Dialog :open="isResetModalOpen" @update:open="val => { if (!val) isResetModalOpen = false; }">
            <DialogContent class="sm:max-w-[400px]">
                <DialogHeader>
                    <DialogTitle>Reset Kasir</DialogTitle>
                </DialogHeader>
                <div class="py-4">
                    <p class="text-sm text-gray-500">
                        Apakah Anda yakin ingin mereset kasir? Semua item di keranjang dan pengaturan pembayaran akan dihapus.
                    </p>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="isResetModalOpen = false">Batal</Button>
                    <Button type="button" variant="destructive" @click="executeResetKasir" class="bg-red-600 hover:bg-red-700">Ya, Reset Kasir</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
