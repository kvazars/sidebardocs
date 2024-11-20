import { h, resolveComponent } from "vue";
import { createRouter, createWebHashHistory } from "vue-router";

import DefaultLayout from "../layouts/DefaultLayout.vue";

const routes = [
    {
        path: "/",
        name: "Home",
        meta: { title: "Главная" },
        component: DefaultLayout,
        children: [
            {
                path: "/files/:id",
                name: "ShowFile",
                meta: { title: "Просмотр файла" },
                component: () => import("../views/ShowFile.vue"),
                props: true,
            },
            {
                path: "/files/:id/edit",
                name: "EditFile",
                meta: { title: "Редактирование документа" },
                component: () => import("../views/EditFile.vue"),
                props: true,
            },
            {
                path: "/files/new/:parent",
                name: "CreateFile",
                meta: { title: "Создание документа" },
                component: () => import("../views/EditFile.vue"),
                props: true,
            },
            {
                path: "/:catchAll(.*)*",
                name: "NotFound",
                meta: { title: "Ресурс не найден" },
                component: () => import("../views/pages/Page404.vue"),
            },
            {
                path: "500",
                name: "Page500",
                component: () => import("../views/pages/Page500.vue"),
            },
            {
                path: "admin",
                name: "admin",
                meta: { title: "Управление" },
                component: () => import("../views/AdminPanel.vue"),
            },
            {
                path: "settings",
                name: "settings",
                meta: { title: "Настройки пользователя" },
                component: () => import("../views/TheSettings.vue"),
            },
        ],
    },
];

const router = createRouter({
    history: createWebHashHistory(import.meta.env.BASE_URL),
    routes,
    scrollBehavior() {
        // always scroll to top
        return { top: 0 };
    },
});

export default router;
