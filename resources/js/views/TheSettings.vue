<template>
    <CCard class="my-4">
        <CCardHeader>Смена пароля</CCardHeader>
        <CCardBody>
            <CFormInput
                type="password"
                label="Новый пароль"
                placeholder="Новый пароль"
                class="mb-3"
                v-model="password"
            />
            <CFormInput
                type="password"
                label="Подтверждение нового пароля"
                placeholder="Подтверждение нового пароля"
                class="mb-3"
                v-model="password_confirm"
            />
            <div class="text-center">
                <CButton color="primary" @click="pass">Сменить пароль</CButton>
            </div>
        </CCardBody>
    </CCard>
</template>

<script>
import { useRouter } from "vue-router";

export default {
    props: ["datasend", "showToast", "catchError"],
    data() {
        return {
            password: "",
            password_confirm: "",
            router: useRouter(),
        };
    },
    methods: {
        pass() {
            let form = {
                password: this.password,
                password_confirmation: this.password_confirm,
            };

            this.datasend("newPass", "POST", form)
                .then((res) => {
                    if (res.success) {
                        this.showToast(res.success, res.message);
                        setTimeout(() => {
                            this.router.push({
                                name: "Home",
                            });
                        }, 2000);
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
