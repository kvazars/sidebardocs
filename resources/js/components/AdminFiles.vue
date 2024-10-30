<template>
    <div v-if="viewOk">
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
                    <CTableDataCell>{{ val.name }} </CTableDataCell>
                    <CTableDataCell>{{ val.parent.name }}</CTableDataCell>
                    <CTableDataCell>
                        <CFormSwitch
                            v-model="files[val.id].child.accessibility"
                            :id="'accessibility_for_' + val.id"
                    /></CTableDataCell>
                    <CTableDataCell>
                        <div v-for="(g, index) in val.groups" :key="index">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                v-model="files[val.id].groups[index].checked"
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

                    this.files = res;

                    // console.log(res.groups);
                    // this.groups = res.groups;

                    // Object.values(this.files).forEach((el) => {
                    //     // this.group.push({'id':5});

                    //     let gr = [];
                    //     this.group[el.id] = res.groups;
                    //     // console.log(res.groups);

                    //     Object.values(el.available).forEach((els) => {
                    //         gr.push(els.group_id);
                    //     });
                    //     console.log(gr);
                    //     // console.log(this.group[el.id], el.id);
                    //     Object.values(this.group[el.id]).forEach((elem) => {
                    //         console.log(elem, el.id);
                    //         // console.log(gr.includes(elem.id));
                    //         // console.log(this.group[el.id], el.id);
                    //         // if (gr.includes(elem.id)) {
                    //             this.group[el.id][elem.id].checked = gr.includes(elem.id);
                    //             //this.group[el.id] == els.group_id;
                    //             // console.log(this.group[el.id][elem.id]);
                    //         // } else {
                    //             // this.group[el.id][elem.id].checked = false;
                    //         // }
                    //     });
                    //     // console.log(this.group);

                    //     //  this.groups.checked =

                    //     el.child.accessibility = el.child.accessibility
                    //         ? true
                    //         : false;
                    // });
                    // console.log(this.group);

                    this.viewOk = true;
                })
                .catch((error) => {
                    console.log(error);
                });
        },
    },
};
</script>
