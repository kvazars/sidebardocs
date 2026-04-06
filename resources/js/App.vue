<script setup>
import { onBeforeMount } from "vue";
import { useColorModes } from "@coreui/vue";
// import AppSidebar from '@/components/AppSidebar.vue'

import { useThemeStore } from "@/stores/theme.js";
const { isColorModeSet, setColorMode } = useColorModes(
    "coreui-free-vue-admin-template-theme"
);
const currentTheme = useThemeStore();

onBeforeMount(() => {
    const urlParams = new URLSearchParams(window.location.href.split("?")[1]);
    let theme = urlParams.get("theme");

    if (theme !== null && theme.match(/^[A-Za-z0-9\s]+/)) {
        theme = theme.match(/^[A-Za-z0-9\s]+/)[0];
    }

    const known = ["light", "dark", "auto"];

    if (theme && known.includes(theme)) {
        setColorMode(theme);
        currentTheme.toggleTheme(theme);
        return;
    }

    if (isColorModeSet()) {
        const stored = localStorage.getItem(
            "coreui-free-vue-admin-template-theme"
        );
        if (stored && known.includes(stored)) {
            currentTheme.toggleTheme(stored);
        }
        return;
    }

    setColorMode(currentTheme.theme);
});
</script>

<template>
    <router-view />
</template>

<style lang="scss">
@import "styles/style";
@import "styles/examples";
</style>
