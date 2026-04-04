<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, ShoppingCart, ListOrdered, Users, Package, Wallet, Box, FileBarChart, UsersRound } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

// Baca role user dari shared props Inertia
const page = usePage();
const userRole = computed(() => (page.props.auth as any)?.role ?? null);
const isAdmin = computed(() => userRole.value === 'admin');

/**
 * Menu navigasi dengan role-based filtering.
 * Item dengan `adminOnly: true` hanya tampil untuk role 'admin'.
 * Menggunakan computed agar route() dievaluasi saat komponen di-setup,
 * bukan saat module diinisialisasi — mencegah SSR error Ziggy.
 */
const allNavItems = computed<(NavItem & { adminOnly?: boolean })[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Kasir / Transaksi',
        href: route('transactions.create'),
        icon: ShoppingCart,
    },
    {
        title: 'Data Pesanan',
        href: route('orders.index'),
        icon: ListOrdered,
    },
    {
        title: 'Manajemen Layanan',
        href: route('services.index'),
        icon: Package,
    },
    {
        title: 'Data Pelanggan',
        href: route('customers.index'),
        icon: Users,
    },
    {
        // Hanya Admin
        title: 'Pengeluaran',
        href: route('expenses.index'),
        icon: Wallet,
        adminOnly: true,
    },
    {
        // Hanya Admin
        title: 'Stok Bahan',
        href: route('stocks.index'),
        icon: Box,
        adminOnly: true,
    },
    {
        // Hanya Admin
        title: 'Laporan Keuangan',
        href: route('reports.index'),
        icon: FileBarChart,
        adminOnly: true,
    },
    {
        // Hanya Admin
        title: 'Manajemen Akun',
        href: route('users.index'),
        icon: UsersRound,
        adminOnly: true,
    },
]);

// Filter menu berdasarkan role — kasir hanya dapat item non-admin
const mainNavItems = computed<NavItem[]>(() =>
    allNavItems.value.filter(item => !item.adminOnly || isAdmin.value)
);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
