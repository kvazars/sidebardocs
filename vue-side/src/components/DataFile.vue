<template>
    <i class="fa fa-file"></i>&nbsp;
    <a :href="fileUrl" download>
        {{ title }}</a>
</template>

<script>
export default {
    props: ["src", "datasend", 'title'],
    data() {
        return {
            fileUrl: null,
        }
    },
    mounted() {
        let form = new FormData();
        form.append("file", this.src);
        this.datasend("getFile", "POST", form)
            .then((res) => {
                console.log(res);

                this.fileUrl = res.url;
                // this.imageAsBase64 = res.image;
            })
            .catch((error) => {
                console.log(error);
            });
    },
};
</script>