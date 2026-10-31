import AttachesTool from "@editorjs/attaches";
import ImageTool from "@editorjs/image";

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
    "zip", "rar", "7z", "tar", "gz",
    "exe", "msi", "apk", "dmg", "iso",
    "mp3", "wav", "ogg", "flac", "mp4", "avi", "mov", "mkv", "webm",
    "psd", "ai", "sketch", "fig", "key",
];

/**
 * Удаляет блок инструмента из редактора (после неудачной загрузки
 * перетащенного файла, чтобы не оставалась пустая форма загрузки).
 *
 * @param {API} api - Editor.js API из инструмента
 * @param {BlockAPI} blockApi - API блока, переданное в конструктор инструмента
 * @param {HTMLElement} [wrapperEl] - DOM-элемент инструмента (запасной способ поиска блока)
 */
function removeToolBlock(api, blockApi, wrapperEl) {
    try {
        if (blockApi && blockApi.id) {
            const index = api.blocks.getBlockIndex(blockApi.id);
            if (typeof index === "number") {
                api.blocks.delete(index);
                return;
            }
        }
    } catch (e) {
        // блок уже удалён или не найден — пробуем запасной способ
    }

    if (!wrapperEl) {
        return;
    }

    try {
        const count = api.blocks.getBlocksCount();
        for (let i = 0; i < count; i++) {
            const block = api.blocks.getBlockByIndex(i);
            if (block && block.holder && block.holder.contains(wrapperEl)) {
                api.blocks.delete(i);
                return;
            }
        }
    } catch (e) {
        // блок не найден — ничего не делаем
    }
}

/**
 * AttachesTool с поддержкой вставки файла перетаскиванием в редактор.
 *
 * Ядро Editor.js маршрутизирует drop-файлы через pasteConfig.files подходящему
 * инструменту и вызывает его onPaste. Оригинальный @editorjs/attaches не объявляет
 * pasteConfig, поэтому перетаскивание не-графических файлов молча игнорировалось:
 * каретка вставала в место сброса, но загрузка не начиналась.
 */
export class DroppableAttachesTool extends AttachesTool {
    constructor(params) {
        super(params);
        this._ownBlockApi = params && params.block;
    }

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
     * Загружает перетащенный файл через настроенный uploader.
     * При любой ошибке удаляет блок, чтобы не оставалась пустая форма загрузки.
     *
     * @param {File} file - перетащенный файл
     */
    async uploadDroppedFile(file) {
        if (
            !this.config.uploader ||
            typeof this.config.uploader.uploadByFile !== "function"
        ) {
            this.removeOwnBlock();
            return;
        }

        this.nodes.wrapper.classList.add(
            this.CSS.wrapperLoading,
            this.CSS.loader
        );

        let response;

        try {
            response = await this.config.uploader.uploadByFile(file);
        } catch (error) {
            // текст ошибки уже показан uploader'ом через toast
            this.removeOwnBlock();
            return;
        }

        if (!response || !response.success) {
            this.removeOwnBlock();
            return;
        }

        this.onUpload(response);
    }

    removeOwnBlock() {
        removeToolBlock(this.api, this._ownBlockApi, this.nodes.wrapper);
    }
}

/**
 * ImageTool, удаляющий блок при ошибке загрузки файла,
 * вставленного перетаскиванием или вставкой из буфера.
 * Загрузка через кнопку выбора файла сохраняет штатное поведение.
 */
export class DroppableImageTool extends ImageTool {
    onPaste(event) {
        if (event.type === "file") {
            this._filePasteInProgress = true;
        }
        super.onPaste(event);
    }

    uploadingFailed(errorText) {
        if (this._filePasteInProgress) {
            this._filePasteInProgress = false;
            // текст ошибки уже показан uploader'ом через toast
            removeToolBlock(this.api, this.block, null);
            return;
        }
        super.uploadingFailed(errorText);
    }
}
