<script setup lang="ts">
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

const mainNavItems: NavItem[] = [
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
        title: 'Pengeluaran',
        href: route('expenses.index'),
        icon: Wallet,
    },
    {
        title: 'Stok Bahan',
        href: route('stocks.index'),
        icon: Box,
    },
    {
        title: 'Laporan',
        href: route('reports.index'),
        icon: FileBarChart,
    },
    {
        title: 'Daftar Karyawan',
        href: route('users.index'),
        icon: UsersRound,
    },
];

const footerNavItems: NavItem[] = [];
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
