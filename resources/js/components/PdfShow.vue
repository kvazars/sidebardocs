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
    <div class="vh70" v-if="fileShow">
        <vue-pdf-embed :id="id" :source="file" />
    </div>
</template>
<script>
import VuePdfEmbed from "vue-pdf-embed";

export default {
    props: ["id", "file"],
    data() {
        return {
            fileShow: false,
        };
    },
    components: {
        VuePdfEmbed,
    },
};
</script>
<style>
.vh70 {
    max-height: 100vh;
    overflow-y: auto;
    overflow-x: hidden;
}
</style>
