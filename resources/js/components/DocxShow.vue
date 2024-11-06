<template>
    <vue-office-docx
    :id="id"
        :src="file"
        style="height: 500px;"
    />
</template>
<script>
import VueOfficeDocx from '@vue-office/docx'
import '@vue-office/docx/lib/index.css'


export default {
    props: ["id", "file"],
    components: {
        VueOfficeDocx,
    },
};
</script>