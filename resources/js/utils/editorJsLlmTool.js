const MAX_IMAGE_DIMENSION = 1024;
const MAX_DATA_URI_LENGTH = 4 * 1024 * 1024; // 4 МБ на data URL

export default class EditorJsLlmTool {
    static get toolbox() {
        return {
            title: "AI",
            icon: `
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 3L13.88 8.12L19 10L13.88 11.88L12 17L10.12 11.88L5 10L10.12 8.12L12 3Z" fill="currentColor"/>
                    <path d="M18 15L18.94 17.06L21 18L18.94 18.94L18 21L17.06 18.94L15 18L17.06 17.06L18 15Z" fill="currentColor"/>
                    <path d="M6 14L6.7 15.3L8 16L6.7 16.7L6 18L5.3 16.7L4 16L5.3 15.3L6 14Z" fill="currentColor"/>
                </svg>
            `,
        };
    }

    constructor({ data, api, config }) {
        this.data = data || {};
        this.api = api;
        this.config = config || {};
        this.wrapper = null;
        this.textarea = null;
        this.button = null;
        this.status = null;
        this.preview = null;
        this.fileInput = null;
        this.imageWrap = null;
        this.imageDataUrl = this.data.image || null;
    }

    render() {
        this.wrapper = document.createElement("div");
        this.wrapper.className = "llm-editor-tool";

        const title = document.createElement("div");
        title.className = "llm-editor-tool__title";
        title.textContent = "AI-генерация";

        const hint = document.createElement("div");
        hint.className = "llm-editor-tool__hint";
        hint.textContent = "Введите запрос. Можно приложить изображение";

        this.textarea = document.createElement("textarea");
        this.textarea.className = "llm-editor-tool__textarea";
        this.textarea.placeholder =
            "Например: опиши, что на изображении, и оформи это блоком «Оповещение». (Лучше для ответа использовать короткую получаемую информацию и запросить несколько раз)";
        this.textarea.value = this.data.prompt || "";
        this.textarea.rows = 4;

        // Прикрепление изображения
        const attachRow = document.createElement("div");
        attachRow.className = "llm-editor-tool__attach";

        this.fileInput = document.createElement("input");
        this.fileInput.type = "file";
        this.fileInput.accept = "image/*";
        this.fileInput.hidden = true;
        this.fileInput.addEventListener("change", (event) => {
            this.handleFileSelected(event);
            // Сброс, чтобы повторный выбор того же файла тоже вызвал change
            event.target.value = "";
        });

        const attachButton = document.createElement("button");
        attachButton.type = "button";
        attachButton.className =
            "btn btn-outline-secondary btn-sm llm-editor-tool__attach-btn";
        attachButton.innerHTML = `<i class="bi bi-image me-1"></i>Прикрепить изображение`;
        attachButton.addEventListener("click", () => this.fileInput?.click());

        this.imageWrap = document.createElement("div");
        this.imageWrap.className = "llm-editor-tool__image-wrap";
        this.imageWrap.hidden = true;

        const removeButton = document.createElement("button");
        removeButton.type = "button";
        removeButton.className = "btn btn-outline-danger btn-sm";
        removeButton.innerHTML = `<i class="bi bi-x-lg"></i>`;
        removeButton.title = "Убрать изображение";
        removeButton.addEventListener("click", () => this.clearImage());

        this.imageWrap.append(removeButton);
        attachRow.append(attachButton, this.fileInput);

        if (this.imageDataUrl) {
            this.renderImagePreview();
        }

        const footer = document.createElement("div");
        footer.className = "llm-editor-tool__footer";

        this.button = document.createElement("button");
        this.button.type = "button";
        this.button.className = "btn btn-primary btn-sm";
        this.button.textContent = "Сгенерировать";
        this.button.addEventListener("click", () => this.handleGenerate());

        const note = document.createElement("span");
        note.className = "llm-editor-tool__note";
        note.textContent = "Служебный AI-блок не сохраняется в документе.";

        footer.append(this.button, note);

        this.status = document.createElement("div");
        this.status.className = "llm-editor-tool__status";

        this.preview = document.createElement("pre");
        this.preview.className = "llm-editor-tool__preview";
        this.preview.hidden = true;

        this.wrapper.append(
            title,
            hint,
            this.textarea,
            attachRow,
            this.imageWrap,
            footer,
            this.status,
            this.preview
        );

        return this.wrapper;
    }

    save() {
        const saved = {
            prompt: this.textarea?.value || "",
        };

        if (this.imageDataUrl) {
            saved.image = this.imageDataUrl;
        }

        return saved;
    }

    handleFileSelected(event) {
        const file = event.target?.files?.[0];

        if (!file) {
            return;
        }

        if (!file.type.startsWith("image/")) {
            this.setStatus("error", "Можно прикрепить только изображение");
            return;
        }

        this.setStatus("loading", "Подготовка изображения...");

        this.compressImage(file)
            .then((dataUrl) => {
                if (dataUrl.length > MAX_DATA_URI_LENGTH) {
                    throw new Error(
                        "Изображение слишком большое даже после сжатия"
                    );
                }

                this.imageDataUrl = dataUrl;
                this.renderImagePreview();
                this.setStatus("success", "Изображение прикреплено");
            })
            .catch((error) => {
                this.setStatus(
                    "error",
                    error?.message || "Не удалось прочитать изображение"
                );
            });
    }

    // Читает файл и сжимает до разумных размеров (LLM-сервисы ограничивают размер)
    compressImage(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();

            reader.onerror = () =>
                reject(new Error("Не удалось прочитать изображение"));
            reader.onload = () => {
                const img = new Image();

                img.onerror = () =>
                    reject(new Error("Не удалось декодировать изображение"));
                img.onload = () => {
                    try {
                        const scale = Math.min(
                            1,
                            MAX_IMAGE_DIMENSION /
                                Math.max(img.width, img.height)
                        );
                        const width = Math.max(1, Math.round(img.width * scale));
                        const height = Math.max(
                            1,
                            Math.round(img.height * scale)
                        );

                        const canvas = document.createElement("canvas");
                        canvas.width = width;
                        canvas.height = height;

                        const ctx = canvas.getContext("2d");
                        ctx.drawImage(img, 0, 0, width, height);

                        // Сначала пробуем webp (компактнее), при ошибке — jpeg
                        let dataUrl;
                        try {
                            dataUrl = canvas.toDataURL("image/webp", 0.85);
                            if (
                                !dataUrl ||
                                !dataUrl.startsWith("data:image/webp")
                            ) {
                                throw new Error("webp unsupported");
                            }
                        } catch {
                            dataUrl = canvas.toDataURL("image/jpeg", 0.85);
                        }

                        resolve(dataUrl);
                    } catch (error) {
                        reject(error);
                    }
                };

                img.src = reader.result;
            };

            reader.readAsDataURL(file);
        });
    }

    renderImagePreview() {
        if (!this.imageWrap) {
            return;
        }

        this.imageWrap.innerHTML = "";

        const removeButton = document.createElement("button");
        removeButton.type = "button";
        removeButton.className =
            "btn btn-outline-danger btn-sm llm-editor-tool__image-remove";
        removeButton.innerHTML = `<i class="bi bi-x-lg"></i>`;
        removeButton.title = "Убрать изображение";
        removeButton.addEventListener("click", () => this.clearImage());

        const img = document.createElement("img");
        img.src = this.imageDataUrl;
        img.alt = "Прикрепленное изображение";
        img.className = "llm-editor-tool__image";

        this.imageWrap.append(img, removeButton);
        this.imageWrap.hidden = false;
    }

    clearImage() {
        this.imageDataUrl = null;

        if (this.imageWrap) {
            this.imageWrap.innerHTML = "";
            this.imageWrap.hidden = true;
        }

        this.setStatus("", "");
    }

    async handleGenerate() {
        const prompt = this.textarea?.value.trim() || "";

        if (!prompt) {
            this.setStatus("error", "Введите запрос для генерации");
            return;
        }

        if (typeof this.config.onGenerate !== "function") {
            this.setStatus("error", "Генерация недоступна");
            return;
        }

        this.button.disabled = true;
        this.setPreview("");
        this.setStatus("loading", "Генерация ответа...");

        try {
            const blockIndex =
                typeof this.api?.blocks?.getCurrentBlockIndex === "function"
                    ? this.api.blocks.getCurrentBlockIndex()
                    : null;

            await this.config.onGenerate({
                prompt,
                image: this.imageDataUrl,
                blockIndex,
                setPreview: (value) => this.setPreview(value),
                setStatus: (type, message) => this.setStatus(type, message),
            });
        } catch (error) {
            if (!this.status.textContent) {
                this.setStatus("error", error?.message || "Ошибка генерации");
            }
        } finally {
            this.button.disabled = false;
        }
    }

    setPreview(value) {
        const nextValue = (value || "").trim();
        this.preview.textContent = nextValue;
        this.preview.hidden = nextValue === "";
    }

    setStatus(type, message) {
        this.status.className = `llm-editor-tool__status llm-editor-tool__status--${type}`;
        this.status.textContent = message || "";
    }
}
