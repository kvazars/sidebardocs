<template>
    <router-view />
</template>

<script>
import { useThemeStore } from "@/stores/theme.js";
import { initAppThemeFromUrlAndPinia } from "@/utils/coreuiTheme.js";

export default {
    name: "App",
    beforeMount() {
        initAppThemeFromUrlAndPinia(useThemeStore());
    },
};
</script>

<style lang="scss">
@import "styles/style";
@import "styles/examples";
</style>
