import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
        }),
        VitePWA({
            outDir: 'public/build',
            buildBase: '/build/',
            scope: '/',
            injectRegister: null,
            registerType: 'autoUpdate',
            manifest: {
                name: 'Primadaya Print',
                short_name: 'Primadaya',
                description: 'Primadaya Point of Sale',
                theme_color: '#000000',
                background_color: '#ffffff',
                display: 'standalone',
                start_url: '/',
                icons: [
                    {
                        src: '/apple-touch-icon.png',
                        sizes: '192x192',
                        type: 'image/png'
                    },
                    {
                        src: '/apple-touch-icon.png',
                        sizes: '512x512',
                        type: 'image/png'
                    }
                ]
            },
            devOptions: {
                enabled: false,
            }
        }),
        inertia(),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
    server: {
        host: true,
        hmr: {
            host: '192.168.100.92',
        },
    },
});
