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
        redirectToHome(mess = false) {
            if (mess) {
                if (!confirm("Вы уверены?")) return;
            }
            this.router.push({ name: "Home" });
        },
        deleteFile() {
            if (confirm("Вы уверены?")) {
                if (this.$route.name == "CreateFile") {
                    this.redirectToHome();
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
                        .catch((error) => console.log(error));
                }
            }
        },
        html2doc() {
            let els = document.querySelector("#file").innerHTML;

            let html = `<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
  <head>
  <meta charset='utf-8'>
  <title>Export HTML To Doc</title>
  </head>
  <body>
   ${els}
  </body>
 </html>`;
            var blob = new Blob(["\ufeff", html], {
                type: "application/msword",
            });
            let url =
                "data:application/vnd.ms-word;charset=utf-8," +
                encodeURIComponent(html);

            let filename =
                this.breadcrumbs[this.breadcrumbs.length - 1] + ".doc";
            var downloadLink = document.createElement("a");
            document.body.appendChild(downloadLink);
            if (navigator.msSaveOrOpenBlob) {
                navigator.msSaveOrOpenBlob(blob, filename);
            } else {
                downloadLink.href = url;
                downloadLink.download = filename;
                downloadLink.click();
            }
            document.body.removeChild(downloadLink);
        },
    },
};
</script>
