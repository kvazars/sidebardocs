import { h, resolveComponent } from "vue";
import { createRouter, createWebHashHistory } from "vue-router";

import DefaultLayout from "@/layouts/DefaultLayout";

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
				component: () => import("@/views/ShowFile.vue"),
				props: true,
			},
			{
				path: "/files/:id/edit",
				name: "EditFile",
				meta: { title: "Редактирование документа", requiresAuth: true },
				component: () => import("@/views/editFile.vue"),
				props: true,
			},
			{
				path: "/files/new/:parent",
				name: "CreateFile",
				meta: { title: "Создание документа" },
				component: () => import("@/views/editFile.vue"),
				props: true,
			},
			{
				path: "/:catchAll(.*)*",
				name: "NotFound",
				meta: { title: "Ресурс не найден" },
				component: () => import("@/views/pages/Page404"),
			},
			{
				path: "500",
				name: "Page500",
				component: () => import("@/views/pages/Page500"),
			},
			{
				path: "admin",
				name: "admin",
				meta: { title: "Управление" },
				component: () => import("@/views/AdminPanel"),
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
