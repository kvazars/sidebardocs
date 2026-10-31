import AttachesTool from "@editorjs/attaches";

/**
 * MIME-типы файлов, принимаемых перетаскиванием.
 * Изображения (image/*) сюда намеренно не входят — их вставкой занимается image tool,
 * который объявлен в tools раньше по совпадению не должен перебиваться.
 */
const DROP_MIME_TYPES = [
    "application/*",
    "text/*",
    "audio/*",
    "video/*",
    "font/*",
    "message/*",
    "model/*",
];

/**
 * Расширения на случай, когда браузер отдаёт пустой или generic MIME-тип.
 */
const DROP_EXTENSIONS = [
    "pdf", "doc", "docx", "xls", "xlsx", "ppt", "pptx",
    "odt", "ods", "odp", "rtf", "txt", "md", "csv", "json", "xml",
    "zip", "rar", "7z", "tar", "gz", "webm", "jpg","png",
    "psd"
];

/**
 * AttachesTool с поддержкой вставки файла перетаскиванием в редактор.
 *
 * Ядро Editor.js маршрутизирует drop-файлы через pasteConfig.files подходящему
 * инструменту и вызывает его onPaste. Оригинальный @editorjs/attaches не объявляет
 * pasteConfig, поэтому перетаскивание не-графических файлов молча игнорировалось:
 * каретка вставала в место сброса, но загрузка не начиналась.
 */
export default class DroppableAttachesTool extends AttachesTool {
    static get pasteConfig() {
        return {
            files: {
                mimeTypes: DROP_MIME_TYPES,
                extensions: DROP_EXTENSIONS,
            },
        };
    }

    /**
     * Вызывается ядром Editor.js при вставке/сбросе файла на блок.
     *
     * @param {PasteEvent} event - editor.js custom paste event
     */
    onPaste(event) {
        if (event.type !== "file") {
            return;
        }

        const file = event.detail && event.detail.file;

        if (!file) {
            return;
        }

        this.uploadDroppedFile(file);
    }

    /**
     * Загружает перетащенный файл через настроенный uploader,
     * повторяя поведение штатной кнопки выбора файла.
     *
     * @param {File} file - перетащенный файл
     */
    async uploadDroppedFile(file) {
        if (
            !this.config.uploader ||
            typeof this.config.uploader.uploadByFile !== "function"
        ) {
            return;
        }

        this.nodes.wrapper.classList.add(
            this.CSS.wrapperLoading,
            this.CSS.loader
        );

        try {
            const response = await this.config.uploader.uploadByFile(file);
            this.onUpload(response);
        } catch (error) {
            this.uploadingFailed(this.config.errorMessage);
        }
    }
}
