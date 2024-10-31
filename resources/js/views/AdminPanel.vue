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
                        : " файлами"
                }}
            </h4>
            <div class="w-100 row">
                <div class="col-lg-6">
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
                                        cardName = 'createGroup';
                                        groupId = null;
                                        groupName = null;
                                    }
                                "
                                class="text-white m-2"
                                color="success"
                                ><i class="fa fa-plus"></i
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
                                                    cardName = 'editGroup';
                                                    groupId = group.id;
                                                    groupName = group.name;
                                                }
                                            "
                                            class=""
                                            color="primary"
                                            ><i
                                                class="fa fa-pencil-square-o"
                                            ></i
                                        ></CButton>
                                        <CButton
                                            class="text-white"
                                            color="danger"
                                            @click="removeGroups(group.id)"
                                            ><i class="fa fa-times"></i
                                        ></CButton>
                                    </CButtonGroup>
                                    <CButton
                                        class="text-white"
                                        color="success"
                                        @click="
                                            () => {
                                                cardName = 'createUser';
                                                groupIds = group.id;
                                                fullname = null;
                                                login = null;
                                                password = null;
                                                userId = null;
                                            }
                                        "
                                        ><i class="fa fa-user-plus"></i
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
                                                    class="fa fa-pencil-square-o text-info"
                                                    @click="
                                                        () => {
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
                                                    class="fa fa-times text-danger"
                                                    @click="removeUser(user.id)"
                                                ></i>
                                            </div>
                                        </template>
                                    </CListGroupItem>
                                </CListGroup>
                                <span v-else>Пользователи отсутствуют</span>
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
                                            cardName = 'createUser';
                                            fullname = null;
                                            login = null;
                                            password = null;
                                            userId = null;
                                        }
                                    "
                                    ><i class="fa fa-user-plus"></i
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
                                            class="fa fa-pencil-square-o text-info"
                                            @click="
                                                () => {
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
                                            class="fa fa-times text-danger"
                                            @click="removeUser(user.id)"
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
                                            cardName = 'createUser';
                                            fullname = null;
                                            login = null;
                                            password = null;
                                            userId = null;
                                        }
                                    "
                                    ><i class="fa fa-user-plus"></i
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
                                            class="fa fa-pencil-square-o text-info"
                                            @click="
                                                () => {
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
                                            class="fa fa-times text-danger"
                                            @click="removeUser(user.id)"
                                        ></i>
                                    </div>
                                </template>
                            </CListGroupItem>
                        </CListGroup>
                    </template>
                    <template v-if="role == 'system'">
                        <div class="col-lg-12">
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
                <div class="col-lg-6">
                    <CCard v-if="role == 'user' && cardName == 'createGroup'">
                        <CCardHeader class="fw-bold text-center"
                            >Создание группы</CCardHeader
                        >
                        <CCardBody>
                            <CFormInput
                                v-model="groupName"
                                class="mb-2"
                                type="text"
                                label="Название"
                                placeholder="Введите название группы"
                            />
                            <div class="text-center">
                                <CButton @click="createGroup" color="primary"
                                    >Создать</CButton
                                >
                            </div>
                        </CCardBody>
                    </CCard>
                    <CCard v-if="role == 'user' && cardName == 'editGroup'">
                        <CCardHeader class="fw-bold text-center"
                            >Редактирование группы</CCardHeader
                        >
                        <CCardBody>
                            <CFormInput
                                v-model="groupName"
                                class="mb-2"
                                type="text"
                                label="Название"
                                placeholder="Введите название группы"
                            />
                            <div class="text-center">
                                <CButton @click="createGroup" color="primary"
                                    >Сохранить</CButton
                                >
                            </div>
                        </CCardBody>
                    </CCard>
                    <CCard v-if="cardName == 'createUser'">
                        <CCardHeader class="fw-bold text-center"
                            >Пользователя</CCardHeader
                        >
                        <CCardBody>
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
                                    ><i class="fa fa-key"></i
                                ></CButton>
                            </CInputGroup>

                            <div class="text-center">
                                <CButton color="primary" @click="createUser"
                                    >Сохранить</CButton
                                >
                            </div>
                        </CCardBody>
                    </CCard>
                </div>
            </div>

            <AdminFiles
                :datasend="datasend"
                :catchError="catchError"
                :showToast="showToast"
                v-if="role == 'table'"
            />

            <EditFile
                ref="child"
                v-if="role == 'system'"
                class="mt-4"
                :dashboard="dashboard"
                :server="server"
                :api="api"
            />
        </CCardBody>
    </CCard>
</template>

<script>
import EditFile from "./EditFile.vue";
import AdminFiles from "../components/AdminFiles.vue";
import { CCard } from "@coreui/vue";

export default {
    props: [
        "datasend",
        "showToast",
        "catchError",
        "dashboard",
        "server",
        "api",
        "userRole",
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
        };
    },
    mounted() {
        if (this.userRole == "admin") {
            this.getList();
        }
    },
    methods: {
        clearCache() {
            this.datasend("checkImageResource", "GET", {})
                .then((res) => {
                    this.showToast(res.success, res.message);
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
                        this.showToast(res.success, res.message);
                        if (res.success) {
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
        removeUser(id) {
            if (confirm("Вы действительно хотите удалить пользователя?")) {
                this.datasend(`user/${id}`, "DELETE", {})
                    .then((res) => {
                        this.showToast(res.success, res.message);
                        if (res.success) {
                            this.getList();
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
            // console.log();
            this.$refs.child.editor.save().then((outputData) => {
                console.log(outputData);

                // console.log(outputData);
                outputData.blocks.forEach((el) => {
                    if (el.type == "image" || el.type == "attaches") {
                        // console.log( el.data.file.url);
                        // return;
                        if (el.data.file.url[0] == "/") {
                            el.data.file.url = el.data.file.url;
                        } else {
                            let els = el.data.file.url.split(this.server);
                            el.data.file.url = els.length > 0 ? els[1] : els[0];
                        }
                    }

                    if (el.type == "gallery") {
                        el.data.files.forEach((el) => {
                            if (!el.url[0] == "/") {
                                let els = el.url.split(this.server);
                                el.url = els.length > 0 ? els[1] : els[0];
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
                this.datasend("about", "POST", form)
                    .then((res) => {
                        // console.log(res);
                        this.showToast(res.success, res.message);
                        if (res.success) {
                            this.getList();
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
                    // console.log(res);

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
            let form = new FormData();
            form.append("name", this.groupName);
            if (this.groupId) {
                form.append("id", this.groupId);
            }

            this.datasend("group", this.groupId ? "PUT" : "POST", form)
                .then((res) => {
                    this.showToast(res.success, res.message);
                    if (res.success) {
                        this.getList();
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
            let form = new FormData();
            form.append("name", this.fullname);
            form.append("login", this.login);
            form.append("id", this.userId);
            form.append("role", this.role);

            if (this.groupIds) {
                form.append("group_id", this.groupIds);
            }
            if (this.password) {
                form.append("password", this.password);
            }

            this.datasend("user", this.userId ? "PUT" : "POST", form)
                .then((res) => {
                    this.showToast(res.success, res.message);
                    if (res.success) {
                        this.getList();
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
