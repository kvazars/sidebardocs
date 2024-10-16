<script>
import EditorJS from '@editorjs/editorjs';
import Header from '@editorjs/header';
import ImageTool from '@editorjs/image';
import List from '@editorjs/list';
import Table from '@editorjs/table';
import Embed from '@editorjs/embed';
import Quote from '@editorjs/quote';
import AceCodeEditorJS from "ace-code-editorjs";
import ace, { edit } from "ace-builds"
import "ace-builds/esm-resolver"
import InlineCode from '@editorjs/inline-code';
import AttachesTool from '@editorjs/attaches';

import modeHTMLWorker from "ace-builds/src-noconflict/worker-html?url";
import modeJSWorker from "ace-builds/src-noconflict/worker-javascript?url";
import modePHPWorker from "ace-builds/src-noconflict/worker-php?url";
ace.config.setModuleUrl("ace/mode/html_worker", modeHTMLWorker);
ace.config.setModuleUrl("ace/mode/javascript_worker", modeJSWorker);
ace.config.setModuleUrl("ace/mode/php_worker", modePHPWorker);
import { computed } from 'vue';
import { useRoute } from 'vue-router';

let editor;
export default {
    setup() {
        const route = useRoute();
        const postId = computed(() => route.params.id);
        return {
            postId,
        };
    },
    props: ["id", "parent"],
    mounted() {
        this.createEditor();
        if (this.id == 1) {
            this.dataBlock = [{
                "id": "uqvFlzwn4H",
                "type": "paragraph",
                "data": {
                    "text": "ewf"
                }
            },
            {
                "id": "zRhbMyXyfh",
                "type": "paragraph",
                "data": {
                    "text": "wefwef"
                }
            },
            {
                "id": "8oPZtnSaDA",
                "type": "paragraph",
                "data": {
                    "text": "wefwefwe"
                }
            }];



        }

        if (this.id == 2) {
            this.dataBlock = [{
                "id": "_dPTGC-2dC",
                "type": "image",
                "data": {
                    "caption": "",
                    "withBorder": false,
                    "withBackground": false,
                    "stretched": false,
                    "file": {
                        "url": "http://localhost:8000/public/contentImages/VmbQj6R78KqFUEnN5yvqOj4j7tE4JUgW8jfO2Wtl.jpg"
                    }
                }
            },
            {
                "id": "ruK35tRzGR",
                "type": "list",
                "data": {
                    "style": "unordered",
                    "items": [
                        "wqd",
                        "qwd"
                    ]
                }
            },
            {
                "id": "RXIyHESczT",
                "type": "paragraph",
                "data": {
                    "text": "qwd"
                }
            }];
        }

    },
    data() {
        return {
            dataBlock: [],
            pagetitle: null,
        }
    },
    methods: {
        save() {
            editor.save().then((outputData) => {
                console.log('Article data: ', outputData)
            }).catch((error) => {
                console.log('Saving failed: ', error)
            });
        },

        createEditor() {
            editor = new EditorJS({

                holder: 'editorjs',

                tools: {
                    quote: {
                        class: Quote,
                        inlineToolbar: true,
                        config: {
                            quotePlaceholder: 'Цитата',
                            captionPlaceholder: 'Подпись к цитате',
                        },
                    },
                    attaches: {
                        class: AttachesTool,
                        config: {
                            endpoint: 'http://localhost:8000/api/saveFile',
                            buttonText: 'Добавить файл',
                            errorMessage: 'Ошибка загрузки файла',
                        }
                    },
                    header: {
                        class: Header,
                        config: {
                            placeholder: 'Введите заголовок',
                            levels: [2, 3, 4],
                            defaultLevel: 2
                        }
                    },
                    inlineCode: {
                        class: InlineCode,
                    },

                    image: {
                        class: ImageTool,
                        config: {
                            endpoints: {
                                byFile: 'http://localhost:8000/api/saveImage',
                                byUrl: 'http://localhost:8000/api/saveImageByUrl',
                            }
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
                                    embedUrl: "https://vk.com/video_ext.php?oid=-<%= remote_id %>",
                                    html: '<iframe style="width:100%;" height="320" allow="encrypted-media; fullscreen; picture-in-picture; screen-wake-lock;" frameborder="0" allowfullscreen></iframe>',
                                    height: 320,
                                    width: 580,
                                    id: (n) => n.join("&id=")
                                },
                            }
                        }
                    },
                    table: {
                        class: Table,
                        inlineToolbar: true,
                    },
                    list: {
                        class: List,
                        inlineToolbar: true,
                        config: {
                            defaultStyle: 'unordered'
                        }
                    },
                    code: {
                        class: AceCodeEditorJS,
                        config: aceConfig,
                    },
                },
                i18n: {
                    messages: {

                        ui: {
                            "blockTunes": {
                                "toggler": {
                                    "Click to tune": "Нажмите, чтобы настроить",
                                    "or drag to move": "или перетащите"
                                },
                            },
                            "inlineToolbar": {
                                "converter": {
                                    "Convert to": "Конвертировать в",
                                    "Filter": "Фильтр",

                                },


                            },
                            "toolbar": {
                                "toolbox": {
                                    "Add": "Добавить",
                                    "Filter": "Фильтр",
                                }
                            },
                            "popover": {
                                "Filter": "Фильтр",
                                "Nothing found": "Ничего не найдено",
                                "Convert to": "Конвертировать в",
                            }
                        },
                        toolNames: {
                            "Text": "Параграф",
                            "Heading": "Заголовок",
                            "Image": "Изображение",
                            "Ace Code": "Код (JS, PHP, HTML, CSS)",
                            "List": "Список",
                            "Warning": "Примечание",
                            "Checklist": "Чеклист",
                            "Quote": "Цитата",
                            "Code": "Код",
                            "Delimiter": "Разделитель",
                            "Raw HTML": "HTML-фрагмент",
                            "Table": "Таблица",
                            "Link": "Ссылка",
                            "Marker": "Маркер",
                            "Bold": "Полужирный",
                            "Attachment": "Файл",
                            "Italic": "Курсив",
                            "InlineCode": "Моноширинный",
                        },
                        tools: {
                            "list": {
                                "Ordered": "Нумерованный",
                                "Unordered": "Маркированный"
                            },
                            "image": {
                                "With border": "Граница",
                                "Stretch image": "Растянуть",
                                "Select an Image": "Выбрать изображение",
                                "With background": "Добавить фон",
                                "Caption": "Подпись",
                            },
                            "header": {
                                "Heading 2": "Заголовок 2",
                                "Heading 3": "Заголовок 3",
                                "Heading 4": "Заголовок 4",
                            },
                            "table": {
                                "Add column to left": "Добавить столбец слева",
                                "Add column to right": "Добавить столбец справа",
                                "Delete column": "Удалить столбец",
                                "Add row above": "Добавить строку выше",
                                "Add row below": "Добавить строку ниже",
                                "Delete row": "Удалить строку",
                                "Stretch": "Растянуть",
                                "With headings": "С заголовками",
                                "Without headings": "Без заголовков",
                                "Heading": "Заголовок",
                                "Collapse": "Сжать",
                            },
                            "warning": {
                                "Title": "Название",
                                "Message": "Сообщение",
                            },


                            "link": {
                                "Add a link": "Вставьте ссылку"
                            },
                            "quote": {
                                "Align Left": "По левому краю",
                                "Align Center": "По центру",
                            },


                            "stub": {
                                'The block can not be displayed correctly.': 'Блок не может быть отображен'
                            }
                        },
                        blockTunes: {
                            "delete": {
                                "Delete": "Удалить",
                                "Click to delete": "Уверены?"
                            },

                            "header": {
                                "title": "Заголовок",
                                "Heading": "Заголовок"
                            },
                            "moveDown": {
                                "Move down": "Ниже"
                            },

                            "moveUp": {
                                "Move up": "Выше"
                            }
                        },
                    }
                },

                onReady: () => {
                    this.dataBlock.forEach(element => {
                        editor.blocks.insert(element.type, element.data);
                    });
                },


            });
        },
    },

}


const aceConfig = {
    languages: {
        plain: {
            label: "Plain Text",
            mode: "ace/mode/plain_text"
        },
        html: {
            label: "HTML",
            mode: "ace/mode/html"
        },
        js: {
            label: "JavaScript",
            mode: "ace/mode/javascript"
        },
        php: {
            label: "PHP",
            mode: "ace/mode/php"
        },
    },
    options: {
        fontSize: 16,
        minLines: 4,
        theme: "ace/theme/monokai"
    }


}


</script>

<template>
    <div>
        parent: {{ parent }}
        id: {{ id }}
        <CCard class="mb-4">
            <CCardHeader>Информация</CCardHeader>
            <CCardBody>
                <CFormInput type="text" id="exampleFormControlInput1" label="Название документа"
                    placeholder="Введите название документа" v-model="pagetitle" />

            </CCardBody>
        </CCard>

        <CCard>
            <CCardHeader>Содержимое</CCardHeader>
            <CCardBody>
                <div id="editorjs"></div>

            </CCardBody>
        </CCard>
        <div class="position-fixed squared">
            <div class="dropdown">
                <button class="btn btn-primary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa fa-cog"></i>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <button class="dropdown-item" @click="save">Сохранить <i class="fa fa-floppy-o"
                                aria-hidden="true"></i></button>
                    </li>

                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li>
                        <a class="dropdown-item" href="#">Удалить <i class="fa fa-trash-o" aria-hidden="true"></i></a>
                    </li>
                </ul>
            </div>
        </div>

    </div>

</template>