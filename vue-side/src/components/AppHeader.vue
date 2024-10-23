<script setup>
import { onMounted, ref } from "vue";
import { useColorModes } from "@coreui/vue";

import AppBreadcrumb from "@/components/AppBreadcrumb.vue";
import { useSidebarStore } from "@/stores/sidebar.js";

const headerClassNames = ref("p-0");
const { colorMode, setColorMode } = useColorModes(
	"coreui-free-vue-admin-template-theme"
);
const sidebar = useSidebarStore();

const props = defineProps(['openWindowFunction']);

onMounted(() => {
	document.addEventListener("scroll", () => {
		if (document.documentElement.scrollTop > 0) {
			headerClassNames.value = "p-0 shadow-sm";
		} else {
			headerClassNames.value = "p-0";
		}
	});
});

const modeTheme = ref(localStorage.getItem('coreui-free-vue-admin-template-theme'));
// modeTheme.value = 
// console.log(modeTheme.value);

</script>

<template>
	<CHeader position="sticky" :class="headerClassNames">
		<CContainer class="border-bottom px-4" fluid>
			<CHeaderToggler @click="sidebar.toggleVisible()" style="margin-inline-start: -14px">
				<i class="fa fa-bars" aria-hidden="true"></i>
			</CHeaderToggler>
			<CHeaderNav>
				<li class="nav-item py-1">
					<div class="vr h-100 mx-2 text-body text-opacity-75"></div>
				</li>
				<div style="padding: 0.2rem;" class="d-flex align-items-center justify-content-center">

					<div v-if="modeTheme == 'light'" :active="colorMode === 'light'" class="d-flex align-items-center"
						component="button" type="button" @click="setColorMode('dark'); modeTheme = 'dark'">
						<i class="fa fa-sun-o "></i>
					</div>
					<div v-if="modeTheme == 'dark'" :active="colorMode === 'dark'" class="d-flex align-items-center"
						component="button" type="button" @click="setColorMode('light'); modeTheme = 'light'">
						<i class="fa fa-moon-o "></i>
					</div>
				</div>
				<li class="nav-item py-1">
					<div class="vr h-100 mx-2 text-body text-opacity-75"></div>
				</li>
				<CDropdown placement="bottom-end" variant="nav-item">
					<CDropdownToggle class="pe-0" :caret="false">
						<i class="fa fa-user"></i>
					</CDropdownToggle>
					<CDropdownMenu class="pt-0">
						<CDropdownItem>
							<i class="fa fa-cog"></i> Настройки
						</CDropdownItem>

						<CDropdownDivider />
						<CDropdownItem @click="openWindowFunction">
							<i class="fa fa-lock"></i> Вход
						</CDropdownItem>
						<CDropdownItem>
							<i class="fa fa-unlock"></i> Выход
						</CDropdownItem>
					</CDropdownMenu>
				</CDropdown>
			</CHeaderNav>
		</CContainer>
		<CContainer class="px-4" fluid>
			<AppBreadcrumb />
		</CContainer>
	</CHeader>
</template>
