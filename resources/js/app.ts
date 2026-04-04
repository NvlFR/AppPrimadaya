import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, createSSRApp, h } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { ZiggyVue } from 'ziggy-js';
import { route } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Pastikan fungsi route dikenali di context global (terutama di <script setup> tanpa perlu import manual)
if (typeof globalThis !== 'undefined') {
    (globalThis as any).route = route;
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    setup({ el, App, props, plugin }) {
        const isSSR = typeof window === 'undefined';
        const app = (isSSR ? createSSRApp : createApp)({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        if (el) {
            app.mount(el);
        }
        return app;
    },
    progress: {
        color: '#4B5563',
    },
});

// Dark mode dinonaktifkan sesuai kebutuhan UI Light Blueprint
// initializeTheme();
