<template>
    <CModal
        :visible="openWindow"
        @close="
            () => {
                openWindowFunction();
            }
        "
        aria-labelledby="FolderLabel"
    >
        <CModalHeader>
            <CModalTitle id="FolderLabel">Авторизация</CModalTitle>
        </CModalHeader>
        <CModalBody>
            <div class="w-100 d-flex flex-column gap-4">
                <div class="w-100 d-flex flex-column gap-2">
                    <CFormInput
                        v-model="login"
                        name="folderName"
                        type="text"
                        v-on:keyup.enter="
                            () => {
                                auth();
                            }
                        "
                        placeholder="Логин"
                    />
                </div>
                <div class="w-100 d-flex flex-column gap-2">
                    <CFormInput
                        v-model="password"
                        name="folderName"
                        type="password"
                        v-on:keyup.enter="
                            () => {
                                auth();
                            }
                        "
                        placeholder="Пароль"
                    />
                </div>
            </div>
        </CModalBody>
        <CModalFooter>
            <CButton
                color="secondary"
                @click="
                    () => {
                        openWindowFunction();
                    }
                "
            >
                Отмена
            </CButton>
            <CButton
                color="primary"
                @click="
                    () => {
                        auth();
                    }
                "
                >Войти</CButton
            >
        </CModalFooter>
    </CModal>
</template>

<script>
import { useAuthIdStore } from "../stores/authId";

export default {
    props: [
        "openWindow",
        "openWindowFunction",
        "datasend",
        "catchError",
        "getMenu",
    ],
    data() {
        return {
            login: "",
            password: "",
            auths: useAuthIdStore(),
        };
    },
    mounted() {},
    methods: {
        auth() {
            let form = { login: this.login, password: this.password };

            this.datasend("auth", "POST", form)
                .then((res) => {
                    this.password = null;
                    this.login = null;
                    if (res.success) {
                        localStorage.setItem("token", res.token);
                        this.auths.changeUser(
                            res.id,
                            res.fullname,
                            res.role,
                            res.token
                        );
                        this.openWindowFunction();
                        this.getMenu();

                        this.$router.push({ name: "Home" });
                    } else if (res.errors) {
                        this.catchError(res.errors);
                    }
                })
                .catch((error) => {
                    console.log(error);
                });
        },
    },
};
</script>
