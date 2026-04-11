<script setup lang="ts">
import AppLayoutBase from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <AppLayoutBase :breadcrumbs="breadcrumbs">
        <template #header-actions>
            <slot name="header-actions" />
        </template>
        <slot />
    </AppLayoutBase>
</template>
