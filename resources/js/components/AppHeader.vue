<template>
    <CHeader position="sticky" :class="headerClassNames" id="headerOne">

        <CContainer class="border-bottom px-4" fluid>
            <CHeaderToggler @click="sidebar.toggleVisible()" style="margin-inline-start: -14px">
                <i class="bi bi-list" aria-hidden="true" v-if="auths.id"></i>
            </CHeaderToggler>
            <CHeaderNav>
                <li class="nav-item py-1">
                    <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
                </li>
                <li class="nav-item d-flex align-items-center justify-content-center">
                    <div
                        class="btn btn-link px-2 py-1 text-body text-decoration-none"
                        role="button"
                        tabindex="0"
                        :title="themeCycleTitle"
                        style="cursor: pointer"
                        @click="cycleTheme"
                        @keydown.enter.prevent="cycleTheme"
                    >
                        <i :class="themeIconClass"></i>
                    </div>
                </li>

                <template v-if="auths.id">
                    <li class="nav-item py-1">
                        <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
                    </li>

                    <li class="nav-item d-flex align-items-center justify-content-center">
                        <div class="d-flex align-items-center">
                            <div @click="openSearchModal" class="ps-1 d-flex align-items-center" component="button"
                                type="button">
                                <i class="bi bi-search"></i>
                            </div>
                        </div>
                    </li>
                </template>


                <template v-if="auths.id">
                    <template v-if="auths.role == 'ceo'">
                        <li class="nav-item py-1">
                            <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
                        </li>

                        <li class="nav-item d-flex align-items-center justify-content-center">
                            <div class="d-flex align-items-center">
                                <div @click="addFirstLevel" class="ps-1 d-flex align-items-center" component="button"
                                    type="button">
                                    <i class="bi bi-folder-plus"></i>
                                </div>
                            </div>
                        </li>
                    </template>

                </template>




                <li class="nav-item py-1">
                    <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
                </li>

                <li v-if="!auths.id" class="nav-item d-flex align-items-center justify-content-center">
                    <div class="d-flex align-items-center">
                        <div @click="openWindowFunction" class="ps-1 d-flex align-items-center" component="button"
                            type="button">
                            <i class="bi bi-person-fill"></i>
                        </div>
                    </div>
                </li>
                <li>
                    <CDropdown placement="bottom-end" variant="nav-item" v-if="auths.id">
                        <CDropdownToggle class="pe-0" :caret="false">
                            <i class="bi bi-person-lines-fill"></i>
                        </CDropdownToggle>
                        <CDropdownMenu>
                            <CDropdownItem @click="goToAdmin" v-if="auths.role != 'user'">
                                <i class="bi bi-sliders2"></i> Управление
                            </CDropdownItem>
                            <CDropdownItem @click="goToSettings" v-if="auths.role != 'user'">
                                <i class="bi bi-pencil-fill"></i> Смена пароля
                            </CDropdownItem>

                            <CDropdownItem v-if="auths.id" @click="logout">
                                <i class="bi bi-box-arrow-right"></i> Выход
                            </CDropdownItem>
                        </CDropdownMenu>
                    </CDropdown>
                </li>
            </CHeaderNav>
        </CContainer>
    </CHeader>


</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useColorModes } from "@coreui/vue";

import { useSidebarStore } from "../stores/sidebar.js";
import { useAuthIdStore } from "../stores/authId";
import { useThemeStore } from "../stores/theme.js";
import { useRouter } from "vue-router";

const headerClassNames = ref("p-0");
const { colorMode, setColorMode } = useColorModes(
    "coreui-free-vue-admin-template-theme"
);
const themeStore = useThemeStore();
const sidebar = useSidebarStore();
const auths = useAuthIdStore();
const router = useRouter();

const props = defineProps(["openWindowFunction", "datasend", "logoutFun", "openSearchModal", "addFirstLevel"]);

const themeIconClass = computed(() => {
    switch (colorMode.value) {
        case "dark":
            return "bi bi-moon-fill";
        case "light":
            return "bi bi-sun-fill";
        default:
            return "bi bi-circle-half";
    }
});

const themeMenuTitle = computed(() => {
    switch (colorMode.value) {
        case "dark":
            return "Тёмная тема";
        case "light":
            return "Светлая тема";
        default:
            return "Тема как в системе";
    }
});

const themeCycleLabels = {
    light: "Светлая",
    dark: "Тёмная",
    auto: "Как в системе",
};

const themeCycleTitle = computed(() => {
    const order = ["light", "dark", "auto"];
    const cur = colorMode.value;
    const i = order.indexOf(cur);
    const nextIdx = i === -1 ? 0 : (i + 1) % order.length;
    const next = order[nextIdx];
    return `${themeMenuTitle.value} · далее: ${themeCycleLabels[next]} (нажмите)`;
});

function applyTheme(mode) {
    setColorMode(mode);
    themeStore.toggleTheme(mode);
}

function cycleTheme() {
    const order = ["light", "dark", "auto"];
    const cur = colorMode.value;
    const i = order.indexOf(cur);
    const nextIdx = i === -1 ? 0 : (i + 1) % order.length;
    applyTheme(order[nextIdx]);
}

onMounted(() => {
    document.addEventListener("scroll", () => {
        if (document.documentElement.scrollTop > 0) {
            headerClassNames.value = "p-0 shadow-sm";
        } else {
            headerClassNames.value = "p-0";
        }
    });
});

function logout() {
    props
        .datasend("logout", "GET", {})
        .then((res) => {
            if (res.success) {
                props.logoutFun();
            }
        })
        .catch((error) => {
            console.log(error);
        });
}

function goToAdmin() {
    router.push({
        name: "admin",
    });
}

function goToSettings() {
    router.push({
        name: "settings",
    });
}
</script>
