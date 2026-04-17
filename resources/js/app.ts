import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, createSSRApp, defineAsyncComponent, h } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { ZiggyVue } from 'ziggy-js';
import { route } from 'ziggy-js';

// Layout non-kritis dimuat lazy — tidak masuk main bundle
const AuthLayout = defineAsyncComponent(() => import('@/layouts/AuthLayout.vue'));
const SettingsLayout = defineAsyncComponent(() => import('@/layouts/settings/Layout.vue'));

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

if (typeof window !== 'undefined') {
    import('virtual:pwa-register').then(({ registerSW }) => {
        registerSW({ immediate: true });
    }).catch(() => {});
}

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
                return undefined;
        }
    },
    setup({ el, App, props, plugin }) {
        const isSSR = typeof window === 'undefined';
        
        if (isSSR && props.initialPage.props.ziggy) {
            const ziggy = props.initialPage.props.ziggy as any;
            (globalThis as any).Ziggy = {
                ...ziggy,
                location: new URL(ziggy.location)
            };
        }

        const app = (isSSR ? createSSRApp : createApp)({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, typeof window !== 'undefined' ? undefined : (props.initialPage.props.ziggy as any));

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
