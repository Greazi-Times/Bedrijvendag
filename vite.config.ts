import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.ts',
                'resources/css/app.css',
                'resources/css/filament/dashboard/theme.css'
            ],
            ssr: 'resources/js/ssr.ts',
            refresh: true
        }),
        tailwindcss(),
        // Generation goes through a lock shared with `npm run types`, so nothing reads the generated files while they are rewritten.
        wayfinder({
            formVariants: true,
            command: 'node scripts/wayfinder-generate.mjs'
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false
                }
            }
        })
    ]
});
