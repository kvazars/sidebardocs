import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import autoprefixer from "autoprefixer";
import { nodePolyfills } from "vite-plugin-node-polyfills";

export default defineConfig({
    base: "./",
    css: {
        postcss: {
            plugins: [
                autoprefixer({}), // add options if needed
            ],
        },
        preprocessorOptions: {
            scss: {
                quietDeps: true,
                silenceDeprecations: ["import"],
                api: "modern-compiler",
            },
        },
    },
    plugins: [
        laravel({
            input: ["resources/js/app.js"],
            refresh: true,
        }),
        nodePolyfills({
            include: ["stream", "events", "timers", "util", "buffer", "process"],
            protocolImports: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        // Large document viewers/editors are split into async chunks on purpose.
        // Raise the warning threshold so build output stays actionable.
        chunkSizeWarningLimit: 11000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes("node_modules")) {
                        return;
                    }

                    if (id.includes("pdfjs-dist")) {
                        return "pdf";
                    }

                    if (id.includes("ace-builds")) {
                        return "ace";
                    }

                    if (
                        id.includes("@editorjs") ||
                        id.includes("editorjs")
                    ) {
                        return "editorjs";
                    }

                    if (id.includes("@coreui")) {
                        return "coreui";
                    }
                },
            },
        },
    },
    server: {
        port: 3001,
        proxy: {
            // https://vitejs.dev/config/server-options.html
        },
    },
    resolve: {
        alias: {
            vue: "vue/dist/vue.esm-bundler.js",
        },
    },
});
