<template>
    <div v-if="user">
        <CNav variant="tabs">
            <CNavItem>
                <CNavLink
                    href="javascript:void(0);"
                    :active="tabPaneActiveKey == 1"
                    @click="
                        () => {
                            tabPaneActiveKey = 1;
                        }
                    "
                >
                    <i class="bi bi-archive"></i> Ресурс
                </CNavLink>
            </CNavItem>
            <CNavItem v-if="!this.$route.params.parent">
                <CNavLink
                    href="javascript:void(0);"
                    :active="tabPaneActiveKey == 2"
                    v-if="this.$route.params.id"
                    @click="
                        () => {
                            tabPaneActiveKey = 2;
                        }
                    "
                    ><i class="bi bi-gear"></i> Управление тестами</CNavLink
                >
            </CNavItem>
        </CNav>
        <CTabContent>
            <CTabPane
                role="tabpanel"
                aria-labelledby="home-tab"
                :visible="tabPaneActiveKey == 1"
            >
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
                                            class="d-flex gap-3 align-items-center"
                                        >
                                            <div>
                                                <CFormSwitch
                                                    v-model="
                                                        accessibilitymanagers
                                                    "
                                                    label="Доступно для всех менеджеров"
                                                    id="accessibilitymanagers_for"
                                                />
                                            </div>
                                            <div>
                                                <CFormSwitch
                                                    v-model="accessibility"
                                                    label="Доступно всем авторизованным"
                                                    id="accessibility_for"
                                                />
                                            </div>
                                        </div>
                                        <div
                                            v-if="!accessibility"
                                            class="row w-100 px-2"
                                        >
                                            <div
                                                class="col-lg-3"
                                                v-for="(
                                                    item, key
                                                ) in groupAvailables"
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
                                                        :value="
                                                            'group_' + gr.id
                                                        "
                                                        :id="'group_' + gr.id"
                                                        v-model="gr.checked"
                                                    />
                                                    <label
                                                        style="
                                                            user-select: none;
                                                        "
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
                    <CCardHeader class="d-flex justify-content-between align-items-center">
                        <span>Содержимое</span>
                        <div class="d-flex gap-2">
                            <button
                                class="btn btn-sm btn-danger text-white"
                                @click="deleteResource"
                                v-if="this.$route.params.id"
                                title="Удалить документ"
                            >
                                <i class="bi bi-trash me-1"></i>Удалить
                            </button>
                            <button
                                class="btn btn-sm btn-secondary text-white"
                                @click="cancel"
                                title="Отменить и вернуться"
                            >
                                <i class="bi bi-x-circle me-1"></i>Отмена
                            </button>
                            <button
                                class="btn btn-sm btn-success text-white"
                                @click="save"
                                title="Сохранить документ"
                            >
                                <i class="bi bi-check-circle me-1"></i>Сохранить
                            </button>
                            <button
                                class="btn btn-sm btn-info text-white"
                                @click="triggerImportDocx"
                                title="Импортировать содержимое из DOCX файла"
                            >
                                <i class="bi bi-file-word me-1"></i>Импорт DOCX
                            </button>
                            <button
                                class="btn btn-sm btn-info text-white"
                                @click="triggerImportPptx"
                                title="Импортировать содержимое из PPTX файла"
                            >
                                <i class="bi bi-file-slides me-1"></i>Импорт PPTX
                            </button>
                            <input
                                ref="docxFileInput"
                                type="file"
                                accept=".docx"
                                style="display: none"
                                @change="handleDocxImport"
                            />
                            <input
                                ref="pptxFileInput"
                                type="file"
                                accept=".pptx"
                                style="display: none"
                                @change="handlePptxImport"
                            />
                        </div>
                    </CCardHeader>
                    <CCardBody class="p-1">
                        <div v-if="importError" class="alert alert-danger mb-2">
                            {{ importError }}
                        </div>
                        <div id="editorjs"></div>
                    </CCardBody>
                </CCard>
            </CTabPane>
            <CTabPane
                role="tabpanel"
                aria-labelledby="profile-tab"
                :visible="tabPaneActiveKey == 2"
                v-if="this.$route.params.id"
            >
                <TestManagement
                    :datasend="datasend"
                    :showToast="showToast"
                    :changeCurrentView="changeCurrentView"
                    v-if="currentView == 'management'"
                />

                <TestCreator
                    v-if="currentView == 'creator'"
                    :editTestId="editTestId"
                    :changeCurrentView="changeCurrentView"
                    :datasend="datasend"
                    :showToast="showToast"
                />
            </CTabPane>
            <CTabPane
                role="tabpanel"
                aria-labelledby="contact-tab"
                :visible="tabPaneActiveKey == 3"
            >
                Результаты
            </CTabPane>
        </CTabContent>
    </div>
</template>
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
import RawTool from "@editorjs/raw";
import Alert from "editorjs-alert";
import { useRouter } from "vue-router";
import { useAuthIdStore } from "../stores/authId";
import TestManagement from "../components/TestManagement.vue";
import TestCreator from "../components/TestCreator.vue";
import { importDocxToEditorJS, validateDocxFile } from "../utils/importFromDocx";
import { importPptxToEditorJS, validatePptxFile } from "../utils/importFromPptx";
export default {
    components: {
        TestManagement,
        TestCreator,
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
                        this.accessibilitymanagers = res.content
                            .accessibilitymanagers
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
                            this.showToast("Что-то пошло не так", res.success);
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
            accessibilitymanagers: false,
            groupAvailables: {},
            editor: null,
            visibleGroups: false,
            tabPaneActiveKey: 1,
            currentView: "management",
            editTestId: null,
            importError: null,
        };
    },
    methods: {
        changeCurrentView(view = "management", id = null) {
            this.currentView = view;
            this.editTestId = id;
        },

        // Открывает диалог выбора файла DOCX
        triggerImportDocx() {
            this.$refs.docxFileInput.click();
        },

        // Открывает диалог выбора файла PPTX
        triggerImportPptx() {
            this.$refs.pptxFileInput.click();
        },

        // Обрабатывает импорт DOCX файла
        async handleDocxImport(event) {
            this.importError = null;
            const file = event.target.files[0];

            if (!file) return;

            try {
                // Валидируем файл
                validateDocxFile(file);

                // Показываем сообщение о загрузке
                this.showToast("Импортирование документа...", "info");

                // Импортируем содержимое из DOCX
                const blocks = await importDocxToEditorJS(file);

                if (!blocks || blocks.length === 0) {
                    throw new Error("Не удалось извлечь содержимое из документа");
                }

                const currentContent = await this.editor.save();
                const currentBlocks = Array.isArray(currentContent.blocks)
                    ? currentContent.blocks
                    : [];
                const importBlocks = blocks.filter(
                    (block) => block && block.type && block.data
                );

                await this.editor.render({
                    time: Date.now(),
                    version: currentContent.version || "2.30.6",
                    blocks: [...currentBlocks, ...importBlocks],
                });

                this.showToast(
                    `Успешно добавлено ${blocks.length} блоков из документа`,
                    "success"
                );

                // Очищаем input для возможности повторного выбора того же файла
                event.target.value = "";
            } catch (error) {
                console.error("Ошибка импорта DOCX:", error);
                this.importError = error.message || "Ошибка при импорте документа";
                this.showToast(this.importError, "danger");
                event.target.value = "";
            }
        },

        // Обрабатывает импорт PPTX файла
        async handlePptxImport(event) {
            this.importError = null;
            const file = event.target.files[0];

            if (!file) return;

            try {
                // Валидируем файл
                validatePptxFile(file);

                // Показываем сообщение о загрузке
                this.showToast("Импортирование презентации...", "info");

                // Функция для загрузки изображения на сервер
                const uploadImage = async (imageBlob) => {
                    // Пропускаем большие файлы (>2MB)
                    if (imageBlob.size > 2000000) {
                        console.warn('Изображение слишком большое, пропускаем:', imageBlob.size, 'байт');
                        return null;
                    }

                    console.log('Загружаю изображение, размер:', imageBlob.size, 'байт, тип:', imageBlob.type);
                    const formData = new FormData();
                    formData.append('image', imageBlob, 'image.jpg');

                    try {
                        const response = await this.datasend('saveImage', 'POST', formData, true);
                        console.log('Ответ сервера:', response);
                        if (response.success) {
                            return response.file.url;
                        } else {
                            throw new Error('Ошибка загрузки изображения: ' + (response.message || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Ошибка загрузки изображения:', error);
                        return null;
                    }
                };

                // Импортируем содержимое из PPTX
                const blocks = await importPptxToEditorJS(file, uploadImage);

                if (!blocks || blocks.length === 0) {
                    throw new Error("Не удалось извлечь содержимое из презентации");
                }

                // Добавляем импортированные блоки в конец редактора
                for (const block of blocks) {
                    if (block && block.type) {
                        try {
                            await this.editor.blocks.insert(
                                block.type,
                                block.data,
                                undefined,
                                false
                            );
                        } catch (error) {
                            console.warn(
                                `Не удалось добавить блок типа ${block.type}:`,
                                error
                            );
                        }
                    }
                }

                this.showToast(
                    `Успешно добавлено ${blocks.length} блоков из презентации`,
                    "success"
                );

                // Очищаем input для возможности повторного выбора того же файла
                event.target.value = "";
            } catch (error) {
                console.error("Ошибка импорта PPTX:", error);
                this.importError = error.message || "Ошибка при импорте презентации";
                this.showToast(this.importError, "danger");
                event.target.value = "";
            }
        },

        // Отмена и возврат назад
        cancel() {
            this.$router.go(-1);
        },

        // Удаление документа
        deleteResource() {
            if (!confirm('Вы уверены, что хотите удалить этот документ?')) {
                return;
            }

            if (!this.$route.params.id) {
                this.showToast("Ошибка: ID документа не найден", "danger");
                return;
            }

            try {
                this.datasend("resource/" + this.$route.params.id, "DELETE", {})
                    .then((res) => {
                        if (res.success) {
                            this.showToast("Документ успешно удален", "success");
                            this.getMenu();
                            this.$router.push({ name: "Home" });
                        } else if (res.errors) {
                            this.catchError(res.errors);
                        } else {
                            this.showToast("Ошибка при удалении документа", "danger");
                        }
                    })
                    .catch((error) => {
                        console.log(error);
                        this.showToast("Ошибка при удалении документа", "danger");
                    });
            } catch (error) {
                console.error("Ошибка удаления:", error);
                this.showToast("Ошибка при удалении документа", "danger");
            }
        },

        save() {
            this.editor
                .save()
                .then((outputData) => {
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
                    let form = {
                        data: JSON.stringify(outputData.blocks),
                        name: this.name ?? "",
                        accessibility: this.accessibility ? 1 : 0,
                        accessibilitymanagers: this.accessibilitymanagers
                            ? 1
                            : 0,
                        availables: JSON.stringify(
                            Object.values(
                                Object.assign(this.groupAvailables)
                            ).flat()
                        ),
                    };

                    if (this.$route.params.parent) {
                        form.tree_id = this.$route.params.parent;
                    } else {
                        form.id = this.$route.params.id;
                    }
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
                    raw: {
                        class: RawTool,
                        config: { placeholder: "Вставьте содержимое" },
                    },
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
