import { ref } from "vue";
import { defineStore } from "pinia";

export const useThemeStore = defineStore("theme", () => {
	/** @type {import('vue').Ref<'light' | 'dark' | 'auto'>} */
	const theme = ref("auto");

	const toggleTheme = (_theme) => {
		theme.value = _theme;
	};

	return { theme, toggleTheme };
});
