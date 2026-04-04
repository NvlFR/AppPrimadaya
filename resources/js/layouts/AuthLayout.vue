<script setup lang="ts">
import AuthLayoutBase from '@/layouts/auth/AuthSplitLayout.vue';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <AuthLayoutBase :title="title" :description="description">
        <slot />
    </AuthLayoutBase>
</template>
