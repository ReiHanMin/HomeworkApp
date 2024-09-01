import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: ['resources/js/app.js', 'resources/css/app.css'], // Include both JS and CSS entry points
            refresh: true,
        }),
    ],
    build: {
        outDir: 'public/build', // Ensure the output is in the correct directory
        manifest: true, // Generate a manifest file
        rollupOptions: {
            input: {
                app: 'resources/js/app.js', // Define a named input entry point for clarity
                // Add any other entry points here if needed
            },
            output: {
                entryFileNames: 'assets/js/[name].[hash].js', // Adjust output file name pattern for JS
                chunkFileNames: 'assets/js/[name].[hash].js', // Adjust output for chunks
                assetFileNames: 'assets/[ext]/[name].[hash].[ext]', // Adjust output for other assets like images, fonts, etc.
            },
        },
        emptyOutDir: true, // Clear the output directory before building
    },
});
