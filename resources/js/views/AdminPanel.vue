<template>
    <CCard class="my-4">
        <CCardHeader>
            <CNav class="justify-content-center" v-if="userRole == 'admin'">
                <CNavItem>
                    <CNavLink
                        href="#"
                        @click="
                            () => {
                                role = 'user';
                            }
                        "
                        active
                    >
                        Пользователи
                    </CNavLink>
                </CNavItem>

                <CNavItem>
                    <CNavLink
                        href="#"
                        @click="
                            () => {
                                role = 'ceo';
                            }
                        "
                        >Менеджеры</CNavLink
                    >
                </CNavItem>

                <CNavItem>
                    <CNavLink
                        href="#"
                        @click="
                            () => {
                                role = 'admin';
                            }
                        "
                        >Администраторы</CNavLink
                    >
                </CNavItem>

                <CNavItem>
                    <CNavLink
                        href="#"
                        @click="
                            () => {
                                role = 'system';
                            }
                        "
                        >Система</CNavLink
                    >
                </CNavItem>

                <CNavItem>
                    <CNavLink
                        href="#"
                        @click="
                            () => {
                                role = 'table';
                            }
                        "
                        >Файлы</CNavLink
                    >
                </CNavItem>
            </CNav>
            <CNav class="justify-content-center" v-else>
                <CNavItem>
                    <CNavLink
                        href="#"
                        @click="
                            () => {
                                role = 'table';
                            }
                        "
                        >Файлы</CNavLink
                    >
                </CNavItem>
            </CNav>
        </CCardHeader>
        <CCardBody>
            <h4 class="text-center">
                Управление{{
                    role == "user"
                        ? " пользователями"
                        : role == "admin"
                        ? " администраторами"
                        : role == "ceo"
                        ? " менеджерами"
                        : role == "system"
                        ? " системой"
                        : " файлами"
                }}
            </h4>
            <div class="row">
                <div>
                    <p class="fw-bold text-center">
                        {{
                            role == "user"
                                ? "Список пользователей"
                                : role == "admin"
                                ? "Список администраторов"
                                : role == "ceo"
                                ? "Список менеджеров"
                                : ""
                        }}
                    </p>

                    <CAccordion v-if="role == 'user'">
                        <CAccordionItem>
                            <CButton
                                @click="
                                    () => {
                                        visibleModal = true;
                                        cardName = 'createGroup';
                                        groupId = null;
                                        groupName = null;
                                    }
                                "
                                class="text-white m-2"
                                color="success"
                                ><i class="bi bi-plus"></i
                            ></CButton>
                        </CAccordionItem>
                        <CAccordionItem
                            :item-key="index"
                            v-for="(group, index) in groupList"
                            :key="index"
                        >
                            <CAccordionHeader>{{
                                group.name
                            }}</CAccordionHeader>
                            <CAccordionBody>
                                <div
                                    class="d-flex flex-row justify-content-between mb-2"
                                >
                                    <CButtonGroup>
                                        <CButton
                                            @click="
                                                () => {
                                                    visibleModal = true;
                                                    cardName = 'editGroup';
                                                    groupId = group.id;
                                                    groupName = group.name;
                                                }
                                            "
                                            class=""
                                            color="primary"
                                            ><i class="bi bi-pencil-fill"></i
                                        ></CButton>
                                        <CButton
                                            class="text-white"
                                            color="danger"
                                            @click="removeGroups(group.id)"
                                            ><i class="bi bi-x"></i
                                        ></CButton>
                                    </CButtonGroup>
                                    <CButton
                                        class="text-white"
                                        color="success"
                                        @click="
                                            () => {
                                                visibleModal = true;
                                                cardName = 'createUser';
                                                groupIds = group.id;
                                                fullname = null;
                                                login = null;
                                                password = null;
                                                userId = null;
                                            }
                                        "
                                        ><i class="bi bi-person-add"></i
                                    ></CButton>
                                </div>
                                <CListGroup flush v-if="group.users[0]">
                                    <CListGroupItem
                                        class="d-flex flex-row justify-content-between"
                                        v-for="(user, index) in group.users"
                                        :key="index"
                                    >
                                        <template v-if="user.login">
                                            <span
                                                >{{ user.name }} ({{
                                                    user.login
                                                }})</span
                                            >
                                            <div
                                                class="d-flex flex-row align-items-center gap-2"
                                            >
                                                <i
                                                    v-if="!user.deleted_at"
                                                    class="bi bi-pencil-fill text-info"
                                                    @click="
                                                        () => {
                                                            visibleModal = true;
                                                            cardName =
                                                                'createUser';
                                                            fullname =
                                                                user.name;
                                                            password = null;
                                                            login = user.login;
                                                            groupIds = null;
                                                            userId = user.id;
                                                        }
                                                    "
                                                ></i>

                                                <i
                                                    v-if="!user.deleted_at"
                                                    title="Авторизоваться"
                                                    class="bi bi-box-arrow-in-right text-info"
                                                    @click="authUser(user.id)"
                                                ></i>

                                                <i
                                                    :class="{
                                                        'bi bi-x text-danger':
                                                            !user.deleted_at,
                                                        'bi bi-check text-success':
                                                            user.deleted_at,
                                                    }"
                                                    @click="
                                                        removeUser(
                                                            user.id,
                                                            user.deleted_at
                                                        )
                                                    "
                                                ></i>
                                            </div>
                                        </template>
                                    </CListGroupItem>
                                </CListGroup>
                            </CAccordionBody>
                        </CAccordionItem>
                    </CAccordion>
                    <template v-if="role == 'ceo'">
                        <CListGroup flush v-if="ceos">
                            <CListGroupItem>
                                <CButton
                                    class="text-white"
                                    color="success"
                                    @click="
                                        () => {
                                            visibleModal = true;
                                            cardName = 'createUser';
                                            fullname = null;
                                            login = null;
                                            password = null;
                                            userId = null;
                                        }
                                    "
                                    ><i class="bi bi-person-add"></i
                                ></CButton>
                            </CListGroupItem>
                            <CListGroupItem
                                class="d-flex flex-row justify-content-between"
                                v-for="(user, index) in ceos"
                                :key="index"
                            >
                                <template v-if="user.login">
                                    <span
                                        >{{ user.name }} ({{
                                            user.login
                                        }})</span
                                    >
                                    <div
                                        class="d-flex flex-row align-items-center gap-2"
                                    >
                                        <i
                                            v-if="!user.deleted_at"
                                            class="bi bi-pencil-fill text-info"
                                            @click="
                                                () => {
                                                    visibleModal = true;
                                                    cardName = 'createUser';
                                                    fullname = user.name;
                                                    password = null;
                                                    login = user.login;
                                                    groupIds = null;
                                                    userId = user.id;
                                                }
                                            "
                                        ></i>
                                        <i
                                            v-if="!user.deleted_at"
                                            title="Авторизоваться"
                                            class="bi bi-box-arrow-in-right text-info"
                                            @click="authUser(user.id)"
                                        ></i>
                                        <i
                                            :class="{
                                                'bi bi-x text-danger':
                                                    !user.deleted_at,
                                                'bi bi-check text-success':
                                                    user.deleted_at,
                                            }"
                                            @click="
                                                removeUser(
                                                    user.id,
                                                    user.deleted_at
                                                )
                                            "
                                        ></i>
                                    </div>
                                </template>
                            </CListGroupItem>
                        </CListGroup>
                    </template>
                    <template v-if="role == 'admin'">
                        <CListGroup flush v-if="admins">
                            <CListGroupItem>
                                <CButton
                                    class="text-white"
                                    color="success"
                                    @click="
                                        () => {
                                            visibleModal = true;
                                            cardName = 'createUser';
                                            fullname = null;
                                            login = null;
                                            password = null;
                                            userId = null;
                                        }
                                    "
                                    ><i class="bi bi-person-add"></i
                                ></CButton>
                            </CListGroupItem>
                            <CListGroupItem
                                class="d-flex flex-row justify-content-between"
                                v-for="(user, index) in admins"
                                :key="index"
                            >
                                <template v-if="user.login">
                                    <span
                                        >{{ user.name }} ({{
                                            user.login
                                        }})</span
                                    >
                                    <div
                                        class="d-flex flex-row align-items-center gap-2"
                                    >
                                        <i
                                            v-if="!user.deleted_at"
                                            class="bi bi-pencil-fill text-info"
                                            @click="
                                                () => {
                                                    visibleModal = true;
                                                    cardName = 'createUser';
                                                    fullname = user.name;
                                                    password = null;
                                                    login = user.login;
                                                    groupIds = null;
                                                    userId = user.id;
                                                }
                                            "
                                        ></i>
                                        <i
                                            v-if="!user.deleted_at"
                                            title="Авторизоваться"
                                            class="bi bi-box-arrow-in-right text-info"
                                            @click="authUser(user.id)"
                                        ></i>
                                        <i
                                            :class="{
                                                'bi bi-x text-danger':
                                                    !user.deleted_at,
                                                'bi bi-check text-success':
                                                    user.deleted_at,
                                            }"
                                            @click="
                                                removeUser(
                                                    user.id,
                                                    user.deleted_at
                                                )
                                            "
                                        ></i>
                                    </div>
                                </template>
                            </CListGroupItem>
                        </CListGroup>
                    </template>
                    <template v-if="role == 'system'">
                        <div class="col-lg-12 mb-3">
                            <CCard>
                                <CCardHeader class="fw-bold text-center"
                                    >Управление системой</CCardHeader
                                >
                                <CCardBody>
                                    <CFormInput
                                        class="mb-2"
                                        type="text"
                                        label="Название для сайта"
                                        v-model="systemName"
                                        placeholder="Введите название для сайта"
                                    />
                                    <CFormInput
                                        type="file"
                                        id="formFile"
                                        label="Логотип"
                                    />
                                    <img
                                        :src="systemLogo"
                                        style="max-width: 200px"
                                        v-if="systemLogo"
                                        class="my-3 img-fluid img-thumbnail"
                                        alt=""
                                    />

                                    <div class="text-center">
                                        <CButtonGroup
                                            role="group"
                                            aria-label="Basic example"
                                            ><CButton
                                                color="primary"
                                                @click="updateAbout"
                                                >Сохранить</CButton
                                            >
                                            <CButton
                                                class="text-white"
                                                color="danger"
                                                @click="clearCache"
                                                >Очистить кэш</CButton
                                            ></CButtonGroup
                                        >
                                    </div>
                                </CCardBody>
                            </CCard>
                        </div>
                    </template>
                </div>
            </div>

            <AdminFiles
                :datasend="datasend"
                :catchError="catchError"
                :showToast="showToast"
                :dashboard="dashboard"
                :server="server"
                :api="api"
                :getMenu="getMenu"
                :setContent="setContent"
                v-if="role == 'table'"
            />

            <EditFile
                ref="child"
                v-if="role == 'system'"
                class="mt-4"
                :dashboard="dashboard"
                :server="server"
                :api="api"
                :datasend=null
                :getMenu="getMenu"
                :setContent="setContent"
                :showToast="showToast"
                :catchError="catchError"
            />
        </CCardBody>
    </CCard>

    <CModal
        :visible="visibleModal"
        size="lg"
        @close="
            () => {
                visibleModal = false;
            }
        "
    >
        <CModalHeader>
            <CModalTitle
                v-html="
                    cardName == 'createGroup'
                        ? 'Создание группы'
                        : cardName == 'editGroup'
                        ? 'Редактирование группы'
                        : 'Пользователь'
                "
            ></CModalTitle>
        </CModalHeader>
        <CModalBody>
            <div v-if="role == 'user' && cardName == 'createGroup'">
                <CFormInput
                    v-model="groupName"
                    class="mb-2"
                    type="text"
                    label="Название"
                    placeholder="Введите название группы"
                />
            </div>
            <div v-if="role == 'user' && cardName == 'editGroup'">
                <CFormInput
                    v-model="groupName"
                    class="mb-2"
                    type="text"
                    label="Название"
                    placeholder="Введите название группы"
                />
            </div>
            <div v-if="cardName == 'createUser'">
                <CFormInput
                    class="mb-2"
                    type="text"
                    label="Имя"
                    v-model="fullname"
                    placeholder="Введите имя пользователя"
                />
                <CFormInput
                    class="mb-2"
                    type="text"
                    label="Логин"
                    v-model="login"
                    placeholder="Введите логин пользователя"
                />
                <label for="password">Пароль</label>
                <CInputGroup class="mb-2">
                    <CFormInput
                        v-model="password"
                        id="password"
                        placeholder="Введите пароль пользователя, если это необходимо"
                        aria-label="Пароль"
                        aria-describedby="button-addon2"
                    />
                    <CButton
                        type="button"
                        color="secondary"
                        variant="outline"
                        id="button-addon2"
                        @click="
                            () => {
                                password = generatePassword();
                            }
                        "
                        ><i class="bi bi-key"></i
                    ></CButton>
                </CInputGroup>
            </div>
        </CModalBody>
        <CModalFooter>
            <CButtonGroup>
                <CButton
                    color="primary"
                    @click="
                        () => {
                            if (cardName == 'createUser') {
                                createUser();
                            } else if (role == 'user') {
                                createGroup();
                            }
                        }
                    "
                    >Сохранить</CButton
                >
                <CButton
                    color="danger"
                    class="text-white"
                    @click="
                        () => {
                            visibleModal = false;
                        }
                    "
                    >Выйти</CButton
                ></CButtonGroup
            >
        </CModalFooter>
    </CModal>
</template>

<script>
import EditFile from "./EditFile.vue";
import AdminFiles from "../components/AdminFiles.vue";
import { CButtonGroup } from "@coreui/vue";

export default {
    props: [
        "datasend",
        "showToast",
        "catchError",
        "dashboard",
        "server",
        "api",
        "userRole",
        "getMenu",
        "setContent",
    ],

    data() {
        return {
            role: this.userRole == "admin" ? "user" : "table",
            groupList: null,
            groupName: "",
            cardName: null,
            groupId: null,
            groupIds: null,
            fullname: null,
            login: null,
            password: null,
            userId: null,
            admins: {},
            ceos: {},
            systemName: null,
            systemLogo: null,
            visibleModal: false,
        };
    },
    mounted() {
        if (this.userRole == "admin") {
            this.getList();
        }
    },
    methods: {
        authUser(user) {
            if (confirm("Вы действительно хотите авторизоваться?")) {
                let form = { user: user };

                this.datasend(`authUser`, "POST", form)
                    .then((res) => {
                        localStorage.setItem("token", res.token);
                        location.reload();
                    })
                    .catch((error) => {
                        console.log(error);
                    });
            }
        },
        clearCache() {
            this.datasend("checkImageResource", "GET", {})
                .then((res) => {
                    this.showToast(res.message, "success");
                })
                .catch((error) => {
                    console.log(error);
                });
        },
        generatePassword() {
            let length = 6,
                charset =
                    "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789",
                retVal = "";
            for (var i = 0, n = charset.length; i < length; ++i) {
                retVal += charset.charAt(Math.floor(Math.random() * n));
            }
            return retVal;
        },
        removeGroups(id) {
            if (confirm("Вы действительно хотите удалить группу?")) {
                this.datasend(`group/${id}`, "DELETE", {})
                    .then((res) => {
                        this.showToast(res.success, "success");
                        if (res.success) {
                            this.visibleModal = false;
                            this.cardName = null;
                            this.getList();
                        } else if (res.errors) {
                            this.catchError(res.errors);
                        }
                    })
                    .catch((error) => {
                        console.log(error);
                    });
            }
        },
        removeUser(id, del = null) {
            if (
                confirm(
                    `Вы действительно хотите ${
                        del ? "восстановить" : "удалить"
                    } пользователя?`
                )
            ) {
                this.datasend(`user/${id}`, "DELETE", {})
                    .then((res) => {
                        this.showToast(res.success, "success");
                        if (res.success) {
                            this.getList();
                            this.visibleModal = false;
                            this.cardName = null;
                        } else if (res.errors) {
                            this.catchError(res.errors);
                        }
                    })
                    .catch((error) => {
                        console.log(error);
                    });
            }
        },
        updateAbout() {
            this.$refs.child.editor.save().then((outputData) => {
                outputData.blocks.forEach((el) => {
                    if (el.type == "image" || el.type == "attaches") {
                        if (el.data.file.url[0] == "h") {
                            el.data.file.url = el.data.file.url.split(
                                this.server
                            )[1];
                        }
                    }

                    if (el.type == "gallery") {
                        el.data.files.forEach((els) => {
                            if (els.url[0] == "h") {
                                els.url = els.url.split(this.server)[1];
                            }
                        });
                    }

                    if (el.type == "attaches") {
                        el.data.title = el.data.title
                            ? el.data.title
                            : "Скачать файл";
                    }
                });

                let form = new FormData();
                form.append("name", this.systemName);
                form.append("data", JSON.stringify(outputData.blocks));
                if (document.querySelector("#formFile").files[0]) {
                    form.append(
                        "logo",
                        document.querySelector("#formFile").files[0]
                    );
                }
                this.datasend("about", "POST", form, true)
                    .then((res) => {
                        this.showToast(res.message, "success");
                        if (res.success) {
                            this.getList();
                            this.visibleModal = false;
                            this.cardName = null;
                        } else if (res.errors) {
                            this.catchError(res.errors);
                        }
                    })
                    .catch((error) => {
                        console.log(error);
                    });
            });
        },

        getList() {
            this.datasend("group", "GET", {})
                .then((res) => {
                    this.groupList = res.groups;
                    this.admins = res.admin;
                    this.ceos = res.ceo;
                    if (res.system) {
                        this.systemName = res.system.name;
                        this.systemLogo = this.server + "/" + res.system.logo;
                    }
                })
                .catch((error) => {
                    console.log(error);
                });
        },
        createGroup() {
            let form = { name: this.groupName };
            if (this.groupId) {
                form.id = this.groupId;
            }

            this.datasend("group", this.groupId ? "PUT" : "POST", form)
                .then((res) => {
                    this.showToast(res.success, "success");
                    if (res.success) {
                        this.getList();
                        this.visibleModal = false;
                        this.cardName = null;
                    } else if (res.errors) {
                        this.catchError(res.errors);
                    }
                })
                .catch((error) => {
                    console.log(error);
                });
        },
        createUser() {
            let form;
            if (this.userId) {
                form = {
                    id: this.userId,
                    login: this.login,
                    name: this.fullname,
                    role: this.role,
                };
            } else {
                form = {
                    name: this.fullname,
                    login: this.login,
                    role: this.role,
                    group_id: this.groupIds,
                };
            }
            if (this.password) {
                form.password = this.password;
            }
            this.datasend("user", this.userId ? "PUT" : "POST", form)
                .then((res) => {
                    this.showToast(res.success, "success");
                    if (res.success) {
                        this.getList();
                        this.visibleModal = false;
                        this.cardName = null;
                    } else if (res.errors) {
                        this.catchError(res.errors);
                    }
                })
                .catch((error) => {
                    console.log(error);
                });
        },
    },
    components: {
        EditFile,
        AdminFiles,
    },
};
</script>
