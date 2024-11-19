<template>
    <div class="text-center my-4" v-if="newCode">
        <div v-html="newCode" class="iframeh100"></div>
    </div>
</template>
<script>
export default {
    props: ["code"],
    data() {
        return {
            newCode: null,
        };
    },
    mounted() {
        this.codeChange();
    },
    methods: {
        codeChange() {
            this.newCode = this.code
                .replace(/<script[^>]*>(?:(?!<\/script>)[^])*<\/script>/g, "")
                .replace("?iframe=1", "");
        },
    },
};
</script>
