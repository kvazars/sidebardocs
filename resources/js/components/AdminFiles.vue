<template>
    <div v-if="viewOk" class="table-responsive">
        <div></div>
        <CTable>
            <CTableHead>
                <CTableRow>
                    <CTableHeaderCell
                        scope="col"
                        v-if="Object.values(files.data)[0].user"
                        ><span class="d-flex flex-row align-items-baseline"
                            >Владелец<i
                                class="fa fa-sort-desc"
                                aria-hidden="true"
                            ></i>
                        </span>
                    </CTableHeaderCell>
                    <CTableHeaderCell scope="col">
                        <div class="d-flex flex-row align-items-center">
                            <span class="sort-up">Документ</span>
                        </div></CTableHeaderCell
                    >
                    <CTableHeaderCell scope="col"
                        ><span class="d-flex flex-row align-items-center"
                            >Родитель<i
                                class="fa fa-sort-asc"
                                aria-hidden="true"
                            ></i> </span
                    ></CTableHeaderCell>
                    <CTableHeaderCell scope="col"
                        ><span class="d-flex flex-row align-items-center"
                            >Доступно всем<i
                                class="fa fa-sort-asc"
                                aria-hidden="true"
                            ></i> </span
                    ></CTableHeaderCell>
                    <CTableHeaderCell scope="col"
                        ><span class="d-flex flex-row align-items-center"
                            >Группы<i
                                class="fa fa-sort-asc"
                                aria-hidden="true"
                            ></i> </span
                    ></CTableHeaderCell>
                    <CTableHeaderCell scope="col"
                        ><span class="d-flex flex-row align-items-center"
                            >Обновлено<i
                                class="fa fa-sort-asc"
                                aria-hidden="true"
                            ></i> </span
                    ></CTableHeaderCell>
                    <CTableHeaderCell scope="col"></CTableHeaderCell>
                </CTableRow>
            </CTableHead>
            <CTableBody>
                <CTableRow v-for="val in files.data" :key="val">
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
                        {{
                            Object.keys(val.groups)
                                .filter((i) => val.groups[i].checked)
                                .map((aIndex) => val.groups[aIndex])
                                .map((el) => el.name)
                                .join(", ")
                        }}
                    </CTableDataCell>
                    <CTableDataCell>
                        {{
                            new Date(val.updated_at).toLocaleDateString() +
                            " " +
                            new Date(val.updated_at).toLocaleTimeString()
                        }}
                    </CTableDataCell>
                    <CTableDataCell class="text-end">
                        <CButtonGroup role="group">
                            <CButton
                                color="primary"
                                @click="
                                    () => {
                                        selGroups = val.id;
                                        visibleGroups = true;
                                    }
                                "
                                ><i class="fa fa-edit"></i
                            ></CButton>
                            <CButton color="primary" @click="save(val.id)"
                                ><i class="fa fa-floppy-o"></i
                            ></CButton>

                            <router-link
                                class="btn btn-primary"
                                target="_blank"
                                :to="{
                                    name: 'ShowFile',
                                    params: { id: val.id },
                                }"
                            >
                                <i class="fa fa-paper-plane"></i
                            ></router-link>

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
        <div class="d-flex justify-content-end">
            <Bootstrap5Pagination
                :data="files"
                @pagination-change-page="setPage"
            />
        </div>
    </div>

    <CModal
        :visible="visibleGroups"
        size="lg"
        @close="
            () => {
                visibleGroups = false;
            }
        "
        aria-labelledby="LiveDemoExampleLabel"
    >
        <CModalHeader>
            <CModalTitle id="LiveDemoExampleLabel">Группы</CModalTitle>
        </CModalHeader>
        <CModalBody>
            <div class="container">
                <div class="row">
                    <div
                        class="form-check col-lg-2"
                        v-for="(val, index) in this.files.data[selGroups]
                            .groups"
                        :key="index"
                    >
                        <input
                            class="form-check-input"
                            type="checkbox"
                            v-model="val.checked"
                            :id="'group_' + val.id + '-' + selGroups"
                        />
                        <label
                            style="user-select: none"
                            class="form-check-label"
                            :for="'group_' + val.id + '-' + selGroups"
                        >
                            {{ val.name }}
                        </label>
                    </div>
                </div>
            </div>
        </CModalBody>
        <CModalFooter>
            <CButton
                color="primary"
                @click="
                    () => {
                        visibleGroups = false;
                    }
                "
                >Выйти</CButton
            >
        </CModalFooter>
    </CModal>
</template>
<script>
import { Bootstrap5Pagination } from "laravel-vue-pagination";

export default {
    components: {
        Bootstrap5Pagination,
    },
    props: ["datasend", "catchError", "showToast"],
    data() {
        return {
            files: null,
            groups: null,
            group: [],
            viewOk: false,
            selGroups: null,
            visibleGroups: false,
            page: 1,
        };
    },
    mounted() {
        this.getFiles();
    },
    methods: {
        save(id) {
            let form = new FormData();
            form.append("id", id);
            form.append(
                "accessibility",
                this.files.data[id].child.accessibility ? 1 : 0
            );
            form.append("groups", JSON.stringify(this.files.data[id].groups));
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
                        if (res.success) {
                            this.getFiles();
                            this.showToast(res.success, res.message);
                        }
                    })
                    .catch((error) => console.log(error));
            }
        },
        setPage(page) {
            this.page = page;
            this.getFiles();
        },
        getFiles() {
            this.datasend(`getFiles?page=${this.page}`, "GET", {})
                .then((res) => {
                    this.files = res;

                    let resData = res.data.reduce((a, x) => {
                        a[x.id] = x;
                        return a;
                    }, {});

                    this.files.data = resData;
                    console.log(this.files);

                    this.viewOk = true;
                })
                .catch((error) => {
                    console.log(error);
                });
        },
    },
};
</script>
