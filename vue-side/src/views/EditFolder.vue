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
                            <CFormInput name="folderName" type="text" id="folderName" placeholder="Новое имя папки" />
                        </div>
                        <div class="w-100 d-flex flex-row gap-2">
                            <CButton color="success" class="w-50 text-white">Сохранить</CButton>
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
    props: ["id"],
    data() {
        return {

        }
    },
}
</script>
