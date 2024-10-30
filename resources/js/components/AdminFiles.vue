<template>
    <div v-if="files">
        <CTable>
            <CTableHead>
                <CTableRow>
                    <CTableHeaderCell scope="col">#</CTableHeaderCell>
                    <CTableHeaderCell scope="col">Class</CTableHeaderCell>
                    <CTableHeaderCell scope="col">Heading</CTableHeaderCell>
                    <CTableHeaderCell scope="col">Heading</CTableHeaderCell>
                    <CTableHeaderCell scope="col">Save</CTableHeaderCell>
                </CTableRow>
            </CTableHead>
            <CTableBody>
                <CTableRow v-for="val in files">
                    <CTableDataCell>{{ val.name }}</CTableDataCell>
                    <CTableDataCell>{{ val.parent.name }}</CTableDataCell>
                    <CTableDataCell>{{
                        val.child.accessibility
                    }}</CTableDataCell>
                    <CTableDataCell>
                        <div v-for="(g, index) in groups" :key="index">
                            {{ g.name }}
                        </div>
                    </CTableDataCell>
                    <CTableDataCell
                        ><CButtonGroup role="group">
                            <CButton color="primary" @click="save(val.id)"
                                ><i class="fa fa-floppy-o"></i
                            ></CButton>
                            <CButton
                                class="text-white"
                                color="danger"
                                @click="delete val.id"
                                ><i class="fa fa-trash"></i
                            ></CButton>
                        </CButtonGroup>
                    </CTableDataCell>
                </CTableRow>
            </CTableBody>
        </CTable>
    </div>
</template>
<script>
export default {
    props: ["datasend", "catchError", "showToast"],
    data() {
        return {
            files: null,
            groups: null,
        };
    },
    mounted() {
        this.getFiles();
    },
    methods: {
        save(id) {
            
        },
        delete(id) {
            this.datasend("resource/" + id, "DELETE", {})
                .then((res) => {
                    if (res.success) {
                        this.getFiles();
                        this.showToast(res.success, res.message);
                    }
                })
                .catch((error) => console.log(error));
        },
        getFiles() {
            this.datasend("getFiles", "GET", {})
                .then((res) => {
                    console.log(res);
                    this.files = res.files;
                    this.groups = res.groups;
                })
                .catch((error) => {
                    console.log(error);
                });
        },
    },
};
</script>
