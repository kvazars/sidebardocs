<template>
<div class="vh70">
    <vue-pdf-embed :id="id" :source="file"  />
</div>
</template>
<script>
import VuePdfEmbed from 'vue-pdf-embed'

export default {
    props: ["id", "file"],
    components: {
        VuePdfEmbed,
    },
};
</script>
<style>
.vh70{
    max-height: 100vh;
    overflow-y: auto;
    overflow-x: hidden;
}
</style>