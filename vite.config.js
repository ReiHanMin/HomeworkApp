import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
    ],
    build: {
        outDir: 'public/build', // Ensure the output is in the correct directory
        manifest: true, // Generate a manifest file
        rollupOptions: {
            input: 'resources/js/app.js',
        },
    },
});
