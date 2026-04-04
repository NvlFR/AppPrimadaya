<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Trash2, ShoppingCart, Plus, Save, User, FileText, ChevronRight } from 'lucide-vue-next';

const props = defineProps<{
    services: Array<any>;
    customers: Array<any>;
    paper_sizes: Array<any>;
}>();

// Form Data via Inertia untuk dikirim ke Backend
const form = useForm({
    customer_id: '',
    items: [] as Array<{
        service_id: string;
        paper_size_id: string | null;
        print_type: string;
        qty: number;
        unit_price: number;
        item_notes: string;
        file: File | null;
    }>,
    discount_percent: 0,
    payment_method: 'cash',
    amount_paid: 0,
    notes: '',
});

// State internal kasir
const selectedServiceId = ref('');
const showPaymentModal = ref(false);

// Fungsi Menambahkan Layanan ke Keranjang
const addItem = () => {
    if (!selectedServiceId.value) return;
    
    const service = props.services.find(s => s.id === parseInt(selectedServiceId.value));
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

// Fungsi Hapus Item dari Keranjang
const removeItem = (index: number) => {
    form.items.splice(index, 1);
};

// Fungsi Update Harga Dinamis bila User mengganti ukuran kertas / jenis cetak
const updateItemPrice = (index: number) => {
    const item = form.items[index];
    const service = props.services.find(s => s.id === parseInt(item.service_id));
    
    if (service && service.has_matrix_pricing) {
        // Cari harga di array prices milik service tersebut
        const priceObj = service.prices.find((p: any) => 
            p.paper_size_id == item.paper_size_id && p.print_type === item.print_type
        );
        
        item.unit_price = priceObj ? parseFloat(priceObj.price) : parseFloat(service.base_price);
    }
};

// -- Perhitungan Total Otomatis -- //
const subtotal = computed(() => {
    return form.items.reduce((sum, item) => sum + (item.unit_price * item.qty), 0);
});

const discountAmount = computed(() => {
    return subtotal.value * (form.discount_percent / 100);
});

const totalFinal = computed(() => {
    return subtotal.value - discountAmount.value;
});

const changeAmount = computed(() => {
    return Math.max(0, form.amount_paid - totalFinal.value);
});

// Helper formatting rupiah
const formatRupiah = (number: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
};

// Proses Submit ke Server
const submitTransaction = () => {
    if (form.items.length === 0) {
        alert("Keranjang kosong!");
        return;
    }
    
    if (form.payment_method === 'cash' && form.amount_paid < totalFinal.value) {
        alert("Nominal pembayaran kurang dari Total!");
        return;
    }

    form.post(route('transactions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // Setelah berhasil akan di-redirect controller ke halaman invoice
        }
    });
};
</script>

<template>
    <Head title="Kasir & Transaksi Baru" />

    <div class="px-4 py-6 md:px-8 max-w-[1600px] mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 flex items-center">
                    <ShoppingCart class="mr-3 h-6 w-6 text-primary" /> Transaksi Kasir
                </h2>
                <p class="text-sm text-gray-500 mt-1">Buat pesanan baru untuk pelanggan</p>
            </div>
            <!-- Bantuan / Batal -->
            <Button variant="outline" class="text-gray-500">Reset Kasir</Button>
        </div>

        <!-- Layout Kiri (Keranjang & Setup) - Kanan (Ringkasan & Bayar) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 h-full items-start">
            
            <!-- KOLOM KIRI: Keranjang Belanja -->
            <div class="lg:col-span-8 flex flex-col gap-6">
                <!-- 1. Block Data Pesanan / Pelanggan -->
                <Card class="border shadow-sm">
                    <CardHeader class="pb-4">
                        <CardTitle class="text-lg flex items-center"><User class="mr-2 h-5 w-5 text-gray-400" /> Informasi Pelanggan</CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label>Pilih Pelanggan (Tetap)</Label>
                            <Select v-model="form.customer_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih atau tinggalkan kosong (Umum)" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Pelanggan Umum (Tidak Terafiliasi)</SelectItem>
                                    <SelectItem v-for="c in customers" :key="c.id" :value="c.id.toString()">{{ c.name }} - {{ c.phone }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label>Catatan Umum Pesanan</Label>
                            <Input v-model="form.notes" placeholder="Catatan untuk tim produksi..." />
                        </div>
                    </CardContent>
                </Card>

                <!-- 2. Block Pilih Layanan & List Keranjang -->
                <Card class="border shadow-sm min-h-[400px] flex flex-col">
                    <CardHeader class="bg-gray-50 rounded-t-xl border-b pb-4">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <CardTitle class="text-lg flex items-center"><FileText class="mr-2 h-5 w-5 text-gray-400" /> Keranjang Belanja</CardTitle>
                            <div class="flex items-center gap-2 w-full md:w-[350px]">
                                <Select v-model="selectedServiceId">
                                    <SelectTrigger class="bg-white">
                                        <SelectValue placeholder="Cari layanan (Print, Banner, dll)..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="s in services" :key="s.id" :value="s.id.toString()">
                                            {{ s.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button @click="addItem" :disabled="!selectedServiceId" size="icon" class="shrink-0 bg-primary hover:bg-primary/90 text-white">
                                    <Plus class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent class="p-0 flex-1 relative overflow-auto">
                        <div v-if="form.items.length === 0" class="absolute inset-0 flex flex-col justify-center items-center text-center p-8 text-gray-500">
                            <ShoppingCart class="h-12 w-12 text-gray-200 mb-3" />
                            <p class="font-medium text-gray-600">Keranjang masih kosong.</p>
                            <p class="text-sm mt-1">Pilih layanan di atas lalu tekan (+) untuk menambahkan item ke keranjang belanja.</p>
                        </div>

                        <!-- Table Keranjang -->
                        <div v-else class="w-full">
                            <table class="w-full text-sm text-left">
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
                                                    <input 
                                                        type="file" 
                                                        :id="`file-${index}`" 
                                                        class="hidden" 
                                                        @change="(e: any) => item.file = e.target.files[0]"
                                                    >
                                                    <label 
                                                        :for="`file-${index}`" 
                                                        class="flex items-center gap-1.5 px-2 py-1 bg-blue-50 border border-blue-200 rounded cursor-pointer hover:bg-blue-100 transition-colors"
                                                    >
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
            <div class="lg:col-span-4 sticky top-6">
                <Card class="border-2 border-primary/20 shadow-xl overflow-hidden flex flex-col h-full bg-white">
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
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500 flex items-center">
                                    Diskon (%)
                                </span>
                                <!-- Input diskon kecil -->
                                <div class="w-20 pl-2">
                                    <Input v-model="form.discount_percent" type="number" class="h-7 text-right text-xs" min="0" max="100" />
                                </div>
                            </div>
                            <div v-if="discountAmount > 0" class="flex justify-between items-center text-sm text-green-600 font-medium pt-1">
                                <span>Potongan Harga</span>
                                <span>- {{ formatRupiah(discountAmount) }}</span>
                            </div>
                        </div>

                        <!-- Pembayaran Form -->
                        <div class="space-y-4 pt-2">
                            <div class="space-y-2">
                                <Label class="text-gray-700 font-semibold" for="payment_method">Metode Pembayaran</Label>
                                <Select v-model="form.payment_method">
                                    <SelectTrigger class="bg-gray-50 border-gray-300">
                                        <SelectValue placeholder="Pilih Jenis" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="cash">Tunai (Cash)</SelectItem>
                                        <SelectItem value="qris">E-Wallet / QRIS</SelectItem>
                                        <SelectItem value="transfer">Transfer Bank</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div v-if="form.payment_method === 'cash'" class="space-y-2 pt-2">
                                <Label class="text-gray-700 font-semibold" for="amount_paid">Nominal Dibayarkan (Tunai)</Label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-500 font-medium">Rp</div>
                                    <Input v-model="form.amount_paid" type="number" class="pl-10 text-lg font-bold h-12 border-primary/50 focus-visible:ring-primary/50" />
                                </div>
                                <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg mt-3 border border-gray-100">
                                    <span class="text-gray-600 text-sm font-medium">Kembalian:</span>
                                    <span class="text-lg font-bold" :class="changeAmount > 0 ? 'text-primary' : (form.amount_paid < totalFinal && form.amount_paid > 0 ? 'text-red-500' : 'text-gray-400')">
                                        {{ formatRupiah(changeAmount) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </CardContent>

                    <div class="p-6 bg-gray-50 border-t border-gray-100 mt-auto">
                        <Button 
                            @click="submitTransaction" 
                            class="w-full h-14 text-base font-bold shadow-lg bg-primary hover:bg-primary/90"
                            :disabled="form.items.length === 0 || form.processing || (form.payment_method === 'cash' && form.amount_paid < totalFinal)"
                        >
                            <Save class="mr-2 h-5 w-5" /> SIMPAN & CETAK NOTA
                        </Button>
                    </div>
                </Card>
            </div>
            
        </div>
    </div>
</template>
