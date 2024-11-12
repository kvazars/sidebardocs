<template>
    <div v-if="viewOk" class="table-responsive">
        <div class="d-flex flex-row align-items-center gap-3">
            <span>Фильтр</span>
            <CFormSelect
                v-if="user.role == 'admin'"
                class="w-25 mt-1"
                @change="onChange($event)"
                v-model="seluser"
            >
                <option value="">Все пользователи</option>
                <option :value="u.id" v-for="u in users" :key="u">
                    {{ u.name }}
                </option>
            </CFormSelect>
            <CFormInput
                v-model="searchFilter.name"
                class="w-25 mt-1"
                @change="getFiles()"
                type="search"
                placeholder="Название файла"
            />
        </div>

        <CTable v-if="Object.keys(files.data).length > 0">
            <CTableHead>
                <CTableRow>
                    <CTableHeaderCell
                        scope="col"
                        v-if="
                            Object.values(files.data)[0] && user.role != 'ceo'
                        "
                    >
                        <span>Владелец</span>
                    </CTableHeaderCell>
                    <CTableHeaderCell
                        scope="col"
                        @click="
                            () => {
                                searchFilter.sortBy = 'name';
                                searchFilter.sortAsc = !searchFilter.sortAsc;
                                getFiles();
                            }
                        "
                    >
                        <div class="d-flex flex-row align-items-center">
                            <span
                                :class="
                                    searchFilter.sortBy == 'name' &&
                                    searchFilter.sortAsc
                                        ? 'sort-down'
                                        : searchFilter.sortBy == 'name' &&
                                          !searchFilter.sortAsc
                                        ? 'sort-up'
                                        : 'sort-out'
                                "
                                >Документ</span
                            >
                        </div></CTableHeaderCell
                    >
                    <CTableHeaderCell scope="col"
                        ><span>Родитель</span></CTableHeaderCell
                    >
                    <CTableHeaderCell scope="col"
                        ><span>Доступно всем</span></CTableHeaderCell
                    >
                    <CTableHeaderCell scope="col">Группы</CTableHeaderCell>
                    <CTableHeaderCell
                        scope="col"
                        @click="
                            () => {
                                searchFilter.sortBy = 'updated_at';
                                searchFilter.sortAsc = !searchFilter.sortAsc;
                                getFiles();
                            }
                        "
                        ><span
                            :class="
                                searchFilter.sortBy == 'updated_at' &&
                                searchFilter.sortAsc
                                    ? 'sort-down'
                                    : searchFilter.sortBy == 'updated_at' &&
                                      !searchFilter.sortAsc
                                    ? 'sort-up'
                                    : 'sort-out'
                            "
                            >Обновлено</span
                        ></CTableHeaderCell
                    >
                    <CTableHeaderCell scope="col"></CTableHeaderCell>
                </CTableRow>
            </CTableHead>
            <CTableBody>
                <CTableRow v-for="(val, key) in files.data" :key="val">
                    <template v-if="user.role != 'ceo'">
                        <CTableDataCell v-if="val.user"
                            >{{ val.user.name }}
                        </CTableDataCell>
                        <CTableDataCell v-else
                            >Удаленный пользователь
                        </CTableDataCell>
                    </template>

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
                        <CButtonGroup role="group" v-if="val.parent">
                            <CButton
                                :disabled="
                                    (!val.user || val.deleted_at != null) &&
                                    (user.role != 'ceo' ||
                                        val.deleted_at != null)
                                "
                                color="primary"
                                @click="
                                    () => {
                                        selGroups = key;
                                        visibleGroups = true;
                                    }
                                "
                                ><i class="fa fa-edit"></i
                            ></CButton>
                            <CButton
                                :disabled="
                                    (!val.user || val.deleted_at != null) &&
                                    (user.role != 'ceo' ||
                                        val.deleted_at != null)
                                "
                                color="primary"
                                @click="save(key)"
                                ><i class="fa fa-floppy-o"></i
                            ></CButton>

                            <router-link
                                class="btn btn-primary"
                                target="_blank"
                                :to="{
                                    name: 'ShowFile',
                                    params: { id: this.files.data[key].id },
                                }"
                            >
                                <i class="fa fa-paper-plane"></i
                            ></router-link>

                            <CButton
                                :disabled="!val.user && user.role != 'ceo'"
                                class="text-white"
                                :color="!val.deleted_at ? 'danger' : 'success'"
                                @click="remove(key)"
                                ><i
                                    class="fa"
                                    :class="
                                        !val.deleted_at
                                            ? 'fa-trash'
                                            : 'fa-check'
                                    "
                                ></i
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
import { useAuthIdStore } from "../stores/authId";

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
            users: [],
            viewOk: false,
            selGroups: null,
            visibleGroups: false,
            page: 1,
            seluser: null,
            user: useAuthIdStore(),
            searchFilter: {
                user: "",
                name: "",
                sortBy: "name",
                sortAsc: true,
            },
        };
    },
    mounted() {
        this.getFiles();
    },
    methods: {
        onChange(event) {
            this.searchFilter.user = event.target.value;
            this.getFiles();
        },
        save(id) {
            let form = new FormData();
            form.append("id", this.files.data[id].id);
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
                this.datasend(
                    "resourcedel/" + this.files.data[id].id,
                    "DELETE",
                    {}
                )
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
            // let form = "";
            let formHelper = [];

            //if (this.searchFilter.name)
            {
                formHelper.push(
                    "search=" + this.searchFilter.name.toLowerCase()
                );
            }

            if (this.searchFilter.user) {
                formHelper.push("user=" + this.searchFilter.user);
            }

            if (this.searchFilter.sortBy) {
                formHelper.push("sortBy=" + this.searchFilter.sortBy);
                formHelper.push("sortAsc=" + this.searchFilter.sortAsc);
            }

            let form = formHelper.join("&");
            // if (formHelper != []) {
            // formHelper.forEach((el) => {
            //     form = form + "&" + el;
            // });

            this.datasend(`getFiles?page=${this.page}&${form}`, "GET", {})
                .then((res) => {
                    this.files = res.data.files;

                    if (res.data.users.length > 0) {
                        this.users = res.data.users;
                    }

                    // let resData = res.data.files.data.reduce((a, x) => {
                    //     a[x.id] = x;
                    //     return a;
                    // }, {});

                    // this.files.data = resData;
                    this.files.data = res.data.files.data;

                    this.viewOk = true;
                })
                .catch((error) => {
                    console.log(error);
                });
        },
    },
};
</script>
