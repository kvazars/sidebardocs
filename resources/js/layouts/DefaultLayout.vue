<template>
    <div class="vh-100 position-relative layout-shell">
        <AppSidebar
            v-if="showChrome && hasSidebarMenu"
            :sidebarWidth="effectiveSidebarWidth"
            :catchError="catchError"
            :showToast="showToast"
            :menu="menu"
            :datasend="datasend" :getMenu="getMenu" :about="about" :server="server" :editFolder="editFolder" :newFolder="newFolder" />
        <div
            v-if="showSplitter"
            class="sidebar-splitter"
            :class="{ 'is-dragging': isResizingSidebar }"
            :style="splitterStyle"
            @mousedown="startSidebarResize"
        ></div>
        <div class="wrapper d-flex flex-column" :style="wrapperStyle">
            <AppHeader
                v-if="showChrome"
                :openWindowFunction="openWindowFunction"
                :datasend="datasend"
                :logoutFun="logoutFun"
                :showToast="showToast"
                :openSearchModal="openSearchModal" :addFirstLevel="addFirstLevel" />
            <CContainer
                v-if="showChrome"
                class="p-3 position-sticky border-bottom breadcrumb_container d-flex justify-content-between align-items-center z1"
                fluid id="bread">
                <AppBreadcrumb :breadcrumbs="breadcrumbs" :content="content" :saveEditFile="saveEditFile"
                    :datasend="datasend" :getMenu="getMenu" />
            </CContainer>
            <div class="body flex-grow-1">
                <CContainer>
                    <router-view v-if="canRenderRoute" v-slot="{ Component }">
                        <component
                            :is="Component"
                            ref="routeViewComponent"
                            :server="server"
                            :catchError="catchError"
                            :datasend="datasend"
                            :api="api"
                            :getMenu="getMenu"
                            :showToast="showToast"
                            :authss="auths.id"
                            :blockForTest="blockForTest"
                            :setContent="setContent"
                            :dashboard="dashboard"
                            :about="about"
                            :userRole="auths.role"
                            :key="$route.fullPath"
                        />
                    </router-view>
                </CContainer>
            </div>
            <AppFooter :about="about" />
        </div>
        <AuthWindow :openWindow="openWindow" :openWindowFunction="openWindowFunction" :datasend="datasend"
            :catchError="catchError" :getMenu="getMenu" />
        <AntiCopyProtection />
        <CModal :visible="visibleSearchModal" @close="closeSearchModal" aria-labelledby="SearchModalLabel" size="lg"
            backdrop="static">
            <CModalHeader>
                <CModalTitle id="SearchModalLabel">
                    <i class="bi bi-search me-2"></i>
                    Поиск по объектам
                </CModalTitle>
            </CModalHeader>

            <CModalBody>
                <div class="search-container">

                    <div class="search-input-wrapper mb-4">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input ref="searchInput" type="text" class="form-control border-start-0 border-end-0"
                                placeholder="Введите текст для поиска (минимум 2 символа)..." v-model="searchQuery"
                                @keyup.enter="handleSearch" />
                            <button class="btn btn-primary" type="button" @click="handleSearch"
                                :disabled="isSearching || searchQuery.length < 2">
                                <span v-if="isSearching">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    Поиск...
                                </span>
                                <span v-else>
                                    <i class="bi bi-arrow-right me-2"></i>
                                    Найти
                                </span>
                            </button>
                        </div>
                    </div>

                    <div v-if="searchError" class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        {{ searchError }}
                    </div>

                    <div v-else-if="isSearching" class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Поиск...</span>
                        </div>
                        <p class="text-muted">Выполняется поиск...</p>
                    </div>

                    <div v-else-if="searchResults.tree.length === 0 && searchResults.content.length === 0"
                        class="text-center py-5">
                        <i class="bi bi-search-heart display-1 text-muted mb-3"></i>
                        <h5>Ничего не найдено</h5>
                        <p class="text-muted">
                            Попробуйте изменить поисковый запрос или фильтры
                        </p>
                    </div>

                    <div v-else-if="searchResults.tree.length > 0 || searchResults.content.length > 0"
                        class="search-results">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">
                                Найдено: {{ searchResults.tree.length + searchResults.content.length }}
                            </h6>
                            <button class="btn btn-sm btn-outline-secondary" @click="clearSearch">
                                <i class="bi bi-x-circle me-1"></i>
                                Очистить
                            </button>
                        </div>

                        <div class="list-group">
                            <div v-for="result in searchResults.tree" :key="result.id"
                                class="list-group-item list-group-item-action search-result-item"
                                @click="navigateToResult(result)">

                                <div class="d-flex align-items-center">


                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center">
                                            <h6 class="mb-0 me-2">{{ result.name }}</h6>
                                            <span class="badge bg-success">
                                                по названию
                                            </span>
                                        </div>
                                        <div class="result-meta small text-muted mt-1">
                                            <span v-if="result.created_at" class="me-3">
                                                <i class="bi bi-calendar me-1"></i>
                                                {{ new Date(result.created_at).toLocaleDateString() }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="result-action ms-3">
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </div>
                                </div>
                            </div>
                            <div v-for="result in searchResults.content" :key="result.id"
                                class="list-group-item list-group-item-action search-result-item"
                                @click="navigateToResult(result)">
                                <div class="d-flex align-items-center">


                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center">
                                            <h6 class="mb-0 me-2">{{ result.tree.name }}</h6>
                                            <span class="badge bg-info">
                                                по содержимому
                                            </span>
                                        </div>
                                        <div class="result-meta small text-muted mt-1">
                                            <span v-if="result.created_at" class="me-3">
                                                <i class="bi bi-calendar me-1"></i>
                                                {{ new Date(result.created_at).toLocaleDateString() }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="result-action ms-3">
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </CModalBody>
            <CModalFooter>
                <CButton color="secondary" @click="closeSearchModal">
                    Закрыть
                </CButton>
            </CModalFooter>
        </CModal>



        <CModal :visible="visibleModalFolder" @close="
            () => {
                visibleModalFolder = false;
            }
        " aria-labelledby="FolderLabel">
            <CModalHeader>
                <CModalTitle id="FolderLabel">
                    {{ folderId ? "Редактирование папки" : "Новая папка" }}
                </CModalTitle>
            </CModalHeader>
            <CModalBody>
                <div class="w-100 d-flex flex-column gap-4">
                    <div class="w-100 d-flex flex-column gap-2">
                        <CFormInput v-model="folderTitle" v-on:keyup.enter="
                            () => {
                                save();
                            }
                        " name="folderName" type="text" placeholder="Новое имя папки" />
                    </div>
                </div>
            </CModalBody>
            <CModalFooter>
                <CButton color="secondary" @click="
                    () => {
                        visibleModalFolder = false;
                    }
                ">
                    Отмена
                </CButton>
                <CButton color="primary" @click="
                    () => {
                        save();
                    }
                ">Сохранить</CButton>
            </CModalFooter>
        </CModal>
        <CModal
            :visible="confirmDialog.visible"
            @close="closeGlobalConfirm(false)"
            alignment="center"
            backdrop="static"
            aria-labelledby="GlobalConfirmLabel"
        >
            <CModalHeader>
                <CModalTitle id="GlobalConfirmLabel">
                    {{ confirmDialog.title }}
                </CModalTitle>
            </CModalHeader>
            <CModalBody>
                {{ confirmDialog.message }}
            </CModalBody>
            <CModalFooter>
                <CButton
                    color="secondary"
                    @click="closeGlobalConfirm(false)"
                >
                    {{ confirmDialog.cancelText }}
                </CButton>
                <CButton
                    color="primary"
                    @click="closeGlobalConfirm(true)"
                >
                    {{ confirmDialog.confirmText }}
                </CButton>
            </CModalFooter>
        </CModal>

    </div>
</template>

<script>
import AppFooter from "../components/AppFooter.vue";
import AppHeader from "../components/AppHeader.vue";
import AppSidebar from "../components/AppSidebar.vue";
import AuthWindow from "../components/AuthWindow.vue";
import { useAuthIdStore } from "../stores/authId";
import { useSidebarStore } from "../stores/sidebar";
import AppBreadcrumb from "../components/AppBreadcrumb.vue";
import AntiCopyProtection from "../components/AntiCopyProtection.vue";
import { closeConfirmDialog, confirmState } from "../utils/uiConfirm";

export default {
    components: {
        AppFooter,
        AppHeader,
        AppSidebar,
        AuthWindow,
        AppBreadcrumb,
        AntiCopyProtection,
    },

    data() {
        return {
            menu: [],
            server: window.location.origin,
            api: window.location.origin + "/api/",
            openWindow: false,
            auths: useAuthIdStore(),
            sidebar: useSidebarStore(),
            dashboard: null,
            about: null,
            viewSuccess: false,
            testUiBlocked: false,
            content: null,
            breadcrumbs: ["Документы"],
            allId: [],
            visibleSearchModal: false,
            searchQuery: "",
            searchResults: { tree: [], content: [] },
            isSearching: false,
            searchError: null,
            visibleModalFolder: false,
            folderParent: null,
            folderId: null,
            folderTitle: "",
            confirmDialog: confirmState,
            sidebarWidth: 320,
            isResizingSidebar: false,
        };
    },
    mounted() {
        this.restoreSidebarWidth();
        this.getMenu();
        window.addEventListener("mousemove", this.handleSidebarResize);
        window.addEventListener("mouseup", this.stopSidebarResize);
        window.addEventListener("resize", this.handleWindowResize);
        this.$nextTick(() => {
            this.applySidebarWidthToDom();
        });
    },
    beforeUnmount() {
        window.removeEventListener("mousemove", this.handleSidebarResize);
        window.removeEventListener("mouseup", this.stopSidebarResize);
        window.removeEventListener("resize", this.handleWindowResize);
    },
    watch: {
        $route() {
            this.scheduleBreadcrumbsUpdate();
        },
        effectiveSidebarWidth() {
            this.$nextTick(() => {
                this.applySidebarWidthToDom();
            });
        },
        "sidebar.visible"() {
            this.$nextTick(() => {
                this.applySidebarWidthToDom();
            });
        },
        showChrome() {
            this.$nextTick(() => {
                this.applySidebarWidthToDom();
            });
        },
    },
    computed: {
        showChrome() {
            return !this.testUiBlocked;
        },
        canRenderRoute() {
            return this.viewSuccess || this.isStandaloneRoute;
        },
        hasSidebarMenu() {
            return this.menu.length > 0;
        },
        isStandaloneRoute() {
            return ["NotFound", "Page500"].includes(this.$route.name);
        },
        splitterStyle() {
            return {
                left: `${this.effectiveSidebarWidth - 3}px`,
            };
        },
        wrapperStyle() {
            if (!this.showChrome || this.isMobileViewport) {
                return {
                    paddingLeft: "0px",
                };
            }

            return {
                paddingLeft: `${this.effectiveSidebarWidth}px`,
            };
        },
        effectiveSidebarWidth() {
            if (!this.hasSidebarMenu || this.sidebar.visible === false) {
                return 0;
            }

            if (this.sidebar.unfoldable) {
                return 64;
            }

            return this.sidebarWidth;
        },
        isMobileViewport() {
            return window.innerWidth <= 991;
        },
        showSplitter() {
            return (
                this.showChrome &&
                this.hasSidebarMenu &&
                !this.isMobileViewport &&
                this.sidebar.visible !== false &&
                !this.sidebar.unfoldable
            );
        },
    },
    methods: {
        restoreSidebarWidth() {
            const savedWidth = Number(
                localStorage.getItem("sidebarWidthPx") || 320
            );

            if (!Number.isNaN(savedWidth)) {
                this.sidebarWidth = this.clampSidebarWidth(savedWidth);
            }
        },
        clampSidebarWidth(width) {
            const minWidth = 240;
            const maxWidth = Math.min(520, window.innerWidth - 320);
            return Math.max(minWidth, Math.min(width, maxWidth));
        },
        startSidebarResize() {
            this.isResizingSidebar = true;
            document.body.classList.add("sidebar-resizing");
        },
        handleSidebarResize(event) {
            if (!this.isResizingSidebar || this.isMobileViewport) {
                return;
            }

            this.sidebarWidth = this.clampSidebarWidth(event.clientX);
        },
        stopSidebarResize() {
            if (!this.isResizingSidebar) {
                return;
            }

            this.isResizingSidebar = false;
            document.body.classList.remove("sidebar-resizing");
            localStorage.setItem(
                "sidebarWidthPx",
                String(this.sidebarWidth)
            );
        },
        handleWindowResize() {
            this.sidebarWidth = this.clampSidebarWidth(this.sidebarWidth);
            if (this.isMobileViewport) {
                this.stopSidebarResize();
            }
            this.applySidebarWidthToDom();
        },
        applySidebarWidthToDom() {
            const sidebarEl = document.querySelector(".sidebar");
            if (!sidebarEl) {
                return;
            }

            if (
                !this.showChrome ||
                !this.hasSidebarMenu ||
                this.isMobileViewport ||
                this.sidebar.unfoldable ||
                this.sidebar.visible === false
            ) {
                sidebarEl.style.removeProperty("width");
                sidebarEl.style.removeProperty("min-width");
                sidebarEl.style.removeProperty("max-width");
                sidebarEl.style.removeProperty("flex-basis");
                sidebarEl.style.removeProperty("--cui-sidebar-width");
                return;
            }

            const width = `${this.effectiveSidebarWidth}px`;
            sidebarEl.style.setProperty("width", width);
            sidebarEl.style.setProperty("min-width", width);
            sidebarEl.style.setProperty("max-width", width);
            sidebarEl.style.setProperty("flex-basis", width);
            sidebarEl.style.setProperty("--cui-sidebar-width", width);
        },
        scheduleBreadcrumbsUpdate() {
            this.$nextTick(() => {
                this.getBreadcrumbs();
            });
        },
        addFirstLevel() {
            this.visibleModalFolder = true;
            this.folderTitle = "";
            this.folderId = null;
            this.folderParent = "new";
        },
        editFolder(id, name) {
            this.visibleModalFolder = true;
            this.folderTitle = name;
            this.folderId = id;
            this.folderParent = "";
        },
        newFolder(id) {
            this.visibleModalFolder = true;
            this.folderTitle = "";
            this.folderParent = id;
            this.folderId = null;
        },
        save() {
            let form = { name: this.folderTitle };

            if (this.folderParent) {
                form.tree_id = this.folderParent;
            } else {
                form.id = this.folderId;
            }

            this.datasend("folder", "POST", form)
                .then((res) => {
                    if (res.success) {
                        this.getMenu();
                        this.folderTitle = "";
                        this.folderId = "";
                        this.folderParent = "";
                        this.visibleModalFolder = false;
                        this.showToast(res.message, "success");
                    } else if (res.errors) {
                        this.catchError(res.errors);
                    }
                })
                .catch((error) => {
                    this.showToast("Не удалось сохранить папку", "danger");
                });
        },
        openSearchModal() {
            this.visibleSearchModal = true;
            this.searchQuery = "";
            this.searchResults = { tree: [], content: [] };

            this.searchError = null;
            this.$nextTick(() => {
                const searchInput = this.$refs.searchInput;
                if (searchInput) {
                    searchInput.focus();
                }
            });
        },

        closeSearchModal() {
            this.visibleSearchModal = false;
            this.searchQuery = "";
            this.searchResults = { tree: [], content: [] };
            this.searchError = null;
        },


        handleSearch() {
            this.searchError = null;
            this.searchResults = { tree: [], content: [] };
            this.isSearching = true;

            let form = { search: this.searchQuery, allId: JSON.stringify(this.allId) };
            this.datasend("search", "POST", form)
                .then((res) => {
                    this.isSearching = false;
                    if (res.tree.length === 0 && res.content.length === 0) {
                        this.searchError = 'Ничего не найдено';
                    } else {
                        this.searchResults = res;
                    }

                })
                .catch((error) => {
                    this.showToast("Не удалось выполнить поиск", "danger");
                    this.isSearching = false;
                });
        },

        clearSearch() {
            this.searchQuery = "";
            this.searchResults = { tree: [], content: [] };
            this.searchError = null;
        },
        closeGlobalConfirm(result) {
            closeConfirmDialog(result);
        },

        navigateToResult(result) {
            this.closeSearchModal();
            const slug =
                typeof result === "object"
                    ? result.slug || (result.tree && result.tree.slug) || null
                    : null;

            if (!slug) {
                this.showToast("Ссылка для документа не найдена", "danger");
                return;
            }

            this.$router.push({
                name: "ShowFile",
                params: { slug },
            });
        },
        blockForTest(block = true) {
            this.testUiBlocked = block;
        },

        showToast(message, type = "info") {
            const toast = document.createElement("div");
            if (type == "true" || type == true) {
                type = "success";
            }
            toast.className = `alert alert-${type} alert-dismissible fade show`;
            toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      `;
            toast.style.position = "fixed";
            toast.style.top = "20px";
            toast.style.right = "20px";
            toast.style.zIndex = "1060";
            document.body.appendChild(toast);

            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 3000);
        },
        setContent(content) {
            this.content = content;
            this.scheduleBreadcrumbsUpdate();
        },

        openWindowFunction() {
            this.openWindow = !this.openWindow;
        },
        async datasend(
            path,
            method = "POST",
            data = {},
            formIsData = false,
            isBlob = false
        ) {
            const myHeaders = new Headers();
            if (localStorage.getItem("token")) {
                myHeaders.append(
                    "Authorization",
                    `Bearer ${localStorage.getItem("token")}`
                );
            }
            myHeaders.append("Accept", "application/json");
            const requestOptions = {
                method: method,
                headers: myHeaders,
            };
            if (method != "GET") {
                if (!formIsData)
                    myHeaders.append("Content-Type", "application/json");
                // requestOptions.body = data;
                requestOptions.body = formIsData ? data : JSON.stringify(data);
            }
            // if (method == "PUT") {
            // myHeaders.append("Content-Type", "application/json");
            // requestOptions.body = JSON.stringify(data);
            // }

            try {
                let response = await fetch(
                    this.api + path,
                    requestOptions
                ).catch((error) => {
                    this.$router.push({ name: "Page500" });
                });
                if (!response) {
                    return Promise.reject(
                        new Error("Не удалось получить ответ от сервера")
                    );
                }
                if (response.status == 403 || response.status == 401) {
                    this.logoutFun();
                    return response.json();
                }
                return (await !isBlob) ? response.json() : response.blob();
            } catch (error) {
                this.$router.push({ name: "Page500" });
                return Promise.reject(error);
            }
        },
        catchError(error) {
            for (let index = 0; index < Object.keys(error).length; index++) {
                Object.values(error)[index].forEach((element) => {
                    this.showToast(element, "danger");
                });
            }
        },
        logoutFun() {
            localStorage.removeItem("token");
            this.auths.changeUser(null, null, null, null);
            this.getMenu();
            this.$router.push({ name: "Home" });
        },
        getMenu() {
            this.menu = [];
            if (!localStorage.getItem("token")) {
                this.datasend("homepage", "GET", {}).then((res) => {
                    this.dashboard = res.content;
                    this.about = res.about;
                    this.viewSuccess = true;
                });
            } else {
                this.datasend("userFolder", "GET", {})
                    .then((res) => {

                        this.allId = res.allId;
                        let menus = res.menu;

                        this.dashboard = res.content;
                        this.about = res.about;

                        if (localStorage.getItem("token")) {
                            let user = res.user;
                            this.auths.changeUser(
                                user.id,
                                user.name,
                                user.role
                            );
                        }
                        this.viewSuccess = true;

                        function menucreateparent() {
                            let rrr = [];
                            menus.forEach((e) => {
                                if (e.tree_id == null) {
                                    e.title = e.name;
                                    e.icon = "bi bi-folder2";

                                    e.child = menucreate(e.id);
                                    rrr.push(e);
                                    e.tree_id = 0;
                                }
                            });
                            rrr.sort((f, s) => f.position - s.position);
                            return rrr;
                        }

                        function menucreate(i = 0) {
                            let rrr = [];
                            menus.forEach((e) => {
                                if (e.tree_id == i) {
                                    if (e.type == "file") {
                                        e.href = "/files/" + e.slug;
                                    }
                                    e.title = e.name;
                                    e.icon =
                                        e.type == "folder"
                                            ? "bi bi-folder2"
                                            : e.accessibilitylink
                                            ? "bi bi-file text-primary"
                                            : "bi bi-file";
                                    e.child = menucreate(e.id);
                                    rrr.push(e);
                                }
                            });
                            rrr.sort((f, s) => f.position - s.position);
                            return rrr;
                        }

                        this.menu = menucreateparent();
                        this.menu = this.transformItems(this.menu);
                        this.scheduleBreadcrumbsUpdate();
                        this.$nextTick(() => {
                            this.applySidebarWidthToDom();
                        });
                    })
                    .catch((error) => {
                        this.showToast("Не удалось загрузить дерево документов", "danger");
                    });
            }
        },
        transformItems(items) {
            let it = items.map((item) => {
                // if (item.type == "folder" && item.child.length == 0) {
                // 	item.child = [{}];
                // }

                return {
                    ...item,
                    ...(item.child && {
                        child: this.transformItems(item.child),
                    }),
                };
            });

            return it;
        },
        // showToast(success, message) {
        //     if (success) {
        //         toast.success(message, {
        //             theme: "colored",
        //             transition: toast.TRANSITIONS.ZOOM,
        //             position: toast.POSITION.BOTTOM_RIGHT,
        //             multiple: false,
        //             autoClose: 3000,
        //         });
        //     } else {
        //         toast.error(message, {
        //             theme: "colored",
        //             transition: toast.TRANSITIONS.ZOOM,
        //             position: toast.POSITION.BOTTOM_RIGHT,
        //             autoClose: 3000,
        //         });
        //     }
        // },
        saveEditFile() {
            this.$refs.routeViewComponent?.save?.();
        },
        findMenuPath(items, predicate, trail = []) {
            for (const item of items) {
                const nextTrail = [...trail, item];

                if (predicate(item)) {
                    return nextTrail;
                }

                if (item.child?.length) {
                    const nestedPath = this.findMenuPath(
                        item.child,
                        predicate,
                        nextTrail
                    );

                    if (nestedPath.length) {
                        return nestedPath;
                    }
                }
            }

            return [];
        },
        getCurrentMenuPath() {
            const routeName = this.$route.name;
            const routeParams = this.$route.params || {};
            const contentTreeId = Number(
                this.content?.tree_id || this.content?.tree?.id || 0
            );
            const routeId = Number(routeParams.id || routeParams.parent || 0);
            const currentSlug =
                this.content?.tree?.slug ||
                this.content?.slug ||
                routeParams.slug ||
                null;

            if (!this.menu.length) {
                return [];
            }

            if (routeName === "CreateFile" && routeId) {
                return this.findMenuPath(
                    this.menu,
                    (item) => Number(item.id) === routeId
                );
            }

            if (contentTreeId) {
                const pathByContentId = this.findMenuPath(
                    this.menu,
                    (item) => Number(item.id) === contentTreeId
                );

                if (pathByContentId.length) {
                    return pathByContentId;
                }
            }

            if (routeId) {
                const pathByRouteId = this.findMenuPath(
                    this.menu,
                    (item) => Number(item.id) === routeId
                );

                if (pathByRouteId.length) {
                    return pathByRouteId;
                }
            }

            if (currentSlug) {
                return this.findMenuPath(
                    this.menu,
                    (item) => item.slug === currentSlug
                );
            }

            return [];
        },
        getBreadcrumbs() {
            const routeTitle = this.$router.currentRoute.value.meta.title;
            const arr = ["Документы"];
            const contentName =
                this.content?.name || this.content?.tree?.name || null;
            const menuPath = this.getCurrentMenuPath();

            if (menuPath.length) {
                arr.push(...menuPath.map((item) => item.name));

                if (
                    this.$route.name === "CreateFile" &&
                    arr[arr.length - 1] !== "Новый файл"
                ) {
                    arr.push("Новый файл");
                }

                this.breadcrumbs = arr;
                return;
            }

            if (
                contentName &&
                ["ShowFile", "EditFile", "CreateFile"].includes(
                    this.$route.name
                )
            ) {
                arr.push(contentName);
            } else if (this.$route.name === "CreateFile") {
                arr.push("Новый файл");
            } else if (routeTitle && this.$route.name !== "Home") {
                arr.push(routeTitle);
            }

            this.breadcrumbs = arr;
        },
    },
};
</script>

<style scoped>
.layout-shell {
    --cui-sidebar-width: 20rem;
    --cui-sidebar-occupy-start: 20rem;
}

.sidebar-splitter {
    position: fixed;
    top: 0;
    bottom: 0;
    width: 6px;
    cursor: col-resize;
    z-index: 1040;
    background: transparent;
}

.sidebar-splitter::before {
    content: "";
    position: absolute;
    top: 0;
    bottom: 0;
    left: 2px;
    width: 2px;
    background: rgba(13, 110, 253, 0.08);
    transition: background-color 0.15s ease;
}

.sidebar-splitter:hover::before,
.sidebar-splitter.is-dragging::before {
    background: rgba(13, 110, 253, 0.5);
}
</style>
