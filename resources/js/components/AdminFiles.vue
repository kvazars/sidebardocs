<template>
    <div v-if="viewOk">
        <CTable>
            <CTableHead>
                <CTableRow>
                    <CTableHeaderCell scope="col" v-if="files.user"
                        >Менеджер</CTableHeaderCell
                    >
                    <CTableHeaderCell scope="col">Документ</CTableHeaderCell>
                    <CTableHeaderCell scope="col">Родитель</CTableHeaderCell>
                    <CTableHeaderCell scope="col"
                        >Доступно всем</CTableHeaderCell
                    >
                    <CTableHeaderCell scope="col">Группы</CTableHeaderCell>
                    <CTableHeaderCell scope="col"></CTableHeaderCell>
                </CTableRow>
            </CTableHead>
            <CTableBody>
                <CTableRow v-for="val in files" :key="val">
                    <CTableDataCell v-if="val.user"
                        >{{ val.user.name }}
                    </CTableDataCell>
                    <CTableDataCell>{{ val.name }} </CTableDataCell>
                    <CTableDataCell>{{ val.parent.name }}</CTableDataCell>
                    <CTableDataCell>
                        <CFormSwitch
                            v-model="val.child.accessibility"
                            :id="'accessibility_for_' + val.id"
                        />
                    </CTableDataCell>
                    <CTableDataCell>
                        <div v-for="(g, index) in val.groups" :key="index">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                v-model="val.groups[index].checked"
                                :id="'group_' + val.id + '-' + g.id"
                            />
                            <label
                                style="user-select: none"
                                class="form-check-label"
                                :for="'group_' + val.id + '-' + g.id"
                            >
                                {{ g.name }}
                            </label>
                        </div>
                    </CTableDataCell>
                    <CTableDataCell>
                        <CButtonGroup role="group">
                            <CButton color="primary" @click="save(val.id)"
                                ><i class="fa fa-floppy-o"></i
                            ></CButton>
                            <CButton
                                class="text-white"
                                color="danger"
                                @click="remove(val.id)"
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
            group: [],
            viewOk: false,
        };
    },
    mounted() {
        this.getFiles();
    },
    methods: {
        save(id) {
            console.log(this.files[id]);
            let form = new FormData();
            form.append("id", id);
            form.append("accessibility", this.files[id].child.accessibility?1:0);
            form.append("groups", JSON.stringify(this.files[id].groups));
            this.datasend("saveresourceadmin", "POST", form)
                .then((res) => {
                    if (res.success) {
                        this.getFiles();
                        this.showToast(res.success, res.message);
                    }
                })
                .catch((error) => console.log(error));
        },
        remove(id) {
            if (confirm("Вы уверены?")) {
                this.datasend("resourcedel/" + id, "DELETE", {})
                    .then((res) => {
                        console.log(res);
                        if (res.success) {
                            this.getFiles();
                            this.showToast(res.success, res.message);
                        }
                    })
                    .catch((error) => console.log(error));
            }
        },
        getFiles() {
            this.datasend("getFiles", "GET", {})
                .then((res) => {
                    console.log(res);
                    this.files = res;
                    this.viewOk = true;
                })
                .catch((error) => {
                    console.log(error);
                });
        },
    },
};
</script>
