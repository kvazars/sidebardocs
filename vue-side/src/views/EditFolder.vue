<template>
    <div class="w-100 d-flex justify-content-center">
        <CCard class="w-50">
            <CCardHeader>
                Редактирование папки
            </CCardHeader>
            <CCardBody class="d-flex justify-content-center">
                <div class="w-100 d-flex align-items-center flex-column gap-3">
                    <!-- edit {{ id }} -->
                    <div class="w-100 d-flex flex-column gap-4">
                        <div class="w-100 d-flex flex-column gap-2">
                            <label for="folderName" class=" fw-bold">Изменение имени папки</label>
                            <CFormInput v-model="folderTitle" name="folderName" type="text" id="folderName" placeholder="Новое имя папки" />
                        </div>
                        <div class="w-100 d-flex flex-row gap-2">
                            <CButton color="success" class="w-50 text-white" @click="save">Сохранить</CButton>
                            <CButton color="danger" class="w-50 text-white" disabled>Удалить папку</CButton>
                        </div>
                    </div>
                </div>
            </CCardBody>
        </CCard>
    </div>
</template>
<script>
export default {
    props: ["id", "parent", 'datasend'],
    data() {
        return {
            folderTitle: null,
        }
    },
    mounted() {
        // if (this.id) {
        //     this.datasend('folder/' + this.id, 'GET', {}).then((res) => {

        //         // console.log(JSON.parse(res.content.data).blocks);
        //         this.pagetitle = res.name;
        //         this.dataBlock = JSON.parse(res.content.data).blocks;
        //         this.createEditor();
        //     }).catch((error) => {
        //         console.log(error);
        //     });
        // }
    },
    methods: {
        // deleteFolder() {
        //     alert("");
        //     this.datasend('resource/' + this.id, 'DELETE', {}).then((res) => {
        //         // route.push({name: 'home'})
        //         console.log(res);

        //     }).catch(error => console.log(error));

        // },
        save() {
            let form = new FormData();
            form.append('name', this.folderTitle);
            if (this.parent) {
                form.append('tree_id', this.parent);
            } else {
                form.append('id', this.id)
            }
            this.datasend('folder', 'POST', form).then(
                (res) => {
                    console.log(res);

                    // if (res.success) {

                    // } else {

                    // }
                }
            ).catch((error) => {
                console.log(error);
            });

        },
    },
}
</script>
