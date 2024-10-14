<script>
import EditorJS from '@editorjs/editorjs';
import Header from '@editorjs/header';
import ImageTool from '@editorjs/image';
import List from '@editorjs/list';
import Table from '@editorjs/table';
import Embed from '@editorjs/embed';
import AceCodeEditorJS from "ace-code-editorjs";
import ace, { edit } from "ace-builds"
import "ace-builds/esm-resolver"
import InlineCode from '@editorjs/inline-code';

import modeHTMLWorker from "ace-builds/src-noconflict/worker-html?url";
import modeJSWorker from "ace-builds/src-noconflict/worker-javascript?url";
import modePHPWorker from "ace-builds/src-noconflict/worker-php?url";
ace.config.setModuleUrl("ace/mode/html_worker", modeHTMLWorker);
ace.config.setModuleUrl("ace/mode/javascript_worker", modeJSWorker);
ace.config.setModuleUrl("ace/mode/php_worker", modePHPWorker);

let editor;
export default {

    props: ["save", "dataBlock"],
    mounted() {
        // this.load();
        this.createEditor();
    },
    data() {
        return {
            editorDataSave: [],
        }
    },
    methods: {
        saveData() {
            this.save(this.editorDataSave);
            editor.destroy();
            this.createEditor();
        },
        // load() {
        //     this.dataBlock.forEach(element => {
        //         editor.blocks.insert(element.type, element.data);
        //     });
        // },

        createEditor() {
            editor = new EditorJS({

                holder: 'editorjs',

                tools: {
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
                                "Convert to": "Конвертировать в"
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

                            "stub": {
                                'The block can not be displayed correctly.': 'Блок не может быть отображен'
                            }
                        },
                        blockTunes: {
                            "delete": {
                                "Delete": "Удалить"
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

                onChange: () => {

                    editor.save().then((outputData) => {
                        this.editorDataSave = outputData;
                    }).catch((error) => {
                        console.log('Saving failed: ', error)
                    });
                }
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
    <div id="editorjs"></div>
</template>