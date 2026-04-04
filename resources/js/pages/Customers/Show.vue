<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
    UserIcon, 
    PhoneIcon, 
    MapPinIcon, 
    CalendarIcon, 
    ShoppingBagIcon, 
    CreditCardIcon, 
    FileTextIcon,
    ArrowLeftIcon
} from 'lucide-vue-next';

interface Transaction {
    id: number;
    transaction_number: string;
    total: string | number;
    status: string;
    status_label: string;
    payment_method: string;
    created_at: string;
}

const props = defineProps<{
    customer: {
        id: number;
        name: string;
        phone: string | null;
        address: string | null;
        notes: string | null;
        total_spent: string | number;
        transactions_count: number;
        created_at: string;
    };
    transactions: {
        data: Transaction[];
        links: any[];
        current_page: number;
        last_page: number;
    };
}>();

const formatRupiah = (value: number | string) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(value));
};

const getStatusColor = (status: string) => {
    switch(status) {
        case 'selesai':
        case 'diambil':
            return 'bg-green-100 text-green-800 border-green-200';
        case 'diproses':
            return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'pending':
        default:
            return 'bg-orange-100 text-orange-800 border-orange-200';
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="[
        { title: 'Dashboard', href: route('dashboard') }, 
        { title: 'Pelanggan', href: route('customers.index') },
        { title: 'Detail Pelanggan', href: route('customers.show', customer.id) }
    ]">
        <Head :title="`Detail Pelanggan: ${customer.name}`" />

        <div class="px-4 py-6 md:px-8 space-y-6 max-w-7xl mx-auto">
            <!-- Header Nav -->
            <div class="flex items-center space-x-4">
                <Link :href="route('customers.index')">
                    <Button variant="outline" size="icon" class="h-8 w-8 rounded-full">
                        <ArrowLeftIcon class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Profil Pelanggan</h1>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Customer Info Card -->
                <div class="col-span-1 border bg-white rounded-xl shadow-sm p-6 space-y-6 h-fit">
                    <div class="flex items-center space-x-4">
                        <div class="h-16 w-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold text-2xl">
                            {{ customer.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">{{ customer.name }}</h2>
                            <p class="text-sm text-gray-500">ID: CUST-{{ String(customer.id).padStart(4, '0') }}</p>
                        </div>
                    </div>

                    <div class="space-y-4 pt-4 border-t border-gray-100">
                        <div class="flex items-start space-x-3 text-sm">
                            <PhoneIcon class="h-4 w-4 text-gray-400 mt-0.5" />
                            <div>
                                <p class="text-gray-500">No. Telepon/WA</p>
                                <p class="font-medium text-gray-900">{{ customer.phone || 'Belum diatur' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3 text-sm">
                            <MapPinIcon class="h-4 w-4 text-gray-400 mt-0.5" />
                            <div>
                                <p class="text-gray-500">Alamat</p>
                                <p class="font-medium text-gray-900 line-clamp-2">{{ customer.address || 'Belum diatur' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3 text-sm">
                            <CalendarIcon class="h-4 w-4 text-gray-400 mt-0.5" />
                            <div>
                                <p class="text-gray-500">Bergabung Sejak</p>
                                <p class="font-medium text-gray-900">{{ customer.created_at }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100">
                        <p class="text-sm text-gray-500 mb-1">Catatan Internal</p>
                        <p class="text-sm text-gray-800 bg-gray-50 p-3 rounded-md border border-gray-100 min-h-[60px]">
                            {{ customer.notes || 'Tidak ada catatan.' }}
                        </p>
                    </div>
                </div>

                <!-- Transaction History & Stats -->
                <div class="col-span-1 lg:col-span-2 space-y-6">
                    
                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-blue-600 rounded-xl p-5 text-white shadow-sm flex items-center space-x-4">
                            <div class="bg-blue-500 p-3 rounded-full">
                                <CreditCardIcon class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-blue-100 text-sm font-medium">Total Pembelanjaan</p>
                                <p class="text-2xl font-bold">{{ formatRupiah(customer.total_spent) }}</p>
                            </div>
                        </div>
                        <div class="bg-white border rounded-xl p-5 shadow-sm flex items-center space-x-4">
                            <div class="bg-blue-50 p-3 rounded-full">
                                <ShoppingBagIcon class="h-6 w-6 text-blue-600" />
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Total Pesanan</p>
                                <p class="text-2xl font-bold text-gray-900">{{ customer.transactions_count }} Transaksi</p>
                            </div>
                        </div>
                    </div>

                    <!-- Transactions Table -->
                    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                            <h3 class="font-semibold text-gray-900 flex items-center">
                                <FileTextIcon class="h-4 w-4 mr-2 text-gray-500" />
                                Riwayat Pesanan Terakhir
                            </h3>
                            <Link href="/transactions/create">
                                <Button variant="outline" size="sm" class="h-8">
                                    Buat Pesanan Baru
                                </Button>
                            </Link>
                        </div>
                        <div class="overflow-x-auto whitespace-nowrap">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-50 text-gray-600 font-medium border-b border-gray-100">
                                    <tr>
                                        <th class="px-6 py-3">No. Nota</th>
                                        <th class="px-6 py-3">Tanggal</th>
                                        <th class="px-6 py-3">Metode Bayar</th>
                                        <th class="px-6 py-3">Total</th>
                                        <th class="px-6 py-3 text-center">Status</th>
                                        <th class="px-6 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="trx in transactions.data" :key="trx.id" class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 font-medium text-gray-900">{{ trx.transaction_number }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ trx.created_at }}</td>
                                        <td class="px-6 py-4 uppercase text-xs font-semibold">{{ trx.payment_method }}</td>
                                        <td class="px-6 py-4 font-medium">{{ formatRupiah(trx.total) }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <Badge variant="outline" :class="getStatusColor(trx.status)">
                                                {{ trx.status_label }}
                                            </Badge>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <!-- Kita biarkan mati linknya ke transaksi.show karena belum dibuat -->
                                            <Link :href="route('transactions.show', trx.id) || '#'">
                                                <Button variant="ghost" size="sm" class="h-8 text-blue-600 hover:bg-blue-50">
                                                    Lihat Detail
                                                </Button>
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="transactions.data.length === 0">
                                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                            Pelanggan ini belum pernah melakukan pemesanan.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Mini Pagination -->
                        <div class="px-6 py-3 border-t border-gray-100 flex justify-between items-center" v-if="transactions.last_page > 1">
                            <Button 
                                v-if="transactions.links[0].url" 
                                variant="outline" size="sm" 
                                @click="router.get(transactions.links[0].url)" 
                                :disabled="!transactions.links[0].url"
                            >Prev</Button>
                            <span class="text-xs text-gray-500">Halaman {{ transactions.current_page }} / {{ transactions.last_page }}</span>
                            <Button 
                                v-if="transactions.links[transactions.links.length - 1].url" 
                                variant="outline" size="sm" 
                                @click="router.get(transactions.links[transactions.links.length - 1].url)"
                                :disabled="!transactions.links[transactions.links.length - 1].url"
                            >Next</Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
