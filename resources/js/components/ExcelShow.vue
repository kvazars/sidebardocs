<template>
    <vue-office-excel
    :id="id"
        :src="file"
        style="height: 500px;"
    />
</template>
<script>
import VueOfficeExcel from '@vue-office/excel'
import '@vue-office/excel/lib/index.css'


export default {
    props: ["id", "file"],
    components: {
        VueOfficeExcel,
    },
};
</script>