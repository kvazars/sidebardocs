import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import autoprefixer from "autoprefixer";
import path from "node:path";

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
                api: "modern-compiler",
            },
        },
    },
    plugins: [
        laravel({
            input: ["resources/sass/app.scss", "resources/js/app.js"],
            refresh: true,
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
    // resolve: {
    //     alias: [
    //         // webpack path resolve to vitejs
    //         {
    //             find: /^~(.*)$/,
    //             replacement: "$1",
    //         },
    //         {
    //             find: "@/",
    //             replacement: `${path.resolve(__dirname, "src")}/`,
    //         },
    //         {
    //             find: "@",
    //             replacement: path.resolve(__dirname, "/src"),
    //         },
    //     ],
    //     extensions: [
    //         ".mjs",
    //         ".js",
    //         ".ts",
    //         ".jsx",
    //         ".tsx",
    //         ".json",
    //         ".vue",
    //         ".scss",
    //     ],
    // },
    server: {
        port: 3001,
        proxy: {
            // https://vitejs.dev/config/server-options.html
        },
    },
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
});
