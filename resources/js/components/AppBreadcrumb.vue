<script>
import jsPDF from "jspdf";
import { ExportToWord, ExportToPdf } from "vue-doc-exporter";
import { useAuthIdStore } from "../stores/authId";

import { useRouter } from "vue-router";
export default {
    components: {
        ExportToWord,
        ExportToPdf,
    },
    data() {
        return {
            auths: useAuthIdStore(),
            router: useRouter(),
        };
    },
    props: ["breadcrumbs", "content", "saveEditFile", "datasend","getMenu"],
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

<template>
    <CBreadcrumb class="my-0">
        <CBreadcrumbItem v-for="item in breadcrumbs" :key="item">
            {{ item }}
        </CBreadcrumbItem>
    </CBreadcrumb>
    <div
        class="dropdown"
        v-if="
            content &&
            ($route.name == 'CreateFile' ||
                $route.name == 'EditFile' ||
                $route.name == 'ShowFile')
        "
    >
        <button
            class="btn btn-primary btn-sm"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
        >
            <i class="fa fa-cog"></i>
        </button>
        <ul class="dropdown-menu">
            <template
                v-if="$route.name == 'EditFile' || $route.name == 'CreateFile'"
            >
                <li>
                    <button class="dropdown-item" @click="save">
                        Сохранить
                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                    </button>
                </li>
                <li v-if="$route.name == 'EditFile'">
                    <button class="dropdown-item" @click="redirectToHome(true)">
                        Отмена
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </button>
                </li>
                <li v-if="$route.name == 'EditFile'">
                    <hr class="dropdown-divider" />
                </li>
                <li v-if="$route.name == 'EditFile'">
                    <button class="dropdown-item" @click="deleteFile">
                        <span>Удалить</span>
                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                    </button>
                </li>
            </template>
            <template v-if="$route.name == 'ShowFile'">
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
                        >Редактировать
                        <i class="fa fa-pencil-square-o" aria-hidden="true"></i
                    ></router-link>
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
                        Экспорт
                        <i class="fa fa-file-word-o" aria-hidden="true"></i>
                    </button>
                </li>
                <li>
                    <ExportToPdf
                        :filename="
                            this.breadcrumbs
                                ? this.breadcrumbs[this.breadcrumbs.length - 1]
                                : 'document'
                        "
                    >
                        <button class="dropdown-item">
                            Экспорт
                            <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
                        </button>
                    </ExportToPdf>
                </li>
            </template>
        </ul>
    </div>
</template>
