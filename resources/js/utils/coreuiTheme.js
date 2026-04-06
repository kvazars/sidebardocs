/** Синхронизация с @coreui/vue useColorModes (тот же ключ и правило для data-coreui-theme). */
export const COREUI_THEME_STORAGE_KEY =
    "coreui-free-vue-admin-template-theme";

export function applyCoreuiTheme(colorMode) {
    if (typeof document === "undefined") {
        return;
    }
    document.documentElement.dataset.coreuiTheme =
        colorMode === "auto" &&
        window.matchMedia("(prefers-color-scheme: dark)").matches
            ? "dark"
            : colorMode;
    document.documentElement.dispatchEvent(new Event("ColorSchemeChange"));
}

/**
 * Первичная тема до монтирования шапки (useColorModes в AppHeader).
 */
export function initAppThemeFromUrlAndPinia(themeStore) {
    const known = ["light", "dark", "auto"];
    const key = COREUI_THEME_STORAGE_KEY;
    const urlParams = new URLSearchParams(window.location.href.split("?")[1]);
    let theme = urlParams.get("theme");

    if (theme !== null && theme.match(/^[A-Za-z0-9\s]+/)) {
        theme = theme.match(/^[A-Za-z0-9\s]+/)[0];
    }

    if (theme && known.includes(theme)) {
        localStorage.setItem(key, theme);
        applyCoreuiTheme(theme);
        themeStore.toggleTheme(theme);
        return;
    }

    if (localStorage.getItem(key)) {
        const stored = localStorage.getItem(key);
        if (stored && known.includes(stored)) {
            themeStore.toggleTheme(stored);
        }
        applyCoreuiTheme(stored || themeStore.theme);
        return;
    }

    const m = themeStore.theme;
    localStorage.setItem(key, m);
    applyCoreuiTheme(m);
}
