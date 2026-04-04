<script setup lang="ts">
import AppLayoutBase from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <AppLayoutBase :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayoutBase>
</template>
