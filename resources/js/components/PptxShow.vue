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
        <i class="bi" :class="fileShow ? 'bi-toggle-on' : 'bi-toggle-off'"></i>
    </div>
    <vue-office-pptx v-if="fileShow" :id="id" :src="file" />
</template>
<script>
import VueOfficePptx from "@vue-office/pptx";

export default {
    props: ["id", "file"],
    components: {
        VueOfficePptx,
    },
    data() {
        return {
            fileShow: false,
        };
    },
};
</script>
