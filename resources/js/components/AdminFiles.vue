<template>
    <div v-if="viewOk">
        <div class="row">
            <div class="col-md-6">
                <div class="d-flex flex-row align-items-center gap-3">
                    <span>Фильтр</span>
                    <CFormSelect
                        v-if="user.role == 'admin'"
                        class="w-100 mt-1"
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
                        class="w-100 mt-1"
                        @change="getFiles()"
                        type="search"
                        placeholder="Название файла"
                    />
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end">
                    <CButton color="primary" @click="clearmyaccessfiles"
                        ><i class="bi bi-trash"></i> Сбросить все
                        доступы</CButton
                    >
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <CTable v-if="Object.keys(files.data).length > 0">
                <CTableHead>
                    <CTableRow>
                        <CTableHeaderCell
                            scope="col"
                            v-if="
                                Object.values(files.data)[0] &&
                                user.role != 'ceo'
                            "
                        >
                            <span>Владелец</span>
                        </CTableHeaderCell>
                        <CTableHeaderCell
                            scope="col"
                            @click="
                                () => {
                                    searchFilter.sortBy = 'name';
                                    searchFilter.sortAsc =
                                        !searchFilter.sortAsc;
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
                            ><span
                                >Доступно авторизованным</span
                            ></CTableHeaderCell
                        >
                        <CTableHeaderCell scope="col"
                            ><span>Доступно менеджерам</span></CTableHeaderCell
                        >
                        <CTableHeaderCell scope="col">Группы</CTableHeaderCell>
                        <CTableHeaderCell
                            scope="col"
                            @click="
                                () => {
                                    searchFilter.sortBy = 'updated_at';
                                    searchFilter.sortAsc =
                                        !searchFilter.sortAsc;
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
                            <CFormSwitch
                                v-model="val.child.accessibilitymanagers"
                                :id="'accessibilitymanagers_for_' + val.id"
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
                                    ><i class="bi bi-pencil"></i
                                ></CButton>
                                <CButton
                                    :disabled="
                                        (!val.user || val.deleted_at != null) &&
                                        (user.role != 'ceo' ||
                                            val.deleted_at != null)
                                    "
                                    color="primary"
                                    @click="save(key)"
                                    ><i class="bi bi-floppy2-fill"></i
                                ></CButton>

                                <router-link
                                    class="btn btn-primary"
                                    target="_blank"
                                    :to="{
                                        name: 'ShowFile',
                                        params: {
                                            slug: this.files.data[key].slug,
                                        },
                                    }"
                                >
                                    <i class="bi bi-binoculars-fill"></i
                                ></router-link>

                                <CButton
                                    :disabled="!val.user && user.role != 'ceo'"
                                    class="text-white"
                                    :color="
                                        !val.deleted_at ? 'danger' : 'success'
                                    "
                                    @click="remove(key)"
                                    ><i
                                        class="bi"
                                        :class="
                                            !val.deleted_at
                                                ? 'bi-trash'
                                                : 'bi-check'
                                        "
                                    ></i
                                ></CButton>
                            </CButtonGroup>
                        </CTableDataCell>
                    </CTableRow>
                </CTableBody>
            </CTable>
        </div>
        <div
            v-if="files && files.last_page > 1"
            class="d-flex justify-content-end mt-3"
        >
            <nav aria-label="Навигация по страницам файлов">
                <ul class="pagination mb-0">
                    <li
                        class="page-item"
                        :class="{ disabled: !files.prev_page_url }"
                    >
                        <button
                            class="page-link"
                            type="button"
                            :disabled="!files.prev_page_url"
                            @click="setPage(files.current_page - 1)"
                        >
                            Назад
                        </button>
                    </li>
                    <li
                        v-for="item in paginationItems"
                        :key="item.key"
                        class="page-item"
                        :class="{
                            active:
                                item.type === 'page' &&
                                item.page === files.current_page,
                            disabled: item.type === 'ellipsis',
                        }"
                    >
                        <span
                            v-if="item.type === 'ellipsis'"
                            class="page-link"
                            aria-hidden="true"
                        >
                            ...
                        </span>
                        <button
                            v-else
                            class="page-link"
                            type="button"
                            @click="setPage(item.page)"
                        >
                            {{ item.page }}
                        </button>
                    </li>
                    <li
                        class="page-item"
                        :class="{ disabled: !files.next_page_url }"
                    >
                        <button
                            class="page-link"
                            type="button"
                            :disabled="!files.next_page_url"
                            @click="setPage(files.current_page + 1)"
                        >
                            Вперёд
                        </button>
                    </li>
                </ul>
            </nav>
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
import { useAuthIdStore } from "../stores/authId";
import { CButton } from "@coreui/vue";
import { confirmAction, getErrorMessage } from "../utils/uiHelpers";

export default {
    props: [
        "datasend",
        "catchError",
        "showToast",
        "dashboard",
        "server",
        "api",
        "getMenu",
        "setContent",
    ],

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
    computed: {
        paginationItems() {
            if (!this.files?.last_page) {
                return [];
            }

            const current = this.files.current_page;
            const last = this.files.last_page;
            const pages = new Set([1, last]);

            for (
                let page = Math.max(1, current - 1);
                page <= Math.min(last, current + 1);
                page++
            ) {
                pages.add(page);
            }

            if (current <= 3) {
                for (let page = 1; page <= Math.min(last, 4); page++) {
                    pages.add(page);
                }
            }

            if (current >= last - 2) {
                for (let page = Math.max(1, last - 3); page <= last; page++) {
                    pages.add(page);
                }
            }

            const sortedPages = [...pages].sort((left, right) => left - right);
            const items = [];

            sortedPages.forEach((page, index) => {
                if (index > 0 && page - sortedPages[index - 1] > 1) {
                    items.push({
                        type: "ellipsis",
                        key: `ellipsis-${sortedPages[index - 1]}-${page}`,
                    });
                }

                items.push({
                    type: "page",
                    page,
                    key: `page-${page}`,
                });
            });

            return items;
        },
    },
    methods: {
        onChange(event) {
            this.searchFilter.user = event.target.value;
            this.getFiles();
        },
        async clearmyaccessfiles() {
            if (await confirmAction("Вы уверены?")) {
                this.datasend("clearmyaccessfiles", "POST")
                    .then((res) => {
                        if (res.success) {
                            this.getFiles();
                            this.showToast(res.message, "success");
                        }
                    })
                    .catch((error) =>
                        this.showToast(
                            getErrorMessage(error, "Не удалось сбросить доступы"),
                            "danger"
                        )
                    );
            }
        },
        save(id) {
            let form = {
                id: this.files.data[id].id,
                accessibility: this.files.data[id].child.accessibility ? 1 : 0,
                accessibilitymanagers: this.files.data[id].child
                    .accessibilitymanagers
                    ? 1
                    : 0,
                groups: JSON.stringify(this.files.data[id].groups),
            };

            this.datasend("saveresourceadmin", "POST", form)
                .then((res) => {
                    if (res.success) {
                        this.getFiles();
                        this.showToast(res.message, "success");
                    }
                })
                .catch((error) =>
                    this.showToast(
                        getErrorMessage(error, "Не удалось сохранить изменения"),
                        "danger"
                    )
                );
        },
        async remove(id) {
            if (await confirmAction("Вы уверены?")) {
                this.datasend(
                    "resourcedel/" + this.files.data[id].id,
                    "DELETE",
                    {}
                )
                    .then((res) => {
                        if (res.success) {
                            this.getFiles();
                            this.showToast(res.message, "success");
                        }
                    })
                    .catch((error) =>
                        this.showToast(
                            getErrorMessage(error, "Не удалось удалить файл"),
                            "danger"
                        )
                    );
            }
        },
        setPage(page) {
            if (
                !page ||
                page === this.page ||
                page < 1 ||
                (this.files?.last_page && page > this.files.last_page)
            ) {
                return;
            }

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
                    this.page = res.data.files.current_page;

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
                    this.showToast(
                        getErrorMessage(error, "Не удалось загрузить файлы"),
                        "danger"
                    );
                });
        },
    },
};
</script>
