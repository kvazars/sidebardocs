<script setup>
import { onMounted, ref } from "vue";
import router from "../router/router";

const breadcrumbs = ref();

const getBreadcrumbs = () => {
	return router.currentRoute.value.matched.map((route) => {
		return {
			active: route.path === route.path,
			name: route.name,
			title: route.meta.title,
			path: `${router.options.history.base}${route.path}`,
		};
	});
};

router.afterEach(() => {
	breadcrumbs.value = getBreadcrumbs();
});

onMounted(() => {
	breadcrumbs.value = getBreadcrumbs();
});
</script>

<template>
	<CBreadcrumb class="my-0">
		<CBreadcrumbItem
			v-for="item in breadcrumbs"
			:key="item"
			:href="item.active ? '' : item.path"
			:active="item.active"
		>
			{{ item.title }}
		</CBreadcrumbItem>
	</CBreadcrumb>
</template>
