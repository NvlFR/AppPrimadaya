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
import { Skeleton } from '@/components/ui/skeleton';
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
// Flag: setelah tambah pelanggan baru, otomatis pilih hasil pertama yang muncul
const autoSelectAfterSearch = ref(false);

// ============================================================
// Custom Pricing — Harga Khusus per Pelanggan
// Map: service_id → custom_price (angka)
// Dikosongkan otomatis saat ganti/hapus pelanggan
// ============================================================
const customerCustomPrices = ref<Record<number, number>>({});
const isFetchingCustomPrices = ref(false);

/**
 * Fetch daftar harga khusus untuk pelanggan yang dipilih.
 * Endpoint: GET /customers/{id}/custom-prices
 * Return: { [service_id]: price }
 */
const fetchCustomPrices = async (customerId: number) => {
    isFetchingCustomPrices.value = true;
    try {
        const res = await fetch(`/customers/${customerId}/custom-prices`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (res.ok) {
            customerCustomPrices.value = await res.json();
            // Jika sudah ada item di keranjang, update harga item yang punya harga khusus
            form.items.forEach((item) => {
                const serviceId = parseInt(item.service_id);
                if (customerCustomPrices.value[serviceId] !== undefined) {
                    item.unit_price = customerCustomPrices.value[serviceId];
                }
            });
        }
    } catch (err) {
        console.error('Gagal mengambil harga khusus pelanggan:', err);
    } finally {
        isFetchingCustomPrices.value = false;
    }
};

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
                // Auto-pilih hasil pertama jika flag aktif (setelah tambah pelanggan baru)
                if (autoSelectAfterSearch.value && customerSearchResults.value.length > 0) {
                    selectCustomer(customerSearchResults.value[0]);
                    autoSelectAfterSearch.value = false;
                }
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
    // Fetch harga khusus pelanggan ini untuk auto-apply ke item
    fetchCustomPrices(customer.id);
};

/**
 * Mereset pilihan pelanggan ke Pelanggan Umum.
 * Harga khusus juga direset — semua item kembali ke harga normal.
 */
const clearCustomer = () => {
    form.customer_id = 'none';
    selectedCustomerLabel.value = 'Pelanggan Umum (Tidak Terafiliasi)';
    customerSearchQuery.value = '';
    customerSearchResults.value = [];
    isCustomerDropdownOpen.value = false;
    // Bersihkan custom prices — harga kembali ke base_price layanan
    customerCustomPrices.value = {};
    // Reset unit_price item yang mungkin sudah diubah ke custom price
    form.items.forEach((item) => {
        const service = props.services.find(s => s.id === parseInt(item.service_id));
        if (service && !service.has_matrix_pricing && !service.is_per_meter) {
            item.unit_price = parseFloat(service.base_price);
        }
    });
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
        // Dimensi untuk layanan per-meter (contoh: Spanduk)
        width: number | undefined;
        height: number | undefined;
    }>,
    discount_type: 'percent' as 'percent' | 'flat',
    discount_value: 0,
    notes: '',
});

// ============================================================
// State internal kasir
// ============================================================
const selectedServiceId = ref('');

// -- State untuk Dialog Konfirmasi Submit (Issue #8) --
const showConfirmDialog = ref(false);
const showCheckoutWarningDialog = ref(false);
const checkoutWarningTitle = ref('');
const checkoutWarningMessage = ref('');

// -- State untuk Dialog Tambah Pelanggan Inline (Issue #10) --
const showAddCustomerDialog = ref(false);
const newCustomerForm = useForm({
    name: '',
    phone: '',
    address: '',
    notes: '',
});
const isAddingCustomer = ref(false);

/**
 * Membuka dialog pendaftaran pelanggan baru.
 * Jika dipicu dari search (bukan dari tombol manual), pre-fill nama dari query pencarian.
 */
const openAddCustomerDialog = (prefillName?: string) => {
    newCustomerForm.reset();
    if (prefillName) {
        newCustomerForm.name = prefillName;
    }
    showAddCustomerDialog.value = true;
    isCustomerDropdownOpen.value = false;
};

// ============================================================
// Keyboard Shortcuts (Issue #4 — sudah ada, dipertahankan)
// ============================================================

// F2: Fokus ke dropdown pemilihan layanan
onKeyStroke('F2', (e) => {
    e.preventDefault();
    const trigger = document.querySelector('[data-search-trigger]') as HTMLElement;
    trigger?.focus();
});

// F4: (Reserved — tidak dipakai di alur baru)

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

    const isPerMeter = service.is_per_meter;
    // Harga awal: cek custom price terlebih dahulu, fallback ke base_price
    // Custom price hanya berlaku untuk layanan non-matrix dan non-per-meter
    const customPrice = customerCustomPrices.value[service.id];
    const initialPrice = (customPrice !== undefined && !service.has_matrix_pricing && !isPerMeter)
        ? customPrice
        : parseFloat(service.base_price);

    form.items.push({
        service_id: service.id.toString(),
        paper_size_id: null,
        print_type: service.has_matrix_pricing ? 'bw' : 'na',
        qty: 1,
        unit_price: initialPrice,
        item_notes: '',
        file: null,
        width: isPerMeter ? 1 : undefined,
        height: isPerMeter ? 1 : undefined,
    });

    selectedServiceId.value = ''; // reset dropdown
};

const findServiceByItem = (item: { service_id: string }) =>
    props.services.find(service => service.id === parseInt(item.service_id));

const normalizeQty = (item: { qty: number }) => {
    item.qty = Math.max(1, Number(item.qty) || 1);
};

const getItemArea = (item: { width: number | undefined; height: number | undefined }) => {
    const width = Number(item.width) || 0;
    const height = Number(item.height) || 0;

    return width * height;
};

const getItemUnitPrice = (item: {
    service_id: string;
    unit_price: number;
    width: number | undefined;
    height: number | undefined;
}) => {
    const service = findServiceByItem(item);

    if (service?.is_per_meter) {
        return parseFloat(service.base_price) * getItemArea(item);
    }

    return Number(item.unit_price) || 0;
};

const getItemSubtotal = (item: {
    service_id: string;
    unit_price: number;
    qty: number;
    width: number | undefined;
    height: number | undefined;
}) => getItemUnitPrice(item) * Math.max(1, Number(item.qty) || 1);

/**
 * Menghitung ulang unit_price berdasarkan dimensi (lebar × tinggi) untuk layanan per-meter.
 * Dipanggil setiap kali input dimensi berubah.
 */
const updateDimensions = (index: number) => {
    const item = form.items[index];
    const service = props.services.find(s => s.id === parseInt(item.service_id));
    if (service?.is_per_meter) {
        const w = Number(item.width) || 0;
        const h = Number(item.height) || 0;
        item.unit_price = parseFloat(service.base_price) * w * h;
    }
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

const updateFormattedItemPrice = (index: number, value: string | number) => {
    const normalizedValue = String(value).replace(/[^\d]/g, '');
    form.items[index].unit_price = normalizedValue ? Number(normalizedValue) : 0;
};

// ============================================================
// Perhitungan Total Otomatis
// ============================================================
const subtotal = computed(() =>
    form.items.reduce((sum, item) => sum + getItemSubtotal(item), 0)
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
const paymentMethodLabel = computed(() => 'Belum Dibayar');

const hasSelectedCustomer = computed(() => form.customer_id !== 'none');
const isCartEmpty = computed(() => form.items.length === 0);

const { formatRupiah, formatAngka } = useFormatRupiah();
const totalItemCount = computed(() =>
    form.items.reduce((sum, item) => sum + (Number(item.qty) || 0), 0)
);

// ============================================================
// Fungsi Reset Kasir
// ============================================================
const isResetModalOpen = ref(false);
const checkoutAttempted = ref(false);
const checkoutWarnings = ref<string[]>([]);

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
    form.discount_type = 'percent';
    form.discount_value = 0;
    form.customer_id = 'none';
    selectedServiceId.value = '';
    
    // Reset state combobox pelanggan (Issue #25)
    customerSearchQuery.value = '';
    isCustomerDropdownOpen.value = false;
    clearCustomer();
    
    isResetModalOpen.value = false;
};

// ============================================================
// Dialog Konfirmasi Submit (Issue #8)
// ============================================================
const openConfirmDialog = () => {
    checkoutAttempted.value = true;

    const issues: string[] = [];

    if (!hasSelectedCustomer.value) {
        issues.push('Pelanggan belum dipilih. Pilih pelanggan terlebih dahulu sebelum menyimpan pesanan.');
    }

    if (isCartEmpty.value) {
        issues.push('Keranjang belanja masih kosong. Tambahkan minimal satu layanan.');
    }

    if (issues.length > 0) {
        checkoutWarningTitle.value = 'Pesanan Belum Lengkap';
        checkoutWarningMessage.value = '';
        checkoutWarnings.value = issues;
        showCheckoutWarningDialog.value = true;
        return;
    }

    checkoutWarnings.value = [];
    showConfirmDialog.value = true;
};

// Proses Submit ke Server — dipanggil setelah konfirmasi
const submitTransaction = () => {
    showConfirmDialog.value = false;

    form.transform((data) => ({
        ...data,
        customer_id: data.customer_id === 'none' ? '' : data.customer_id,
        items: data.items.map((item) => ({
            ...item,
            qty: Math.max(1, Number(item.qty) || 1),
            unit_price: getItemUnitPrice(item),
        })),
        // Kirim discount_amount yang dihitung agar backend tidak perlu hitung ulang
        discount_amount: discountAmount.value,
    })).post(route('transactions.store'), {
        forceFormData: true,
        preserveScroll: true,
        onError: () => {
            checkoutAttempted.value = true;
        },
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
            const newName = newCustomerForm.name;
            showAddCustomerDialog.value = false;
            newCustomerForm.reset();
            // Aktifkan flag auto-select lalu trigger search — hasil pertama otomatis dipilih
            autoSelectAfterSearch.value = true;
            customerSearchQuery.value = newName;
            isCustomerDropdownOpen.value = false;
        },
        onFinish: () => {
            isAddingCustomer.value = false;
        },
    });
};

const shouldShowCustomerError = computed(() =>
    checkoutAttempted.value && !hasSelectedCustomer.value
);
const shouldShowCartError = computed(() =>
    checkoutAttempted.value && isCartEmpty.value
);
</script>

<template>
    <AppLayout :breadcrumbs="[
        { title: 'Dashboard', href: route('dashboard') },
        { title: 'Kasir Baru', href: route('transactions.create') }
    ]">
    <Head title="Transaksi Baru" />

    <div class="relative mx-auto w-full max-w-[1440px] overflow-x-clip px-4 py-5 md:px-6 xl:px-8">
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-72 overflow-hidden">
            <div class="absolute left-[-4rem] top-8 h-52 w-52 rounded-full bg-blue-200/25 blur-3xl"></div>
            <div class="absolute right-[-3rem] top-16 h-64 w-64 rounded-full bg-sky-200/20 blur-3xl"></div>
        </div>

        <div class="relative flex flex-col gap-4 rounded-2xl border border-slate-200/80 bg-white/90 p-5 shadow-[0_10px_30px_rgba(15,23,42,0.06)] backdrop-blur-sm sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-3">
                <div>
                    <!-- <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-primary/80">Transaksi</p> -->
                    <h2 class="mt-2 flex items-center text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">
                        <ShoppingCart class="mr-3 h-7 w-7 text-primary" /> Transaksi
                    </h2>
                    <!-- <p class="mt-1 max-w-2xl text-sm text-slate-500">Pilih pelanggan, susun layanan, lalu selesaikan pembayaran dengan alur yang cepat dan jelas.</p> -->
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-600 shadow-sm">
                        <User class="h-3.5 w-3.5 text-primary" />
                        {{ selectedCustomerLabel }}
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 font-medium text-slate-600">
                        <FileText class="h-3.5 w-3.5 text-slate-400" />
                        {{ form.items.length }} item
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 font-medium text-slate-600">
                        <ShoppingCart class="h-3.5 w-3.5 text-slate-400" />
                        {{ formatRupiah(totalFinal) }}
                    </span>
                </div>
            </div>
            <Button variant="outline" class="w-full border-slate-200 bg-white text-slate-700 shadow-sm transition-colors hover:border-primary/30 hover:bg-primary/5 sm:w-auto" @click="resetKasir">
                <RefreshCw class="mr-2 h-4 w-4" /> Reset Kasir
            </Button>
        </div>

        <!-- Layout Kiri (Keranjang & Setup) - Kanan (Ringkasan & Bayar) -->
        <div class="relative z-10 mt-5 grid min-w-0 grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">

            <!-- KOLOM KIRI: Keranjang Belanja -->
            <div class="flex min-w-0 flex-col gap-5">

                <!-- 1. Block Informasi Pelanggan -->
                <Card class="min-w-0 rounded-2xl border border-slate-200/80 bg-white/95 shadow-sm">
                    <CardHeader class="pb-4">
                        <CardTitle class="flex items-center gap-2 text-lg font-semibold text-slate-900">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <User class="h-4 w-4" />
                            </span>
                            Informasi Pelanggan
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Combobox Async Pelanggan — Issue #25 -->
                        <!-- Pencarian asinkron: tidak load semua data saat halaman dibuka -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between min-h-[32px]">
                                    <Label>Pilih Pelanggan</Label>
                                    <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-6 text-xs text-primary hover:text-primary/80 px-2"
                                    @click="openAddCustomerDialog()"
                                >
                                    <UserPlus class="h-3 w-3 mr-1" /> Daftarkan Baru
                                </Button>
                            </div>

                            <!-- Trigger combobox — menampilkan pelanggan terpilih -->
                            <div ref="customerComboboxRef" class="relative">
                                <button
                                    type="button"
                                    class="flex h-11 w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm shadow-sm ring-offset-background transition-colors focus:outline-none focus:ring-2 focus:ring-primary/20 hover:border-primary/30 hover:bg-slate-50"
                                    :class="isCustomerDropdownOpen ? 'ring-1 ring-ring' : ''"
                                    @click="isCustomerDropdownOpen = !isCustomerDropdownOpen"
                                >
                                    <span :class="form.customer_id === 'none' ? 'text-muted-foreground' : 'text-foreground'">
                                        {{ form.customer_id === 'none' ? 'Tambah Pelanggan' : selectedCustomerLabel }}
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



                                <div
                                    v-if="shouldShowCustomerError || form.errors.customer_id"
                                    class="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"
                                >
                                    <AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                    <span>{{ form.errors.customer_id || 'Pelanggan wajib dipilih sebelum transaksi dapat disimpan.' }}</span>
                                </div>

                                <!-- Dropdown results -->
                                <div
                                    v-if="isCustomerDropdownOpen"
                                    class="absolute z-50 mt-1 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl"
                                >
                                    <!-- Input pencarian -->
                                    <div class="border-b border-slate-100 p-2">
                                        <div class="relative">
                                            <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground" />
                                            <input
                                                v-model="customerSearchQuery"
                                                type="text"
                                                placeholder="Ketik nama atau no. HP..."
                                                class="h-9 w-full rounded-lg border border-input bg-transparent pl-8 pr-3 text-sm focus:outline-none focus:ring-1 focus:ring-primary/25"
                                                autofocus
                                                @keydown.escape="isCustomerDropdownOpen = false"
                                            />
                                        </div>
                                    </div>

                                    <!-- List hasil pencarian -->
                                    <div class="max-h-48 overflow-y-auto py-1">


                                        <!-- Loading state (Skeleton) -->
                                        <div v-if="isSearchingCustomer" class="p-2 space-y-3">
                                            <div v-for="i in 3" :key="i" class="flex items-center px-1">
                                                <Skeleton class="h-6 w-6 rounded-full mr-3" />
                                                <div class="space-y-2 w-full">
                                                    <Skeleton class="h-3 w-1/2" />
                                                    <Skeleton class="h-2 w-1/3" />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Hasil pencarian -->
                                        <template v-else-if="customerSearchResults.length > 0">
                                            <button
                                                v-for="c in customerSearchResults"
                                                :key="c.id"
                                                type="button"
                                                class="w-full flex items-center px-3 py-2.5 text-sm hover:bg-slate-50 transition-colors text-left"
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

                                        <!-- Empty state: auto-detect tidak ada hasil → tawarkan daftar baru -->
                                        <div
                                            v-else-if="customerSearchQuery.trim() && !isSearchingCustomer"
                                            class="py-2"
                                        >
                                            <p class="px-3 py-2 text-xs text-slate-400 text-center">
                                                Pelanggan "<span class="font-medium text-slate-600">{{ customerSearchQuery }}</span>" tidak ditemukan.
                                            </p>
                                            <button
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2.5 text-sm font-medium text-primary hover:bg-primary/5 transition-colors border-t border-slate-100"
                                                @click="openAddCustomerDialog(customerSearchQuery)"
                                            >
                                                <UserPlus class="h-4 w-4 shrink-0" />
                                                Tambah "{{ customerSearchQuery }}" sebagai pelanggan baru
                                            </button>
                                        </div>

                                        <!-- Prompt awal sebelum mengetik -->
                                        <div
                                            v-else-if="!customerSearchQuery.trim()"
                                            class="py-5 text-center text-xs text-slate-400"
                                        >
                                            Ketik nama atau no. HP untuk mencari...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center min-h-[32px]">
                                <Label>Catatan Umum Pesanan</Label>
                            </div>
                            <Input v-model="form.notes" placeholder="Catatan untuk tim produksi..." class="h-11 rounded-xl shadow-sm" />
                        </div>
                    </CardContent>
                </Card>

                <!-- 2. Block Keranjang Belanja -->
                <Card class="flex min-w-0 min-h-[420px] flex-col rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                    <CardHeader class="rounded-t-2xl border-b border-slate-200/80 bg-gradient-to-r from-slate-50 to-white pb-4">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <CardTitle class="flex items-center gap-2 text-lg font-semibold text-slate-900">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900/5 text-slate-600">
                                    <FileText class="h-4 w-4" />
                                </span>
                                Keranjang Belanja
                            </CardTitle>
                            <div class="flex items-center gap-2 w-full md:w-[400px]">
                                <div class="relative w-full">
                                    <Select v-model="selectedServiceId">
                                        <SelectTrigger class="h-10 rounded-xl border-slate-200 bg-white shadow-sm" data-search-trigger>
                                            <SelectValue placeholder="Pilih layanan..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="s in services" :key="s.id" :value="s.id.toString()">
                                                {{ s.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>

                        <!-- Layanan Favorit -->
                        <div class="mt-4 flex items-center gap-2 overflow-x-auto pb-0.5">
                            <span class="shrink-0 text-[10px] font-bold text-gray-400 uppercase flex items-center mr-1"><Zap class="w-3 h-3 mr-1" /> Favorit:</span>
                            <Button
                                v-for="s in pinnedServices"
                                :key="s.id"
                                variant="outline"
                                size="sm"
                                class="h-8 shrink-0 rounded-full border-dashed bg-white text-xs shadow-sm transition-all hover:border-primary hover:text-primary"
                                @click="addItem(s.id)"
                            >
                                {{ s.name }}
                            </Button>
                        </div>

                        <div
                            v-if="shouldShowCartError || form.errors.items"
                            class="mt-4 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"
                        >
                            <AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                            <span>{{ form.errors.items || 'Keranjang belanja masih kosong. Tambahkan minimal satu layanan.' }}</span>
                        </div>
                    </CardHeader>

                    <CardContent class="flex-1 min-w-0 overflow-x-auto p-0">
                        <div v-if="form.items.length === 0" class="flex min-h-[260px] flex-col items-center justify-center px-6 py-8 text-center text-slate-500 sm:min-h-[300px] sm:px-8">
                            <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-slate-100 text-slate-300 shadow-inner">
                                <ShoppingCart class="h-9 w-9" />
                            </div>
                            <p class="text-base font-semibold text-slate-700">Keranjang masih kosong.</p>
                            <p class="mt-1 max-w-sm text-sm leading-6 text-slate-500">Gunakan dropdown layanan atau tombol favorit untuk menambahkan item pertama.</p>
                            <div class="mt-4 flex flex-wrap justify-center gap-2 text-[11px]">
                                <span class="rounded-full border border-slate-200 bg-white px-3 py-1 shadow-sm">Tekan <b>F2</b> untuk cari layanan</span>
                                <span class="rounded-full border border-slate-200 bg-white px-3 py-1 shadow-sm">Tambah item dengan satu klik</span>
                            </div>
                        </div>

                        <!-- Tampilan Mobile (Card List) -->
                        <div v-else class="block divide-y divide-gray-100 md:hidden">
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

                                <!-- Mobile: Matrix Pricing -->
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

                                <!-- Mobile: Per-Meter (Spanduk) -->
                                <div v-else-if="services.find(s => s.id == item.service_id)?.is_per_meter" class="space-y-2">
                                    <div class="grid grid-cols-3 gap-2">
                                        <div class="space-y-1">
                                            <Label class="text-[10px] text-gray-400 uppercase">Lebar (m)</Label>
                                            <Input v-model.number="item.width" type="number" step="0.1" min="0.1" class="h-9 text-center bg-white" @input="updateDimensions(index)" />
                                        </div>
                                        <div class="space-y-1">
                                            <Label class="text-[10px] text-gray-400 uppercase">Tinggi (m)</Label>
                                            <Input v-model.number="item.height" type="number" step="0.1" min="0.1" class="h-9 text-center bg-white" @input="updateDimensions(index)" />
                                        </div>
                                        <div class="space-y-1">
                                            <Label class="text-[10px] text-gray-400 uppercase">Luas (m²)</Label>
                                            <div class="flex h-9 items-center justify-center rounded-md border border-indigo-200 bg-indigo-50 text-sm font-bold text-indigo-700">
                                                {{ getItemArea(item).toFixed(2) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="rounded-lg border border-indigo-100 bg-indigo-50/60 px-3 py-1.5 text-[11px] text-indigo-600 font-mono">
                                        {{ getItemArea(item).toFixed(2) }} m² × {{ formatRupiah(services.find(s => s.id == item.service_id)?.base_price || 0) }}/m² × {{ item.qty }} pcs = <strong>{{ formatRupiah(getItemSubtotal(item)) }}</strong>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-4">
                                    <div class="w-1/3">
                                        <Label class="text-[10px] text-gray-400 uppercase">
                                            {{ findServiceByItem(item)?.is_per_meter ? 'Harga /meter' : 'Harga' }}
                                        </Label>
                                        <div
                                            v-if="findServiceByItem(item)?.is_per_meter"
                                            class="mt-0.5 flex h-9 items-center justify-end rounded-md border border-indigo-200 bg-indigo-50 px-3 text-xs font-bold text-indigo-800"
                                        >
                                            {{ formatRupiah(findServiceByItem(item)?.base_price || 0) }}
                                        </div>
                                        <Input
                                            v-else
                                            :model-value="formatAngka(item.unit_price)"
                                            type="text"
                                            inputmode="numeric"
                                            class="h-9 text-right bg-white mt-0.5"
                                            @update:model-value="value => updateFormattedItemPrice(index, value)"
                                        />
                                    </div>
                                    <div class="w-1/4">
                                        <Label class="text-[10px] text-gray-400 uppercase">Qty</Label>
                                        <Input v-model.number="item.qty" type="number" min="1" class="h-9 text-center bg-white mt-0.5" @update:model-value="() => normalizeQty(item)" />
                                    </div>
                                    <div class="flex-1 text-right pt-4">
                                        <p class="text-xs text-gray-500">Subtotal</p>
                                        <p class="font-bold text-gray-900">{{ formatRupiah(getItemSubtotal(item)) }}</p>
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

                        <!-- Desktop POS Item List -->
                        <div v-if="form.items.length > 0" class="hidden min-w-0 flex-col gap-3 p-4 md:flex">
                            <div
                                v-for="(item, index) in form.items"
                                :key="index"
                                class="min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all hover:border-primary/40 hover:shadow-md"
                            >
                                <!-- Baris 1: Header item — nama + badge subtotal + tombol hapus -->
                                <div class="flex items-center justify-between gap-3 border-b border-gray-100 bg-gray-50/60 px-4 py-2.5">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2.5">
                                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-white">
                                                {{ index + 1 }}
                                            </span>
                                            <p class="truncate text-sm font-semibold text-gray-900">
                                                {{ services.find(s => s.id == item.service_id)?.name }}
                                            </p>
                                            <span class="shrink-0 rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                                                {{ formatRupiah(getItemSubtotal(item)) }}
                                            </span>
                                        </div>
                                    </div>
                                    <Button @click="confirmRemoveItem(index)" variant="ghost" size="icon" class="h-7 w-7 shrink-0 text-red-400 hover:bg-red-50 hover:text-red-600">
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </Button>
                                </div>

                                <!-- Baris 2: Catatan + upload file -->
                                <div class="flex items-center gap-3 px-4 pt-3">
                                    <div class="flex-1 min-w-0">
                                        <Input v-model="item.item_notes" placeholder="Catatan item, contoh: pakai spiral" class="h-8 bg-white text-xs" />
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1.5">
                                        <input type="file" :id="`file-${index}`" class="hidden" @change="(e: any) => item.file = e.target.files[0]">
                                        <label
                                            :for="`file-${index}`"
                                            class="inline-flex cursor-pointer items-center gap-1 rounded-md border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-[10px] font-semibold uppercase text-blue-700 transition-colors hover:bg-blue-100"
                                        >
                                            <Plus class="h-3 w-3 text-blue-600" />
                                            <span class="max-w-[120px] truncate">{{ item.file ? item.file.name : 'Upload File' }}</span>
                                        </label>
                                        <Button v-if="item.file" variant="ghost" size="icon" @click="item.file = null" class="h-7 w-7 text-red-500">
                                            <Trash2 class="h-3 w-3" />
                                        </Button>
                                    </div>
                                </div>

                                <!-- Baris 3: Kontrol harga (flex-wrap agar tidak overflow) -->
                                <div class="flex min-w-0 flex-wrap items-end gap-3 px-4 pb-4 pt-3">

                                    <!-- ===== MATRIX PRICING (Fotocopy/Print): Pilih Ukuran & Tipe ===== -->
                                    <template v-if="services.find(s => s.id == item.service_id)?.has_matrix_pricing">
                                        <div class="space-y-1">
                                            <Label class="text-[10px] font-bold uppercase text-gray-400">Ukuran Kertas</Label>
                                            <Select v-model="item.paper_size_id" @update:modelValue="updateItemPrice(index)">
                                                <SelectTrigger class="h-9 w-[130px] bg-white text-xs"><SelectValue placeholder="Pilih Ukuran" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="ps in paper_sizes" :key="ps.id" :value="ps.id.toString()">Kertas {{ ps.name }}</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div class="space-y-1">
                                            <Label class="text-[10px] font-bold uppercase text-gray-400">Tipe Cetak</Label>
                                            <Select v-model="item.print_type" @update:modelValue="updateItemPrice(index)">
                                                <SelectTrigger class="h-9 w-[120px] bg-white text-xs"><SelectValue placeholder="Warna/BW" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="color">Warna Full</SelectItem>
                                                    <SelectItem value="bw">Hitam Putih</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </template>

                                    <!-- ===== PER METER (Spanduk): Input Lebar × Tinggi ===== -->
                                    <template v-else-if="services.find(s => s.id == item.service_id)?.is_per_meter">
                                        <!-- Baris dimensi: Lebar × Tinggi = Luas -->
                                        <div class="flex flex-wrap items-end gap-2">
                                            <div class="space-y-1">
                                                <Label class="text-[10px] font-bold uppercase text-gray-400">Lebar (m)</Label>
                                                <Input
                                                    v-model.number="item.width"
                                                    type="number"
                                                    step="0.1"
                                                    min="0.5"
                                                    class="h-9 w-[80px] bg-white text-center font-medium"
                                                    @input="updateDimensions(index)"
                                                />
                                            </div>
                                            <span class="mb-2 text-lg font-bold text-gray-300">×</span>
                                            <div class="space-y-1">
                                                <Label class="text-[10px] font-bold uppercase text-gray-400">Tinggi (m)</Label>
                                                <Input
                                                    v-model.number="item.height"
                                                    type="number"
                                                    step="0.1"
                                                    min="0.5"
                                                    class="h-9 w-[80px] bg-white text-center font-medium"
                                                    @input="updateDimensions(index)"
                                                />
                                            </div>
                                            <span class="mb-2 text-lg font-bold text-gray-300">=</span>
                                            <div class="space-y-1">
                                                <Label class="text-[10px] font-bold uppercase text-gray-400">Luas (m²)</Label>
                                                <div class="flex h-9 w-[80px] items-center justify-center rounded-md border border-indigo-200 bg-indigo-50 text-sm font-bold text-indigo-700">
                                                    {{ getItemArea(item).toFixed(2) }}
                                                </div>
                                            </div>
                                            <span class="mb-2 text-lg font-bold text-gray-300">×</span>
                                            <!-- Tarif per m² -->
                                            <div class="space-y-1">
                                                <Label class="text-[10px] font-bold uppercase text-gray-400">Tarif /m²</Label>
                                                <div class="flex h-9 items-center rounded-md border border-dashed bg-gray-50 px-3 text-xs font-semibold text-gray-600">
                                                    {{ formatRupiah(services.find(s => s.id == item.service_id)?.base_price || 0) }}
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Keterangan rumus harga -->
                                        <div class="flex items-center gap-1.5 rounded-lg border border-indigo-100 bg-indigo-50/60 px-3 py-1.5 text-[11px] text-indigo-600">
                                            <span class="font-mono font-bold">{{ getItemArea(item).toFixed(2) }} m²</span>
                                            <span>×</span>
                                            <span class="font-mono font-bold">{{ formatRupiah(services.find(s => s.id == item.service_id)?.base_price || 0) }}/m²</span>
                                            <span>×</span>
                                            <span class="font-mono font-bold">{{ item.qty }} pcs</span>
                                            <span>=</span>
                                            <span class="font-mono font-bold text-indigo-800">{{ formatRupiah(getItemSubtotal(item)) }}</span>
                                        </div>
                                    </template>

                                    <!-- ===== SERVICE STANDAR (non-matrix, non-meter) ===== -->
                                    <template v-else>
                                        <div class="space-y-1">
                                            <Label class="text-[10px] font-bold uppercase text-gray-400">Tipe</Label>
                                            <div class="flex h-9 items-center rounded-md border border-dashed bg-gray-50 px-3 text-xs text-gray-400">Standar</div>
                                        </div>
                                    </template>

                                    <!-- Divider vertikal -->
                                    <div class="hidden h-9 w-px self-end bg-gray-200 lg:block"></div>

                                    <!-- Harga Satuan — hanya tampil untuk non-per-meter (spanduk sudah ada rumus di atas) -->
                                    <div v-if="!services.find(s => s.id == item.service_id)?.is_per_meter" class="space-y-1">
                                        <Label class="text-[10px] font-bold uppercase text-gray-400">Harga Satuan</Label>
                                        <Input
                                            :model-value="formatAngka(item.unit_price)"
                                            type="text"
                                            inputmode="numeric"
                                            class="h-9 w-[120px] bg-white text-right font-medium"
                                            @update:model-value="value => updateFormattedItemPrice(index, value)"
                                        />
                                    </div>

                                    <!-- Qty -->
                                    <div class="space-y-1">
                                        <Label class="text-[10px] font-bold uppercase text-gray-400">Qty</Label>
                                        <Input
                                            v-model.number="item.qty"
                                            type="number"
                                            class="h-9 w-[70px] bg-white text-center font-medium"
                                            min="1"
                                            @update:model-value="() => normalizeQty(item)"
                                        />
                                    </div>

                                    <!-- Subtotal (auto-hitung) -->
                                    <div class="space-y-1">
                                        <Label class="text-[10px] font-bold uppercase text-gray-400">Subtotal</Label>
                                        <div class="flex h-9 min-w-[120px] items-center justify-end rounded-md bg-primary/10 px-3 text-sm font-bold text-primary">
                                            {{ formatRupiah(getItemSubtotal(item)) }}
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- KOLOM KANAN: Pembayaran & Ringkasan -->
            <div class="min-w-0 xl:sticky xl:top-20">
                <Card class="flex flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_12px_35px_rgba(15,23,42,0.08)]">
                    <!-- Header Total -->
                    <div class="bg-[linear-gradient(135deg,#2563eb_0%,#1d4ed8_100%)] px-5 py-5 text-white">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="text-[11px] font-semibold uppercase tracking-[0.22em] text-white/75">Total Tagihan</span>
                                <h2 class="mt-1 break-words text-2xl font-bold tracking-tight md:text-3xl">{{ formatRupiah(totalFinal) }}</h2>
                            </div>
                            <div class="rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-white/80">
                                {{ paymentMethodLabel }}
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-white/70">Pengecekan akhir pesanan. Pastikan semua item sudah sesuai sebelum menyimpan.</p>
                    </div>

                    <CardContent class="flex-1 space-y-5 p-5">
                        <!-- Ringkasan Rincian -->
                        <div class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-slate-500">Subtotal Item ({{ form.items.length }})</span>
                                <span class="font-semibold text-slate-900">{{ formatRupiah(subtotal) }}</span>
                            </div>

                            <!-- Diskon dengan Toggle Persen / Flat (Issue #9) -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-slate-500">Diskon</span>
                                    <!-- Toggle persen / nominal -->
                                    <div class="flex items-center overflow-hidden rounded-xl border border-slate-200 bg-white text-xs shadow-sm">
                                        <button
                                            type="button"
                                            class="flex items-center gap-1 px-2.5 py-1.5 transition-colors"
                                            :class="form.discount_type === 'percent' ? 'bg-primary text-white' : 'bg-white text-gray-500 hover:bg-gray-50'"
                                            @click="form.discount_type = 'percent'; form.discount_value = 0"
                                        >
                                            <Percent class="h-3 w-3" /> %
                                        </button>
                                        <button
                                            type="button"
                                            class="flex items-center gap-1 px-2.5 py-1.5 transition-colors"
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
                                        class="h-9 rounded-xl text-right text-sm"
                                        :class="form.discount_type === 'flat' ? 'pl-8' : ''"
                                        :min="0"
                                        :max="form.discount_type === 'percent' ? 100 : subtotal"
                                        :placeholder="form.discount_type === 'percent' ? '0%' : '0'"
                                    />
                                </div>
                            </div>

                            <!-- Tampil potongan harga jika ada -->
                            <div v-if="discountAmount > 0" class="flex justify-between items-center border-t border-dashed pt-3 text-sm font-medium text-emerald-600">
                                <span>Potongan Harga</span>
                                <span>- {{ formatRupiah(discountAmount) }}</span>
                            </div>
                        </div>

                        <!-- Info tagihan — no payment at this stage -->
                        <!-- <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4 text-sm text-amber-800">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-amber-100">
                                    <AlertTriangle class="h-4 w-4 text-amber-500" />
                                </div>
                                <div>
                                    <p class="font-semibold text-amber-900">Pembayaran belum diperlukan</p>
                                    <p class="mt-0.5 text-xs text-amber-700 leading-relaxed">Pesanan akan disimpan dengan status <strong>Belum Dibayar</strong>. Proses pembayaran (lunas atau DP) dilakukan setelah pesanan selesai dikerjakan.</p>
                                </div>
                            </div>
                        </div> -->


                    </CardContent>

                    <!-- Tombol Submit -->
                    <div class="mt-auto border-t border-slate-200 bg-slate-50 p-5">
                        <Button
                            @click="openConfirmDialog"
                            class="h-12 w-full rounded-xl bg-primary text-sm font-bold shadow-lg shadow-primary/20 hover:bg-primary/90 md:text-base"
                            :disabled="form.processing"
                        >
                            <div class="flex items-center justify-center gap-2">
                                <Loader2 v-if="form.processing" class="h-5 w-5 animate-spin" />
                                <Save v-else class="h-5 w-5" />
                                <span>{{ form.processing ? 'MEMPROSES...' : 'SIMPAN PESANAN' }}</span>
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
    <Dialog :open="showCheckoutWarningDialog" @update:open="showCheckoutWarningDialog = $event">
        <DialogContent class="max-w-sm">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-lg">
                    <AlertTriangle class="h-5 w-5 text-amber-500" />
                    {{ checkoutWarningTitle }}
                </DialogTitle>
                <DialogDescription class="mt-1">
                    {{ checkoutWarningMessage || 'Lengkapi data berikut agar transaksi bisa dilanjutkan.' }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-2 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                <div v-for="warning in checkoutWarnings" :key="warning" class="flex items-start gap-2">
                    <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" />
                    <span>{{ warning }}</span>
                </div>
            </div>

            <DialogFooter>
                <Button @click="showCheckoutWarningDialog = false" class="w-full">
                    Oke, Perbaiki
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="showConfirmDialog" @update:open="showConfirmDialog = $event">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-lg">
                    <Save class="h-5 w-5 text-primary" />
                    Konfirmasi Transaksi
                </DialogTitle>
                <DialogDescription class="mt-1">
                    Pastikan semua detail pesanan sudah benar. Pembayaran dilakukan terpisah setelah pesanan selesai.
                </DialogDescription>
            </DialogHeader>

            <!-- Ringkasan singkat pesanan -->
            <div class="my-4 space-y-3 text-sm">
                <div class="flex justify-between items-center py-2 border-b border-dashed">
                    <span class="text-gray-500">Pelanggan</span>
                    <span class="font-semibold">{{ selectedCustomerLabel }}</span>
                </div>
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
                <div class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                    <AlertTriangle class="h-3.5 w-3.5 shrink-0 text-amber-500" />
                    <span>Status: <strong>Belum Dibayar</strong> — pembayaran akan diproses setelah pesanan selesai.</span>
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button variant="outline" @click="showConfirmDialog = false" class="flex-1">
                    Periksa Ulang
                </Button>
                <Button @click="submitTransaction" class="flex-1 bg-primary hover:bg-primary/90">
                    <Save class="h-4 w-4 mr-2" />
                    Ya, Simpan Pesanan
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
                        Apakah Anda yakin ingin mereset kasir? Semua item di keranjang akan dihapus.
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
