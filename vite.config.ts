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
            workbox: {
                // Hanya precache file kecil: HTML, ikon, manifest
                // JS/CSS besar ditangani via runtimeCaching di bawah
                globPatterns: ['**/*.{ico,png,svg,webmanifest}'],
                maximumFileSizeToCacheInBytes: 150 * 1024, // Maks 150 KB per file saat precache

                runtimeCaching: [
                    {
                        // JS chunks: CacheFirst karena nama file sudah di-hash
                        urlPattern: /\/build\/assets\/.*\.js$/,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'js-chunks-v1',
                            expiration: {
                                maxEntries: 60,
                                maxAgeSeconds: 30 * 24 * 60 * 60, // 30 hari
                            },
                            cacheableResponse: { statuses: [0, 200] },
                        },
                    },
                    {
                        // CSS: CacheFirst karena sudah di-hash
                        urlPattern: /\/build\/assets\/.*\.css$/,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'css-chunks-v1',
                            expiration: {
                                maxEntries: 10,
                                maxAgeSeconds: 30 * 24 * 60 * 60, // 30 hari
                            },
                            cacheableResponse: { statuses: [0, 200] },
                        },
                    },
                    {
                        // Halaman Inertia: NetworkFirst agar data selalu fresh
                        urlPattern: ({ request }) => request.mode === 'navigate',
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'pages-v1',
                            networkTimeoutSeconds: 8,
                            expiration: {
                                maxEntries: 20,
                                maxAgeSeconds: 24 * 60 * 60, // 1 hari (offline fallback)
                            },
                            cacheableResponse: { statuses: [0, 200] },
                        },
                    },
                ],
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
    build: {
        // Suppress warning untuk chunk besar yang memang lazy (ApexCharts)
        chunkSizeWarningLimit: 600,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;

                    // Chart library — hanya dimuat lazy di halaman Dashboard Admin
                    if (id.includes('apexcharts') || id.includes('vue3-apexcharts')) {
                        return 'vendor-charts';
                    }
                    // Icon library — shared, beri chunk tersendiri untuk cache efisien
                    if (id.includes('lucide-vue-next')) {
                        return 'vendor-icons';
                    }
                    // Shadcn UI primitives (Reka-UI + floating-ui)
                    if (id.includes('reka-ui') || id.includes('@floating-ui')) {
                        return 'vendor-ui';
                    }
                    // VueUse utilities
                    if (id.includes('@vueuse/core') || id.includes('@vueuse/shared')) {
                        return 'vendor-vueuse';
                    }
                    // Inertia + Ziggy: core routing layer, stable → cache tersendiri
                    if (id.includes('@inertiajs') || id.includes('ziggy-js')) {
                        return 'vendor-core';
                    }
                },
            },
        },
    },
    server: {
        host: true,
        hmr: {
            host: '192.168.100.92',
        },
    },
});
