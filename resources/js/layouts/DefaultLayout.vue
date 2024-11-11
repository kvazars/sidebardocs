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
        setContent(content) {
            this.content = content;
        },

        openWindowFunction() {
            this.openWindow = !this.openWindow;
        },
        async datasend(path, method = "POST", data = {}) {
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
                requestOptions.body = data;
            }
            if (method == "PUT") {
                let object = {};
                data.forEach(function (value, key) {
                    object[key] = value;
                });
                myHeaders.append("Content-Type", "application/json");
                requestOptions.body = JSON.stringify(object);
            }

            try {
                let response = await fetch(
                    this.api + path,
                    requestOptions
                ).catch(() => {
                    this.page500 = true;
                });
                if (response.status == 403 || response.status == 401) {
                    this.logoutFun();
                }
                return await response.json();
            } catch (error) {
                this.page500 = true;
                // this.$router.push({ name: "Page500" });
            }
        },
        catchError(error) {
            for (let index = 0; index < Object.keys(error).length; index++) {
                Object.values(error)[index].forEach((element) => {
                    this.showToast(false, element);
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
            this.datasend(
                localStorage.getItem("token") ? "userFolder" : "folder",
                "GET",
                {}
            )
                .then((res) => {
                    let menus = res.menu;

                    this.dashboard = res.content;
                    this.about = res.about;

                    if (localStorage.getItem("token")) {
                        let user = res.user;
                        this.auths.changeUser(user.id, user.name, user.role);
                    }
                    this.viewSuccess = true;

                    function menucreateparent() {
                        let rrr = [];
                        menus.forEach((e) => {
                            if (e.tree_id == null) {
                                e.title = e.name;
                                e.icon = "fa fa-folder";

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
                                        ? "fa fa-folder"
                                        : "fa fa-file";
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
        showToast(success, message) {
            if (success) {
                toast.success(message, {
                    theme: "colored",
                    transition: toast.TRANSITIONS.ZOOM,
                    position: toast.POSITION.BOTTOM_RIGHT,
                    multiple: false,
                    autoClose: 3000,
                });
            } else {
                toast.error(message, {
                    theme: "colored",
                    transition: toast.TRANSITIONS.ZOOM,
                    position: toast.POSITION.BOTTOM_RIGHT,
                    autoClose: 3000,
                });
            }
        },
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

<template>
    <div class="vh-100 position-relative">
        <AppSidebar
            v-if="menu.length > 0 || auths.id"
            :catchError="catchError"
            :showToast="showToast"
            :menu="menu"
            :datasend="datasend"
            :getMenu="getMenu"
            :about="about"
            :server="server"
        />
        <div class="wrapper d-flex flex-column">
            <AppHeader
                :openWindowFunction="openWindowFunction"
                :datasend="datasend"
                :logoutFun="logoutFun"
            />
            <CContainer
                class="p-3 position-sticky border-bottom breadcrumb_container d-flex justify-content-between align-items-center z1"
                fluid
            >
                <AppBreadcrumb
                    :breadcrumbs="breadcrumbs"
                    :content="content"
                    :saveEditFile="saveEditFile"
                />
            </CContainer>
            <div class="body flex-grow-1">
                <CContainer>
                    <template v-if="page500">
                        <Page500 />
                    </template>
                    <router-view
                        :server="server"
                        :catchError="catchError"
                        :datasend="datasend"
                        :api="api"
                        :getMenu="getMenu"
                        :showToast="showToast"
                        :key="$route.fullPath"
                        :authss="auths.id"
                        :setContent="setContent"
                        v-if="
                            !page500 &&
                            $route.name != 'Home' &&
                            $route.name != 'EditFile' &&
                            $route.name != 'Page500' &&
                            $route.name != 'admin' &&
                            viewSuccess
                        "
                    />
                    <router-view
                        :server="server"
                        :catchError="catchError"
                        :datasend="datasend"
                        :showToast="showToast"
                        :key="$route.fullPath"
                        :dashboard="dashboard"
                        :setContent="setContent"
                        :api="api"
                        :userRole="auths.role"
                        v-if="auths.id && dashboard && $route.name == 'admin'"
                    />

                    <template v-if="$route.name == 'Home' && dashboard">
                        <ShowFile
                            :server="server"
                            :dashboard="dashboard"
                            :about="about"
                        />
                    </template>
                    <EditFile
                        v-if="$route.name == 'EditFile'||$route.name == 'CreateFile'"
                        ref="EditFile"
                        :server="server"
                        :datasend="datasend"
                        :getMenu="getMenu"
                        :catchError="catchError"
                        :dashboard="dashboard"
                        :showToast="showToast"
                        :api="api"
                        :setContent="setContent"
                    />
                </CContainer>
            </div>
            <AppFooter :about="about" />
        </div>
        <AuthWindow
            :openWindow="openWindow"
            :openWindowFunction="openWindowFunction"
            :datasend="datasend"
            :catchError="catchError"
            :getMenu="getMenu"
        />
    </div>
</template>
