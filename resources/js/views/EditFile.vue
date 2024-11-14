<script>
import EditorJS from "@editorjs/editorjs";
import Header from "@editorjs/header";
import ImageTool from "@editorjs/image";
import List from "@editorjs/list";
import Table from "@editorjs/table";
import Embed from "@editorjs/embed";
import Quote from "@editorjs/quote";
import AceCodeEditorJS from "ace-code-editorjs";
import ace, { edit } from "ace-builds";
import "ace-builds/esm-resolver";
import InlineCode from "@editorjs/inline-code";
import AttachesTool from "@editorjs/attaches";
import ImageGallery from "@kiberpro/editorjs-gallery";
import Sortable from "sortablejs";
import modeHTMLWorker from "ace-builds/src-noconflict/worker-html?url";
import modeCSSWorker from "ace-builds/src-noconflict/worker-css?url";
import modeJSWorker from "ace-builds/src-noconflict/worker-javascript?url";
import modePHPWorker from "ace-builds/src-noconflict/worker-php?url";
import LinkWithTarget from "editorjs-link-with-target";
ace.config.setModuleUrl("ace/mode/html_worker", modeHTMLWorker);
ace.config.setModuleUrl("ace/mode/css_worker", modeCSSWorker);
ace.config.setModuleUrl("ace/mode/javascript_worker", modeJSWorker);
ace.config.setModuleUrl("ace/mode/php_worker", modePHPWorker);
import Alert from "editorjs-alert";
import { computed } from "vue";
import { useRouter } from "vue-router";
import { useAuthIdStore } from "../stores/authId";

export default {
    setup() {
        const postId = computed(() => route.params.id);
        return {
            postId,
        };
    },
    props: [
        "datasend",
        "server",
        "getMenu",
        "catchError",
        "dashboard",
        "showToast",
        "api",
        "setContent",
    ],
    mounted() {
        if (this.datasend) {
            if (this.$route.params.id) {
                this.datasend(
                    "resourceauth/" + this.$route.params.id,
                    "GET",
                    {}
                )
                    .then((res) => {
                        if (
                            this.user.role == "user" ||
                            !this.user ||
                            !localStorage.getItem("token")
                        ) {
                            this.$router.push({ name: "NotFound" });
                        }
                        if (
                            this.user.role == "ceo" &&
                            res.content.tree.user_id != this.user.id
                        ) {
                            this.$router.push({ name: "NotFound" });
                        }

                        this.name = res.name;

                        this.accessibility = res.content.accessibility
                            ? true
                            : false;

                        res.groups.forEach((el) => {
                            let firstletter = el.name.substr(0, 1);
                            if (!this.groupAvailables[firstletter]) {
                                this.groupAvailables[firstletter] = [];
                            }
                            this.groupAvailables[firstletter].push(el);
                        });

                        this.dataBlock = JSON.parse(res.content.data);
                        this.setContent(res.content);
                        this.createEditor();
                    })
                    .catch((error) => {
                        console.log(error);
                    });
            } else {
                this.datasend("getGroups", "GET", {})
                    .then((res) => {
                        if (res.success) {
                            res.groups.forEach((el) => {
                                let firstletter = el.name.substr(0, 1);
                                if (!this.groupAvailables[firstletter]) {
                                    this.groupAvailables[firstletter] = [];
                                }
                                this.groupAvailables[firstletter].push(el);
                            });
                        } else {
                            this.showToast(res.success, "Что-то пошло не так");
                        }
                    })
                    .catch((error) => {
                        console.log(error);
                    });
                this.createEditor();
            }
        } else {
            this.dataBlock = JSON.parse(this.dashboard.data);
            this.createEditor();
        }
    },

    data() {
        return {
            dataBlock: [],
            name: null,
            router: useRouter(),
            user: useAuthIdStore(),
            accessibility: false,
            groupAvailables: {},
            editor: null,
        };
    },
    methods: {
        save() {
            this.editor
                .save()
                .then((outputData) => {
                    let form = new FormData();
                    outputData.blocks.forEach((el) => {
                        if (el.type == "image" || el.type == "attaches") {
                            el.data.file.url = el.data.file.url.split(
                                this.server
                            )[1];
                        }

                        if (el.type == "gallery") {
                            el.data.files.forEach((el) => {
                                el.url = el.url.split(this.server)[1];
                            });
                        }

                        if (el.type == "attaches") {
                            el.data.title = el.data.title
                                ? el.data.title
                                : "Скачать файл";
                        }
                    });

                    form.append("data", JSON.stringify(outputData.blocks));
                    form.append("name", this.name ?? "");
                    form.append("accessibility", this.accessibility ? 1 : 0);

                    form.append(
                        "availables",
                        JSON.stringify(
                            Object.values(
                                Object.assign(this.groupAvailables)
                            ).flat()
                        )
                    );

                    if (this.$route.params.parent) {
                        form.append("tree_id", this.$route.params.parent);
                    } else {
                        form.append("id", this.$route.params.id);
                    }
                    // console.log(this.name);
                    this.datasend("resource", "POST", form)
                        .then((res) => {
                            if (res.success) {
                                this.getMenu();
                                this.router.push({
                                    name: "ShowFile",
                                    params: { id: res.id },
                                });
                            } else if (res.errors) {
                                this.catchError(res.errors);
                            }
                        })
                        .catch((error) => {
                            console.log(error);
                        });
                })
                .catch((error) => {
                    console.log("Saving failed: ", error);
                });
        },

        createEditor() {
            this.editor = new EditorJS({
                holder: "editorjs",
                tools: {
                    alert: {
                        class: Alert,
                        inlineToolbar: true,
                        config: {
                            alertTypes: [
                                "primary",
                                "secondary",
                                "info",
                                "success",
                                "warning",
                                "danger",
                                "light",
                                "dark",
                            ],
                            defaultType: "primary",
                            messagePlaceholder: "Добавьте текст",
                        },
                    },
                    link: {
                        class: LinkWithTarget,
                    },
                    gallery: {
                        class: ImageGallery,

                        config: {
                            sortableJs: Sortable,
                            additionalRequestHeaders: {
                                Authorization: `Bearer ${localStorage.getItem(
                                    "token"
                                )}`,
                            },
                            endpoints: {
                                byFile: this.api + "saveImage",
                            },
                        },
                    },

                    quote: {
                        class: Quote,
                        inlineToolbar: true,
                        config: {
                            quotePlaceholder: "Цитата",
                            captionPlaceholder: "Подпись к цитате",
                        },
                    },
                    attaches: {
                        class: AttachesTool,
                        config: {
                            additionalRequestHeaders: {
                                Authorization: `Bearer ${localStorage.getItem(
                                    "token"
                                )}`,
                            },
                            endpoint: this.api + "saveFile",
                            buttonText: "Добавить файл",
                            errorMessage: "Ошибка загрузки файла",
                        },
                    },
                    header: {
                        class: Header,
                        config: {
                            placeholder: "Введите заголовок",
                            levels: [2, 3, 4],
                            defaultLevel: 2,
                        },
                    },
                    inlineCode: {
                        class: InlineCode,
                    },

                    image: {
                        class: ImageTool,
                        config: {
                            additionalRequestHeaders: {
                                Authorization: `Bearer ${localStorage.getItem(
                                    "token"
                                )}`,
                            },
                            endpoints: {
                                byFile: this.api + "saveImage",
                                byUrl: this.api + "saveImageByUrl",
                            },
                        },
                    },
                    embed: {
                        class: Embed,
                        config: {
                            services: {
                                youtube: true,
                                coub: true,
                                vk: {
                                    regex: /(?:http(?:s?):\/\/)vk\.com\/video-(.([\d]+)_(\d+))/,
                                    embedUrl:
                                        "https://vk.com/video_ext.php?oid=-<%= remote_id %>",
                                    html: '<iframe style="width:100%;" height="320" allow="encrypted-media; fullscreen; picture-in-picture; screen-wake-lock;" frameborder="0" allowfullscreen></iframe>',
                                    height: 320,
                                    width: 580,
                                    id: (n) => n.join("&id="),
                                },
                            },
                        },
                    },
                    table: {
                        class: Table,
                        inlineToolbar: true,
                    },
                    list: {
                        class: List,
                        inlineToolbar: true,
                        config: {
                            defaultStyle: "unordered",
                        },
                    },
                    code: {
                        class: AceCodeEditorJS,
                        config: aceConfig,
                    },
                },
                i18n: {
                    messages: {
                        ui: {
                            blockTunes: {
                                toggler: {
                                    "Click to tune": "Нажмите, чтобы настроить",
                                    "or drag to move": "или перетащите",
                                },
                            },
                            inlineToolbar: {
                                converter: {
                                    "Convert to": "Конвертировать в",
                                    Filter: "Фильтр",
                                },
                            },
                            toolbar: {
                                toolbox: {
                                    Add: "Добавить",
                                    Filter: "Фильтр",
                                },
                            },
                            popover: {
                                Filter: "Фильтр",
                                "Nothing found": "Ничего не найдено",
                                "Convert to": "Конвертировать в",
                            },
                        },
                        toolNames: {
                            Text: "Параграф",
                            Heading: "Заголовок",
                            Alert: "Оповещение",
                            Image: "Изображение",
                            Gallery: "Галерея",
                            "Ace Code": "Код (JS, PHP, HTML, CSS)",
                            List: "Список",
                            Warning: "Примечание",
                            Checklist: "Чеклист",
                            Quote: "Цитата",
                            Code: "Код",
                            Delimiter: "Разделитель",
                            "Raw HTML": "HTML-фрагмент",
                            Table: "Таблица",
                            Link: "Ссылка",
                            Marker: "Маркер",
                            Bold: "Полужирный",
                            Attachment: "Файл",
                            Italic: "Курсив",
                            InlineCode: "Моноширинный",
                        },
                        tools: {
                            alert: {
                                Primary: " ",
                                Secondary: " ",
                                Success: " ",
                                Danger: " ",
                                Warning: " ",
                                Info: " ",
                                Light: " ",
                                Dark: " ",
                                Left: "Слева",
                                Center: "По центру",
                                Right: "Справа",
                            },
                            embed: {
                                "Enter a caption": "Описание...",
                            },
                            gallery: {
                                "Select an Image": "Выберите изображение",
                                Delete: "Удалить",
                                "Gallery caption": "Подпись",
                                Slider: "Слайдер",
                                Fit: "Галерея",
                            },
                            list: {
                                Ordered: "Нумерованный",
                                Unordered: "Маркированный",
                            },
                            image: {
                                "With border": "Граница",
                                "Stretch image": "Растянуть",
                                "Select an Image": "Выбрать изображение",
                                "With background": "Добавить фон",
                                Caption: "Подпись",
                            },
                            header: {
                                "Heading 2": "Заголовок 2",
                                "Heading 3": "Заголовок 3",
                                "Heading 4": "Заголовок 4",
                            },
                            table: {
                                "Add column to left": "Добавить столбец слева",
                                "Add column to right":
                                    "Добавить столбец справа",
                                "Delete column": "Удалить столбец",
                                "Add row above": "Добавить строку выше",
                                "Add row below": "Добавить строку ниже",
                                "Delete row": "Удалить строку",
                                Stretch: "Растянуть",
                                "With headings": "С заголовками",
                                "Without headings": "Без заголовков",
                                Heading: "Заголовок",
                                Collapse: "Сжать",
                            },
                            warning: {
                                Title: "Название",
                                Message: "Сообщение",
                            },

                            link: {
                                "Add a link": "Вставьте ссылку",
                                "Open in new window": "В новом окне",
                                Save: "Сохранить",
                                "Add a link": "Вставить ссылку",
                            },
                            quote: {
                                "Align Left": "По левому краю",
                                "Align Center": "По центру",
                            },
                            attaches: {
                                "File title": "Название файла",
                            },

                            stub: {
                                "The block can not be displayed correctly.":
                                    "Блок не может быть отображен",
                            },
                        },
                        blockTunes: {
                            delete: {
                                Delete: "Удалить",
                                "Click to delete": "Уверены?",
                            },

                            header: {
                                title: "Заголовок",
                                Heading: "Заголовок",
                            },
                            moveDown: {
                                "Move down": "Ниже",
                            },

                            moveUp: {
                                "Move up": "Выше",
                            },
                        },
                    },
                },

                onReady: () => {
                    this.dataBlock.forEach((element) => {
                        if (
                            element.type == "image" ||
                            element.type == "attaches"
                        ) {
                            element.data.file.url =
                                this.server + element.data.file.url;
                        }
                        if (element.type == "gallery") {
                            element.data.files.forEach((el) => {
                                el.url = this.server + el.url;
                            });
                        }
                        this.editor.blocks.insert(element.type, element.data);
                    });
                },
            });
        },
    },
};

const aceConfig = {
    languages: {
        plain: {
            label: "Простой текст",
            mode: "ace/mode/plain_text",
        },
        html: {
            label: "HTML",
            mode: "ace/mode/html",
        },
        css: {
            label: "CSS",
            mode: "ace/mode/css",
        },
        js: {
            label: "JavaScript",
            mode: "ace/mode/javascript",
        },
        php: {
            label: "PHP",
            mode: "ace/mode/php",
        },
        sql: {
            label: "SQL",
            mode: "ace/mode/sql",
        },
    },
    options: {
        fontSize: 16,
        minLines: 4,
        theme: "ace/theme/monokai",
    },
};
</script>

<template>
    <div v-if="user">
        <CCard v-if="datasend" class="my-4">
            <CCardHeader>Информация</CCardHeader>
            <CCardBody>
                <CFormInput
                    type="text"
                    id="exampleFormControlInput1"
                    label="Название документа"
                    placeholder="Введите название документа"
                    v-model="name"
                />
                <CAccordion class="mt-4">
                    <CAccordionItem :item-key="1">
                        <CAccordionHeader>
                            Доступность документа
                        </CAccordionHeader>
                        <CAccordionBody>
                            <div class="w-100 d-flex flex-column gap-3">
                                <div
                                    class="w-100 d-flex justify-content-center"
                                >
                                    <CFormSwitch
                                        v-model="accessibility"
                                        label="Доступно всем"
                                        id="accessibility_for"
                                    />
                                </div>
                                <div
                                    v-if="!accessibility"
                                    class="row w-100 px-2"
                                >
                                    {{ groupAvailables }}
                                    <div
                                        class="col-lg-3"
                                        v-for="(item, key) in groupAvailables"
                                        :key="key"
                                    >
                                        <div
                                            class="d-flex form-check gap-2"
                                            v-for="(gr, index) in item"
                                            :key="index"
                                        >
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                :value="'group_' + gr.id"
                                                :id="'group_' + gr.id"
                                                v-model="gr.checked"
                                            />
                                            <label
                                                style="user-select: none"
                                                class="form-check-label"
                                                :for="'group_' + gr.id"
                                            >
                                                {{ gr.name }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CAccordionBody>
                    </CAccordionItem>
                </CAccordion>
            </CCardBody>
        </CCard>
        <CCard>
            <CCardHeader>Содержимое</CCardHeader>
            <CCardBody class="p-1">
                <div id="editorjs"></div>
            </CCardBody>
        </CCard>
    </div>
</template>
