<template>
    <div
        class="form-check-label ms-2 d-inline-block cursor-pointer"
        role="button"
        @click="
            () => {
                fileShow = !fileShow;
            }
        "
    >
        <span>Показать файл </span>
        <i class="fa" :class="fileShow ? 'fa-toggle-on' : 'fa-toggle-off'"></i>
    </div>
    <vue-office-excel
        v-if="fileShow"
        :id="id"
        :src="file"
        style="height: 500px"
    />
</template>
<script>
import VueOfficeExcel from "@vue-office/excel";
import "@vue-office/excel/lib/index.css";

export default {
    props: ["id", "file"],
    components: {
        VueOfficeExcel,
    },
    data() {
        return {
            fileShow: false,
        };
    },
};
</script>
