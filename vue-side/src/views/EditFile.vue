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

import modeHTMLWorker from "ace-builds/src-noconflict/worker-html?url";
import modeJSWorker from "ace-builds/src-noconflict/worker-javascript?url";
import modePHPWorker from "ace-builds/src-noconflict/worker-php?url";
ace.config.setModuleUrl("ace/mode/html_worker", modeHTMLWorker);
ace.config.setModuleUrl("ace/mode/javascript_worker", modeJSWorker);
ace.config.setModuleUrl("ace/mode/php_worker", modePHPWorker);
import { computed } from "vue";
import { useRouter } from "vue-router";

let editor;
export default {
	setup() {
		const postId = computed(() => route.params.id);
		return {
			postId,
		};
	},
	props: [
		"id",
		"parent",
		"datasend",
		"server",
		"getMenu",
		"catchError",
		"showToast",
		"api",
	],
	mounted() {
		if (this.id) {
			this.datasend("resource/" + this.id, "GET", {})
				.then((res) => {
					this.pagetitle = res.name;
					this.dataBlock = JSON.parse(res.content.data);
					this.createEditor();
				})
				.catch((error) => {
					console.log(error);
				});
		} else {
			this.createEditor();
		}
	},
	data() {
		return {
			dataBlock: [],
			pagetitle: "",
			router: useRouter(),
			// server: ''
		};
	},
	methods: {
		deleteFile() {
			this.datasend("resource/" + this.id, "DELETE", {})
				.then((res) => {
					if (res.success) {
						this.getMenu();
						this.showToast(res.success, res.message);
						this.router.push({ name: "Home" });
					}
				})
				.catch((error) => console.log(error));
		},
		save() {
			editor
				.save()
				.then((outputData) => {
					let form = new FormData();
					outputData.blocks.forEach((el) => {
						if (el.type == "image" || el.type == "attaches") {
							el.data.file.url = el.data.file.url.split(
								this.server
							)[1];
						}
						if (el.type == "attaches") {
							el.data.title = el.data.title
								? el.data.title
								: "Скачать файл";
						}
					});
					console.log(outputData.blocks);

					form.append("data", JSON.stringify(outputData.blocks));
					form.append("name", this.pagetitle ?? "");
					if (this.parent) {
						form.append("tree_id", this.parent);
					} else {
						form.append("id", this.id);
					}
					this.datasend("resource", "POST", form)
						.then((res) => {
							if (res.success) {
								this.getMenu();
								// this.showToast(res.success, res.message);
								this.router.push({
									name: "ShowFile",
									params: { id: res.id },
								});
								// setTimeout(() => {
								// 	this.router.push({
								// 		name: "ShowFile",
								// 		params: { id: res.id },
								// 	});
								// }, 2000);
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
			editor = new EditorJS({
				holder: "editorjs",
				tools: {
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
							Image: "Изображение",
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
							// console.log(element);
							element.data.file.url =
								this.server + element.data.file.url;
							// console.log(element.data.file.url);
						}
						editor.blocks.insert(element.type, element.data);
					});
				},
			});
		},
	},
};

const aceConfig = {
	languages: {
		plain: {
			label: "Plain Text",
			mode: "ace/mode/plain_text",
		},
		html: {
			label: "HTML",
			mode: "ace/mode/html",
		},
		js: {
			label: "JavaScript",
			mode: "ace/mode/javascript",
		},
		php: {
			label: "PHP",
			mode: "ace/mode/php",
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
	<div>
		<CCard class="mb-4">
			<CCardHeader>Информация</CCardHeader>
			<CCardBody>
				<CFormInput
					type="text"
					id="exampleFormControlInput1"
					label="Название документа"
					placeholder="Введите название документа"
					v-model="pagetitle"
				/>
			</CCardBody>
		</CCard>
		<CCard>
			<CCardHeader>Содержимое</CCardHeader>
			<CCardBody class="p-1">
				<div id="editorjs"></div>
			</CCardBody>
		</CCard>
		<div class="position-fixed squared">
			<div class="dropdown">
				<button
					class="btn btn-primary border-end-0 rounded-0 rounded-start"
					type="button"
					data-bs-toggle="dropdown"
					aria-expanded="false"
				>
					<i class="fa fa-cog"></i>
				</button>
				<ul class="dropdown-menu">
					<li>
						<button class="dropdown-item" @click="save">
							Сохранить
							<i class="fa fa-floppy-o" aria-hidden="true"></i>
						</button>
					</li>
					<li>
						<hr class="dropdown-divider" />
					</li>
					<li>
						<button class="dropdown-item" @click="deleteFile">
							Удалить
							<i class="fa fa-trash-o" aria-hidden="true"></i>
						</button>
					</li>
				</ul>
			</div>
		</div>
	</div>
</template>
