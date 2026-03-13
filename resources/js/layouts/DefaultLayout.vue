<template>
    <div class="vh-100 position-relative">
        <AppSidebar v-if="menu.length > 0 || auths.id" :catchError="catchError" :showToast="showToast" :menu="menu"
            :datasend="datasend" :getMenu="getMenu" :about="about" :server="server" :editFolder="editFolder" :newFolder="newFolder" />
        <div class="wrapper d-flex flex-column">
            <AppHeader :openWindowFunction="openWindowFunction" :datasend="datasend" :logoutFun="logoutFun"
                :openSearchModal="openSearchModal" :addFirstLevel="addFirstLevel" />
            <CContainer
                class="p-3 position-sticky border-bottom breadcrumb_container d-flex justify-content-between align-items-center z1"
                fluid id="bread">
                <AppBreadcrumb :breadcrumbs="breadcrumbs" :content="content" :saveEditFile="saveEditFile"
                    :datasend="datasend" :getMenu="getMenu" />
            </CContainer>
            <div class="body flex-grow-1">
                <CContainer>
                    <template v-if="page500">
                        <Page500 />
                    </template>
                    <router-view :server="server" :catchError="catchError" :datasend="datasend" :api="api"
                        :getMenu="getMenu" :showToast="showToast" :key="$route.fullPath" :authss="auths.id"
                        :blockForTest="blockForTest" :setContent="setContent" v-if="
                            !page500 &&
                            $route.name != 'Home' &&
                            $route.name != 'EditFile' &&
                            $route.name != 'CreateFile' &&
                            $route.name != 'Page500' &&
                            $route.name != 'admin' &&
                            viewSuccess
                        " />
                    <router-view :server="server" :catchError="catchError" :datasend="datasend" :showToast="showToast"
                        :key="$route.fullPath" :dashboard="dashboard" :setContent="setContent" :api="api"
                        :userRole="auths.role" v-if="auths.id && dashboard && $route.name == 'admin'" />

                    <template v-if="$route.name == 'Home' && dashboard">
                        <ShowFile :server="server" :dashboard="dashboard" :about="about" :showToast="showToast" />
                    </template>
                    <EditFile v-if="
                        $route.name == 'EditFile' ||
                        $route.name == 'CreateFile'
                    " ref="EditFile" :server="server" :key="$route.fullPath" :datasend="datasend" :getMenu="getMenu"
                        :catchError="catchError" :dashboard="dashboard" :showToast="showToast" :api="api"
                        :setContent="setContent" />
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
                                @click="navigateToResult(result.id)">

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
                                @click="navigateToResult(result.tree_id)">
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

    </div>
</template>

<script>
import AppFooter from "../components/AppFooter.vue";
import AppHeader from "../components/AppHeader.vue";
import AppSidebar from "../components/AppSidebar.vue";
import AuthWindow from "../components/AuthWindow.vue";
import { toast } from "vue3-toastify";
import { useAuthIdStore } from "../stores/authId";
import ShowFile from "../views/ShowFile.vue";
import EditFile from "../views/EditFile.vue";
import Page500 from "../views/pages/Page500.vue";
import AppBreadcrumb from "../components/AppBreadcrumb.vue";
import AntiCopyProtection from "../components/AntiCopyProtection.vue";

export default {
    components: {
        AppFooter,
        AppHeader,
        Page500,
        AppSidebar,
        AuthWindow,
        ShowFile,
        EditFile,
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
            dashboard: null,
            about: null,
            viewSuccess: false,
            page500: false,
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
        };
    },
    mounted() {
        this.getMenu();
    },
    watch: {
        $route() {
            setTimeout(this.getBreadcrumbs, 1000);
        },
    },
    methods: {
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
                    console.log(error);
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
                    console.log(error);
                    this.isSearching = false;
                });
        },

        clearSearch() {
            this.searchQuery = "";
            this.searchResults = { tree: [], content: [] };
            this.searchError = null;
        },

        navigateToResult(result) {
            this.closeSearchModal();
            this.$router.push({ name: "ShowFile", params: { id: result } });
        },
        blockForTest(block = true) {
            if (block) {
                document.querySelector(".sidebar").classList.add("d-none");
                document.querySelector("#file").classList.add("d-none");
                document.querySelector("#headerOne").classList.add("d-none");
                document.querySelector("#bread").classList.add("d-none");
            } else {
                document.querySelector(".sidebar").classList.remove("d-none");
                document.querySelector("#file").classList.remove("d-none");
                document.querySelector("#headerOne").classList.remove("d-none");
                document.querySelector("#bread").classList.remove("d-none");
            }
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
                    console.log(error);
                    this.page500 = true;
                });
                if (response.status == 403 || response.status == 401) {
                    this.logoutFun();
                }
                return (await !isBlob) ? response.json() : response.blob();
            } catch (error) {
                console.log(error);

                this.page500 = true;
                // this.$router.push({ name: "Page500" });
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
                                        e.href = "/files/" + e.id;
                                    }
                                    e.title = e.name;
                                    e.icon =
                                        e.type == "folder"
                                            ? "bi bi-folder2"
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
                        setTimeout(this.getBreadcrumbs, 1000);
                    })
                    .catch((error) => {
                        console.log(error);
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
            this.$refs.EditFile.save();
        },
        getBreadcrumbs() {
            let arr = [];

            document
                .querySelectorAll(".vsm--link_active>.vsm--title>span")
                .forEach((el) => {
                    arr.push(el.textContent);
                });

            if (!arr.length) {
                arr = [this.$router.currentRoute.value.meta.title];
            }
            this.breadcrumbs = arr;
        },
    },
};
</script>
