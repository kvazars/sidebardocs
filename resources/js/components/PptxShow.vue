<template>
    <vue-office-pptx :id="id" :src="file" />
</template>
<script>
import VueOfficePptx from "@vue-office/pptx";

export default {
    props: ["id", "file"],
    components: {
        VueOfficePptx,
    },
};
</script>
