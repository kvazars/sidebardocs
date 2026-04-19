<template>
    <CModal
        :visible="openWindow"
        aria-labelledby="FolderLabel"
        backdrop="static"
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
                        :disabled="isBusy"
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
                        :disabled="isBusy"
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
                color="primary"
                :disabled="isBusy || !login || !password"
                @click="
                    () => {
                        auth();
                    }
                "
                >{{ isBusy ? "Входим..." : "Войти" }}</CButton
            >
        </CModalFooter>
    </CModal>
</template>

<script>
import { useAuthIdStore } from "../stores/authId";
import { getErrorMessage } from "../utils/uiHelpers";

export default {
    props: [
        "openWindow",
        "openWindowFunction",
        "datasend",
        "catchError",
        "getMenu",
        "isAuthLoading",
        "applyUserBootstrap",
    ],
    data() {
        return {
            login: "",
            password: "",
            isSubmitting: false,
            auths: useAuthIdStore(),
        };
    },
    mounted() {},
    computed: {
        isBusy() {
            return this.isSubmitting || this.isAuthLoading;
        },
    },
    methods: {
        async auth() {
            if (this.isBusy || !this.login || !this.password) {
                return;
            }

            this.isSubmitting = true;
            let form = { login: this.login, password: this.password };

            try {
                const res = await this.datasend("auth", "POST", form);

                if (!res.success) {
                    if (res.errors) {
                        this.catchError(res.errors);
                    }
                    return;
                }

                localStorage.setItem("token", res.token);
                this.auths.setToken(res.token);
                this.auths.changeUser(
                    res.user?.id ?? null,
                    res.user?.name ?? null,
                    res.user?.role ?? null,
                    res.token
                );
                this.openWindowFunction(false);
                this.password = "";
                this.login = "";

                this.applyUserBootstrap(res);
                this.$router.push({ name: "Home" });
            } catch (error) {
                this.catchError({
                    auth: [getErrorMessage(error, "Ошибка авторизации")],
                });
            } finally {
                this.isSubmitting = false;
            }
        },
    },
};
</script>
