<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { 
    PlusIcon, 
    PencilIcon, 
    TrashIcon, 
    UsersIcon 
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    is_active: boolean;
    created_at: string;
}

const props = defineProps<{
    users: {
        data: User[];
        links: any[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    roles: { id: number; label: string }[];
    filters: { 
        search?: string;
        role_id?: string;
    };
}>();

const page = usePage();
const currentUserId = computed(() => (page.props.auth as any).user.id);

// Filter States
const search = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role_id || '');

// Automatic search
let searchTimeout: any;
const triggerSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('users.index'), {  
            search: search.value,
            role_id: roleFilter.value
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

watch([search, roleFilter], triggerSearch);

// Modal States
const isModalOpen = ref(false);
const isEditMode = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role_id: '',
    is_active: true,
});

const openCreateModal = () => {
    isEditMode.value = false;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (user: User) => {
    isEditMode.value = true;
    editingId.value = user.id;
    form.name = user.name;
    form.email = user.email;
    form.password = ''; // Reset password field
    
    // Find matching role id based on label
    const roleId = props.roles.find(r => r.label === user.role)?.id || '';
    form.role_id = String(roleId);
    
    form.is_active = !!user.is_active;
    isModalOpen.value = true;
};

const saveUser = () => {
    if (isEditMode.value && editingId.value) {
        form.put(route('users.update', editingId.value), {
            onSuccess: () => { isModalOpen.value = false; }
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => { isModalOpen.value = false; }
        });
    }
};

const deleteUser = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus akun pengguna ini?')) {
        router.delete(route('users.destroy', id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: route('dashboard') }, { title: 'Manajemen Akun', href: route('users.index') }]">
        <Head title="Manajemen Akun Karyawan" />

        <div class="px-4 py-6 md:px-8 space-y-6 max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Manajemen Akun Karyawan</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Kelola akun dan role akses untuk Admin dan Kasir.</p>
                </div>
                <Button @click="openCreateModal" class="bg-blue-600 hover:bg-blue-700 shadow-sm">
                    <PlusIcon class="h-4 w-4 mr-2" /> Tambah Akun Baru
                </Button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-4 bg-white p-4 rounded-xl border shadow-sm items-center">
                <div class="flex-1 min-w-[200px]">
                    <Input v-model="search" type="search" placeholder="Cari nama atau email..." />
                </div>
                <select v-model="roleFilter" class="flex h-9 w-[180px] rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="">Semua Role</option>
                    <option v-for="role in roles" :key="role.id" :value="role.id">
                        {{ role.label }}
                    </option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border shadow-sm overflow-hidden whitespace-nowrap overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600 font-medium border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-3">Karyawan</th>
                            <th class="px-6 py-3">Role Akses</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3">Terdaftar Sejak</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in users.data" :key="item.id" class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold uppercase">
                                        {{ item.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900">{{ item.name }}</p>
                                        <p class="text-xs text-gray-500">{{ item.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <Badge variant="outline" :class="item.role === 'Administrator' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-gray-50'">
                                    {{ item.role }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <Badge :variant="item.is_active ? 'default' : 'secondary'">
                                    {{ item.is_active ? 'Aktif' : 'Non-Aktif' }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ item.created_at }}</td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <Button variant="outline" size="sm" class="h-8 shadow-sm" @click="openEditModal(item)">
                                    <PencilIcon class="h-3 w-3 mr-1" /> Edit
                                </Button>
                                <Button 
                                    v-if="item.id !== currentUserId"
                                    variant="destructive" size="sm" class="h-8 shadow-sm" 
                                    @click="deleteUser(item.id)"
                                >
                                    <TrashIcon class="h-3 w-3" />
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <p class="font-medium">Tidak ada pengguna ditemukan.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-between items-center" v-if="users.last_page > 1">
                <span class="text-sm text-gray-400">Halaman {{ users.current_page }} / {{ users.last_page }}</span>
                <div class="flex gap-2">
                    <Button
                        variant="outline" size="sm"
                        :disabled="!users.links?.[0]?.url"
                        @click="users.links?.[0]?.url && router.get(users.links[0].url)"
                    >&larr; Sebelumnya</Button>
                    <Button
                        variant="outline" size="sm"
                        :disabled="!users.links?.[users.links.length - 1]?.url"
                        @click="users.links?.[users.links.length - 1]?.url && router.get(users.links[users.links.length - 1].url)"
                    >Berikutnya &rarr;</Button>
                </div>
            </div>
        </div>

        <!-- Add/Edit User Modal -->
        <Dialog :open="isModalOpen" @update:open="val => { if (!val) isModalOpen = false; }">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>{{ isEditMode ? 'Edit Profil Karyawan' : 'Tambah Karyawan Baru' }}</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="saveUser" class="space-y-4 py-4">
                    
                    <div class="space-y-2">
                        <Label for="name">Nama Lengkap <span class="text-red-500">*</span></Label>
                        <Input id="name" v-model="form.name" required placeholder="Cth: Budi Santoso" />
                        <span class="text-xs text-red-500" v-if="form.errors.name">{{ form.errors.name }}</span>
                    </div>

                    <div class="space-y-2">
                        <Label for="email">Alamat Email <span class="text-red-500">*</span></Label>
                        <Input id="email" type="email" v-model="form.email" required placeholder="Cth: budi@primadaya.com" />
                        <span class="text-xs text-red-500" v-if="form.errors.email">{{ form.errors.email }}</span>
                    </div>

                    <div class="space-y-2">
                        <Label for="role_id">Role Akses <span class="text-red-500">*</span></Label>
                        <select id="role_id" v-model="form.role_id" required class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus:ring-1 focus:ring-blue-600">
                            <option value="">Pilih Role Akses</option>
                            <option v-for="role in roles" :key="role.id" :value="role.id">
                                {{ role.label }}
                            </option>
                        </select>
                        <span class="text-xs text-red-500" v-if="form.errors.role_id">{{ form.errors.role_id }}</span>
                    </div>

                    <div class="space-y-2">
                        <Label for="password">Password {{ isEditMode ? '(Opsional)' : '*' }}</Label>
                        <Input id="password" type="password" v-model="form.password" :required="!isEditMode" placeholder="Minimal 8 karakter" />
                        <p v-if="isEditMode" class="text-xs text-gray-400">Kosongkan jika tidak ingin mengubah password.</p>
                        <span class="text-xs text-red-500" v-if="form.errors.password">{{ form.errors.password }}</span>
                    </div>

                    <div class="flex items-center space-x-2 pt-2">
                        <input type="checkbox" id="status_active" v-model="form.is_active" class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                        <Label for="status_active" class="font-normal cursor-pointer">Akun Aktif (Bisa Login)</Label>
                    </div>

                    <DialogFooter class="pt-4">
                        <Button type="button" variant="outline" @click="isModalOpen = false">Batal</Button>
                        <Button type="submit" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700">Simpan Akun</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
