<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, AlertCircle, X } from 'lucide-vue-next';
import { ref, watch, onMounted } from 'vue';

const page = usePage();
const visible = ref(false);
const message = ref('');
const type = ref<'success' | 'error'>('success');
let timer: any = null;

const showToast = (msg: string, t: 'success' | 'error') => {
    message.value = msg;
    type.value = t;
    visible.value = true;

    if (timer) clearTimeout(timer);
    timer = setTimeout(() => {
        visible.value = false;
    }, 5000);
};

// Pantau perubahan flash props dari server
watch(
    () => page.props.flash,
    (flash: any) => {
        if (flash.success) {
            showToast(flash.success, 'success');
        } else if (flash.error) {
            showToast(flash.error, 'error');
        }
    },
    { deep: true, immediate: true }
);

const close = () => {
    visible.value = false;
};
</script>

<template>
    <Transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="visible"
            class="fixed right-4 top-4 z-[100] max-w-sm w-full bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 shadow-lg rounded-lg pointer-events-auto overflow-hidden"
        >
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <CheckCircle2 v-if="type === 'success'" class="h-5 w-5 text-green-500" />
                        <AlertCircle v-else class="h-5 w-5 text-red-500" />
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                            {{ type === 'success' ? 'Berhasil' : 'Kesalahan' }}
                        </p>
                        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ message }}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button
                            @click="close"
                            class="inline-flex text-neutral-400 hover:text-neutral-500 focus:outline-none"
                        >
                            <span class="sr-only">Tutup</span>
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
            <div 
                class="h-1 bg-current opacity-20 absolute bottom-0 left-0" 
                :class="type === 'success' ? 'text-green-500' : 'text-red-500'"
                :style="{ width: visible ? '100%' : '0%', transition: 'width 5s linear' }"
            ></div>
        </div>
    </Transition>
</template>
