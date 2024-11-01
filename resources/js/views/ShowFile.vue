<template>
    <div>
        <div id="file" class="print-container p-4">
            <h1 class="text-center" v-html="pagetitle"></h1>
            <hr />

            <div v-for="val in fileData" :key="val">
                <p
                    v-if="val.type == 'paragraph'"
                    v-html="val.data.text"
                    class="my-4"
                ></p>

                <div v-if="val.type == 'gallery'" class="my-4 p-2 border">
                    <div v-if="val.data.style == 'slider'">
                        <div
                            :id="'carousel' + val.id"
                            class="carousel slide carousel-dark"
                        >
                            <div class="carousel-indicators">
                                <button
                                    v-for="(url, key) in val.data.files"
                                    :key="key"
                                    type="button"
                                    data-coreui-target
                                    :data-bs-target="'#carousel' + val.id"
                                    :data-bs-slide-to="key"
                                    :class="{ active: key == 0 }"
                                    aria-current="true"
                                ></button>
                            </div>
                            <div class="carousel-inner">
                                <div
                                    class="carousel-item text-center"
                                    v-for="(url, key) in imgs[val.id]"
                                    :key="key"
                                    :class="{ active: key == 0 }"
                                >
                                    <img
                                        :src="url"
                                        class="img-fluid"
                                        style="max-height: 400px !important"
                                        :alt="'slide' + key"
                                    />
                                </div>
                            </div>
                            <button
                                class="carousel-control-prev"
                                type="button"
                                :data-bs-target="'#carousel' + val.id"
                                data-bs-slide="prev"
                            >
                                <span
                                    class="carousel-control-prev-icon"
                                    aria-hidden="true"
                                ></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button
                                class="carousel-control-next"
                                type="button"
                                :data-bs-target="'#carousel' + val.id"
                                data-bs-slide="next"
                            >
                                <span
                                    class="carousel-control-next-icon"
                                    aria-hidden="true"
                                ></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>
                    </div>
                    <div v-else class="row">
                        <div
                            v-for="(url, key) in imgs[val.id]"
                            :key="key"
                            class="pic col-lg-3 col-md-4 col-sm-6 col-12 mb-4"
                        >
                            <div class="img-thumbnail h-100 d-flex">
                                <img
                                    :src="url"
                                    class="img-fluid d-block"
                                    @click="() => showImg(val.id, key)"
                                />
                            </div>
                        </div>
                    </div>

                    <vue-easy-lightbox
                        :visible="visibleRef[val.id]"
                        :imgs="imgs[val.id]"
                        :index="indexRef[val.id]"
                        @hide="onHide(val.id)"
                    ></vue-easy-lightbox>
                    <p
                        v-html="val.data.caption"
                        class="text-center fst-italic"
                    ></p>
                </div>

                <div v-if="val.type == 'code'" class="my-4">
                    <VCodeBlock
                        :code="val.data.code"
                        highlightjs
                        label="Пример кода"
                        :lang="val.data.language"
                        theme="monokai"
                        persistent-copy-button
                    />
                </div>
                <div
                    v-if="val.type == 'image'"
                    class="editorImageBlock text-center my-4"
                    :class="{
                        editorImageBlockStretched: val.data.stretched,
                        editorImageBlockBorder: val.data.withBorder,
                        editorImageBlockBackground: val.data.withBackground,
                    }"
                >
                    <img
                        :src="imgs[val.id]"
                        alt=""
                        class="editorImage text-center"
                    />

                    <p
                        v-html="val.data.caption"
                        class="text-center fst-italic"
                    ></p>
                </div>

                <div v-if="val.type == 'attaches'" class="my-4">
                    <span>
                        <i class="fa fa-file"></i>&nbsp;
                        <a :href="imgs[val.id].url" target="_blank" download>{{
                            imgs[val.id].title
                        }}</a></span
                    >
                </div>

                <div
                    class="headerBlock text-center my-4"
                    v-if="val.type == 'header'"
                >
                    <h2 v-if="val.data.level == 2" v-html="val.data.text"></h2>
                    <h3 v-if="val.data.level == 3" v-html="val.data.text"></h3>
                    <h4 v-if="val.data.level == 4" v-html="val.data.text"></h4>
                </div>

                <div
                    class="tableBlock my-4 table-responsive"
                    v-if="val.type == 'table'"
                >
                    <table
                        class="table table-bordered"
                        style="margin: auto"
                        :class="!val.data.stretched ? 'table-nonfluid' : ''"
                    >
                        <tbody>
                            <tr
                                v-for="(tRow, num) in val.data.content"
                                :key="num"
                            >
                                <!-- eslint-disable -->
                                <th
                                    v-if="num == 0 && val.data.withHeadings"
                                    v-for="tHeader in tRow"
                                    :key="tHeader"
                                >
                                    {{ tHeader }}
                                </th>
                                <!-- eslint-enable -->
                                <td v-else v-for="tText in tRow" :key="tText">
                                    {{ tText }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <ul
                    class="my-4"
                    v-if="val.type == 'list' && val.data.style == 'unordered'"
                >
                    <li
                        v-for="item in val.data.items"
                        :key="item"
                        v-html="item"
                    ></li>
                </ul>
                <ol
                    class="my-4"
                    v-if="val.type == 'list' && val.data.style != 'unordered'"
                >
                    <li v-for="item in val.data.items" :key="item">
                        {{ item }}
                    </li>
                </ol>
                <div class="text-center my-4" v-if="val.type == 'embed'">
                    <iframe
                        :src="val.data.embed"
                        width="100%"
                        height="500px"
                    ></iframe>
                    <p
                        v-html="val.data.caption"
                        class="text-center fst-italic"
                    ></p>
                </div>
                <div v-if="val.type == 'quote'" class="my-4">
                    <figure
                        class="card card-body"
                        :class="{
                            'text-center': val.data.alignment == 'center',
                        }"
                    >
                        <blockquote class="blockquote">
                            <p v-html="val.data.text"></p>
                        </blockquote>
                        <figcaption
                            class="blockquote-footer"
                            v-html="val.data.caption"
                        ></figcaption>
                    </figure>
                </div>
            </div>
        </div>
        <div class="position-fixed squared" v-if="datasend">
            <div class="dropdown">
                <button
                    class="btn btn-primary"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >
                    <i class="fa fa-cog"></i>
                </button>
                <ul class="dropdown-menu">
                    <li v-if="content">
                        <router-link
                            class="dropdown-item"
                            v-if="
                                auths.id == content.tree.user_id ||
                                auths.role == 'admin'
                            "
                            :to="{ name: 'EditFile', params: { id: id } }"
                            >Редактировать
                            <i
                                class="fa fa-pencil-square-o"
                                aria-hidden="true"
                            ></i
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
                            :filename="pagetitle ? pagetitle : 'document'"
                        >
                            <button class="dropdown-item">
                                Экспорт
                                <i
                                    class="fa fa-file-pdf-o"
                                    aria-hidden="true"
                                ></i>
                            </button>
                        </ExportToPdf>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
import jsPDF from "jspdf";
import { ExportToWord, ExportToPdf } from "vue-doc-exporter";
import { useAuthIdStore } from "../stores/authId";

import VueEasyLightbox from "vue-easy-lightbox";

export default {
    components: {
        ExportToWord,
        ExportToPdf,
        VueEasyLightbox,
    },
    props: [
        "id",
        "datasend",
        "showToast",
        "dashboard",
        "server",
        "about",
        "authss",
    ],
    data() {
        return {
            pagetitle: null,
            auths: useAuthIdStore(),
            fileData: [],
            content: null,
            visibleRef: {},
            indexRef: {},
            imgs: {},
        };
    },

    mounted() {
        if (this.datasend && this.id) {
            this.datasend(
                !this.auths.id
                    ? "resource/" + this.id
                    : "resourceauth/" + this.id,
                "GET",
                {}
            )
                .then((res) => {
                    if (!res.content) {
                        this.showToast(res.success, res.message);
                        setTimeout(() => {
                            this.$router.push({
                                name: "NotFound",
                            });
                        }, 2000);
                    } else {
                        this.pagetitle = res.name;
                        this.parseDoc(res.content);
                    }
                })
                .catch();
        } else {
            // console.log(this.dashboard);

            this.parseDoc(this.dashboard);
            this.pagetitle = this.about.name;
        }
    },
    methods: {
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

            let filename = this.pagetitle + ".doc";
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
        showImg(ref, index) {
            this.indexRef[ref] = index;
            this.visibleRef[ref] = true;
        },
        onHide(ref) {
            this.visibleRef[ref] = false;
        },
        parseDoc(res) {
            this.fileData = JSON.parse(res.data);
            this.fileData.forEach((el) => {
                if (el.type == "gallery") {
                    this.imgs[el.id] = [];
                    this.visibleRef[el.id] = false;
                    this.indexRef[el.id] = 0;
                    el.data.files.forEach((els) => {
                        this.imgs[el.id].push(this.server + els.url);
                    });
                }
                if (el.type == "image") {
                    this.imgs[el.id] = this.server + el.data.file.url;
                }
                if (el.type == "attaches") {
                    this.imgs[el.id] = {
                        url: this.server + el.data.file.url,
                        title: el.data.title,
                    };
                }
            });

            this.content = res;
            if (document.querySelector(".sidebar.sidebar-fixed")) {
                document
                    .querySelector(".sidebar.sidebar-fixed")
                    .classList.remove("show");
            }
        },
    },
};
</script>
