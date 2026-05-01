<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useSidebar } from '@/components/ui/sidebar';

const { state } = useSidebar();
</script>

<template>
    <div class="flex items-center gap-2 overflow-hidden">
        <div v-if="state === 'collapsed'" class="flex size-8 items-center justify-center rounded-md bg-white p-1">
             <img src="/logo.png" alt="P" class="h-full w-auto object-contain" />
        </div>
        <div v-else class="flex items-center">
             <img src="/logo.png" alt="Primadaya Print" class="h-10 w-auto object-contain" />
        </div>
    </div>
</template>
