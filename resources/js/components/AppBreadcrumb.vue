<script>
export default {
    props: ["breadcrumbs"],
};
</script>

<template>
    <CBreadcrumb class="my-0">
        <CBreadcrumbItem v-for="item in breadcrumbs">
            {{ item }}
        </CBreadcrumbItem>
    </CBreadcrumb>
</template>
