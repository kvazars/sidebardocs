<template>
    <CBreadcrumb class="my-0">
        <CBreadcrumbItem v-for="item in breadcrumbs" :key="item">
            {{ item }}
        </CBreadcrumbItem>
    </CBreadcrumb>
    <div
        class="dropdown"
        v-if="$route.name == 'ShowFile'"
    >
        <button
            class="btn btn-primary btn-sm"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
        >
            <i class="bi bi-sliders2"></i>
        </button>
        <ul class="dropdown-menu">
            <template v-if="$route.name == 'ShowFile' && content">
                <li>
                    <router-link
                        class="dropdown-item"
                        v-if="
                            auths.id == content.tree.user_id ||
                            auths.role == 'admin'
                        "
                        :to="{
                            name: 'EditFile',
                            params: { id: content.tree_id },
                        }"
                        ><i class="bi bi-pencil-fill" aria-hidden="true"></i>
                        Редактировать
                    </router-link>
                </li>
                <template v-if="content">
                    <li
                        v-if="
                            auths.id == content.tree.user_id ||
                            auths.role == 'admin'
                        "
                    >
                        <hr class="dropdown-divider" />
                    </li>
                </template>

                <li>
                    <button class="dropdown-item" @click="html2doc">
                        <i class="bi bi-file-word" aria-hidden="true"></i>
                        Экспорт
                    </button>
                </li>
            </template>
        </ul>
    </div>
</template>

<script>
import { useAuthIdStore } from "../stores/authId";
import { exportCurrentDocumentToDocx } from "../utils/exportDocumentToDocx";
import { confirmAction } from "../utils/uiHelpers";

import { useRouter } from "vue-router";
export default {
    data() {
        return {
            auths: useAuthIdStore(),
            router: useRouter(),
        };
    },
    props: ["breadcrumbs", "content", "saveEditFile", "datasend", "getMenu"],
    methods: {
        save() {
            this.saveEditFile();
        },
        async redirectToHome(mess = false) {
            if (mess) {
                if (!(await confirmAction("Вы уверены?"))) return;
            }
            this.router.push({ name: "Home" });
        },
        async deleteFile() {
            if (await confirmAction("Вы уверены?")) {
                if (this.$route.name == "CreateFile") {
                    await this.redirectToHome();
                } else {
                    this.datasend(
                        "resourcedel/" + this.content.tree_id,
                        "DELETE",
                        {}
                    )
                        .then((res) => {
                            if (res.success) {
                                this.redirectToHome();
                                this.getMenu();
                            }
                        })
                        .catch(() => null);
                }
            }
        },
        async html2doc() {
            try {
                const filename =
                    this.breadcrumbs[this.breadcrumbs.length - 1] + ".docx";
                await exportCurrentDocumentToDocx(filename);
            } catch (error) {
                console.error("Ошибка экспорта документа в Word:", error);
            }
        },
    },
};
</script>
