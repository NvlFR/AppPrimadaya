<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { home } from '@/routes';

const page = usePage();
const name = import.meta.env.VITE_APP_NAME || 'Primadaya';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0 bg-gray-50"
    >
        <div
            class="relative hidden h-full flex-col bg-[#F3F4F6] p-10 lg:flex justify-center items-center border-r"
        >
            <div class="relative z-20 flex flex-col items-center justify-center text-center">
                <!-- Branding Utama -->
                <div class="mb-4 flex items-center justify-center rounded-xl bg-primary w-20 h-20 shadow-lg shadow-primary/20">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white"><path d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2"/><path d="M17 9V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4"/><path d="M7 15h10v6H7v-6z"/></svg>
                </div>
                <h1 class="text-4xl font-bold tracking-tight text-gray-900">Primadaya</h1>
                <p class="mt-4 text-lg text-gray-600 font-medium">Solusi Manajemen Percetakan Modern</p>
                <div class="mt-8 text-sm text-gray-400">POS & Management System</div>
            </div>
        </div>
        <div class="lg:p-8 bg-white h-full flex items-center justify-center">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-8 sm:w-[400px] p-8 border rounded-2xl shadow-sm bg-white"
            >
                <div class="flex flex-col space-y-2 text-center">
                    <!-- Icon / name buat mobile -->
                    <div class="lg:hidden flex flex-col items-center justify-center mb-4">
                        <div class="flex items-center justify-center rounded-xl bg-primary w-14 h-14 shadow-md shadow-primary/20 mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white"><path d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2"/><path d="M17 9V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4"/><path d="M7 15h10v6H7v-6z"/></svg>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900">Primadaya</h1>
                    </div>
                    
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-900" v-if="title">
                        {{ title }}
                    </h1>
                    <p class="text-sm text-gray-500" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
