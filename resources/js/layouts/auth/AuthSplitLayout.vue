<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

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
                <div class="mb-6 flex items-center justify-center">
                    <img src="/logo.png" alt="Logo" class="h-24 w-auto object-contain" />
                </div>

            </div>
        </div>
        <div class="lg:p-8 bg-white h-full flex items-center justify-center">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-8 sm:w-[400px] p-8 border rounded-2xl shadow-sm bg-white"
            >
                <div class="flex flex-col space-y-2 text-center">
                    <!-- Icon / name buat mobile -->
                    <div class="lg:hidden flex flex-col items-center justify-center mb-6">
                        <img src="/logo.png" alt="Logo" class="h-16 w-auto object-contain mb-2" />
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
