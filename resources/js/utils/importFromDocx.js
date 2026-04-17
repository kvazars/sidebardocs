// utils/importFromDocx.js

/**
 * Импортирует содержимое из DOCX файла в формат EditorJS
 * @param {File} file - DOCX файл для импорта
 * @returns {Promise<Array>} - Массив блоков в формате EditorJS
 */
export async function importDocxToEditorJS(file) {
    try {
        const mammoth = await import("mammoth");
        const result = await mammoth.convertToHtml({
            arrayBuffer: await file.arrayBuffer(),
        });

        return htmlToEditorJSBlocks(result.value);
    } catch (error) {
        throw error;
    }
}

/**
 * Преобразует HTML в EditorJS блоки
 * @param {string} html - HTML содержимое
 * @returns {Array} - Массив EditorJS блоков
 */
function htmlToEditorJSBlocks(html) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, "text/html");
    const blocks = [];

    pushBlocksFromNodes(Array.from(doc.body.childNodes), blocks);

    return blocks.filter(Boolean);
}

/**
 * Рекурсивно добавляет блоки из списка DOM узлов
 * @param {Array<Node>} nodes
 * @param {Array} target
 */
function pushBlocksFromNodes(nodes, target) {
    for (const node of nodes) {
        const blocks = parseNodeToEditorJSBlocks(node);
        if (!blocks) continue;

        if (Array.isArray(blocks)) {
            for (const block of blocks) {
                if (block) target.push(block);
            }
        } else {
            target.push(blocks);
        }
    }
}

/**
 * Преобразует DOM ноду в один или несколько EditorJS блоков
 * @param {Node} node
 * @returns {Object|Array<Object>|null}
 */
function parseNodeToEditorJSBlocks(node) {
    if (node.nodeType === Node.TEXT_NODE) {
        const text = sanitizeText(node.textContent || "");
        if (!text) return null;

        if (node.parentNode && node.parentNode.nodeType === Node.ELEMENT_NODE) {
            const parentTag = node.parentNode.tagName.toLowerCase();
            if (INLINE_TAGS.has(parentTag)) {
                return null;
            }
        }

        return paragraphBlock(escapeHtml(text));
    }

    if (node.nodeType !== Node.ELEMENT_NODE) return null;

    const tag = node.tagName.toLowerCase();

    switch (tag) {
        case "h1":
        case "h2":
        case "h3":
        case "h4":
        case "h5":
        case "h6":
            return parseHeading(node, tag);

        case "p":
            return parseParagraph(node);

        case "ul":
            return parseList(node, "unordered");

        case "ol":
            return parseList(node, "ordered");

        case "blockquote":
            return parseQuote(node);

        case "table":
            return parseTable(node);

        case "img":
            return parseImageFallback(node);

        case "br":
        case "hr":
            return null;

        case "div":
        case "section":
        case "article":
        case "main":
        case "header":
        case "footer":
        case "aside":
        case "figure":
            return parseContainer(node);

        default:
            if (INLINE_TAGS.has(tag)) {
                const html = sanitizeInlineHtml(node.outerHTML);
                if (!html) return null;
                return paragraphBlock(html);
            }

            if (node.childNodes && node.childNodes.length > 0) {
                return parseContainer(node);
            }

            const fallback = sanitizeInlineHtml(node.innerHTML);
            if (!fallback) return null;
            return paragraphBlock(fallback);
    }
}

function parseHeading(node, tag) {
    const html = sanitizeInlineHtml(node.innerHTML);
    if (!html) return null;

    // ShowFile рендерит только уровни 2..4
    const rawLevel = Number(tag[1]);
    const level = Math.min(4, Math.max(2, rawLevel));

    return {
        type: "header",
        data: {
            text: html,
            level,
        },
    };
}

function parseParagraph(node) {
    const html = sanitizeInlineHtml(node.innerHTML);
    if (!html) return null;
    return paragraphBlock(html);
}

function parseList(node, style) {
    const items = [];
    const directItems = getDirectChildrenByTag(node, "li");

    for (const li of directItems) {
        collectListItems(li, items);
    }

    if (items.length === 0) return null;

    return {
        type: "list",
        data: {
            style,
            items,
        },
    };
}

function collectListItems(liNode, items) {
    const clone = liNode.cloneNode(true);
    const nestedLists = clone.querySelectorAll("ul, ol");
    nestedLists.forEach((list) => list.remove());

    const itemHtml = sanitizeInlineHtml(clone.innerHTML);
    if (itemHtml) {
        items.push(itemHtml);
    }

    const nested = getDirectChildrenByTag(liNode, "ul").concat(
        getDirectChildrenByTag(liNode, "ol")
    );

    for (const nestedList of nested) {
        const nestedItems = getDirectChildrenByTag(nestedList, "li");
        for (const nestedLi of nestedItems) {
            collectListItems(nestedLi, items);
        }
    }
}

function parseQuote(node) {
    const clone = node.cloneNode(true);
    const captionNode = clone.querySelector("figcaption, footer, cite");
    const caption = captionNode ? sanitizeInlineHtml(captionNode.innerHTML) : "";
    if (captionNode) captionNode.remove();

    const text = sanitizeInlineHtml(clone.innerHTML);
    if (!text) return null;

    return {
        type: "quote",
        data: {
            text,
            caption,
            alignment: "left",
        },
    };
}

function parseTable(node) {
    const rows = Array.from(node.querySelectorAll("tr")).map((tr) =>
        Array.from(tr.querySelectorAll("td, th"))
            .map((cell) => sanitizeInlineHtml(cell.innerHTML))
            .filter((cell) => cell !== "")
    );

    const content = rows.filter((row) => row.length > 0);
    if (content.length === 0) return null;

    const firstRow = node.querySelector("tr");
    const withHeadings = !!(firstRow && firstRow.querySelector("th"));

    return {
        type: "table",
        data: {
            content,
            stretched: true,
            withHeadings,
        },
    };
}

function parseImageFallback(node) {
    const alt = sanitizeText(node.getAttribute("alt") || "");
    const src = sanitizeText(node.getAttribute("src") || "");
    const text = alt || (src ? `Изображение: ${escapeHtml(src)}` : "");
    if (!text) return null;
    return paragraphBlock(text);
}

function parseContainer(node) {
    const nestedBlocks = [];
    pushBlocksFromNodes(Array.from(node.childNodes), nestedBlocks);
    return nestedBlocks.length ? nestedBlocks : null;
}

function paragraphBlock(text) {
    return {
        type: "paragraph",
        data: {
            text,
        },
    };
}

function getDirectChildrenByTag(node, tagName) {
    return Array.from(node.children || []).filter(
        (child) => child.tagName && child.tagName.toLowerCase() === tagName
    );
}

function sanitizeText(text) {
    return String(text).replace(/\u00A0/g, " ").replace(/\s+/g, " ").trim();
}

function sanitizeInlineHtml(html) {
    const cleaned = String(html)
        .replace(/\u00A0/g, " ")
        .replace(/(<br\s*\/?>\s*){3,}/gi, "<br><br>")
        .trim();

    if (!cleaned) return "";

    const plain = cleaned.replace(/<[^>]*>/g, "").replace(/\s+/g, " ").trim();
    return plain || /<img|<br/i.test(cleaned) ? cleaned : "";
}

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");
}

const INLINE_TAGS = new Set([
    "a",
    "abbr",
    "b",
    "code",
    "em",
    "i",
    "mark",
    "small",
    "span",
    "strong",
    "sub",
    "sup",
    "u",
]);

/**
 * Валидирует DOCX файл перед импортом
 * @param {File} file - Файл для проверки
 * @returns {boolean} - true если файл валиден
 */
export function validateDocxFile(file) {
    if (!file) {
        throw new Error("Файл не выбран");
    }

    if (
        file.type !==
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document" &&
        !file.name.toLowerCase().endsWith(".docx")
    ) {
        throw new Error(
            "Это не DOCX файл. Пожалуйста, выберите файл с расширением .docx"
        );
    }

    const maxSize = 10 * 1024 * 1024; // 10MB
    if (file.size > maxSize) {
        throw new Error(
            `Размер файла не должен превышать 10 MB. Текущий размер: ${(
                file.size /
                1024 /
                1024
            ).toFixed(2)} MB`
        );
    }

    return true;
}
