<template>
    <div :id="id"></div>
</template>
<script>
import { init } from "pptx-preview";

export default {
    props: ["id", "file"],
    mounted() {
        let pptxPrviewer = init(document.getElementById(this.id), {
            width: 1024,
            height: 540,
        });
        fetch(this.file)
            .then((response) => {
                return response.arrayBuffer();
            })
            .then((res) => {
                pptxPrviewer.preview(res);
            });
    },
};
</script>