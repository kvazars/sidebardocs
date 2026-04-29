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
    }

    render() {
        this.wrapper = document.createElement("div");
        this.wrapper.className = "llm-editor-tool";

        const title = document.createElement("div");
        title.className = "llm-editor-tool__title";
        title.textContent = "AI-генерация";

        const hint = document.createElement("div");
        hint.className = "llm-editor-tool__hint";
        hint.textContent =
            "Введите запрос";

        this.textarea = document.createElement("textarea");
        this.textarea.className = "llm-editor-tool__textarea";
        this.textarea.placeholder =
            "Например: составь структуру статьи с заголовками, списком и ссылками. (Лучше для ответа использовать короткую получаемую информацию и запросить несколько раз)";
        this.textarea.value = this.data.prompt || "";
        this.textarea.rows = 4;

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
            footer,
            this.status,
            this.preview
        );

        return this.wrapper;
    }

    save() {
        return {
            prompt: this.textarea?.value || "",
        };
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
