const INLINE_TAGS = new Set([
    "A",
    "B",
    "BR",
    "CODE",
    "EM",
    "I",
    "MARK",
    "S",
    "STRONG",
    "SUB",
    "SUP",
    "U",
]);

const CONTAINER_TAGS = new Set(["ARTICLE", "DIV", "MAIN", "SECTION"]);
const DANGEROUS_TAGS = new Set(["IFRAME", "OBJECT", "SCRIPT", "STYLE"]);

export function convertLlmOutputToEditorBlocks(rawContent) {
    const prepared = unwrapCodeFence((rawContent || "").trim());

    if (!prepared) {
        return [];
    }

    if (looksLikeHtml(prepared)) {
        const htmlBlocks = convertHtmlToEditorBlocks(prepared);
        if (htmlBlocks.length > 0) {
            return htmlBlocks;
        }
    }

    return convertMarkdownLikeTextToBlocks(prepared);
}

function unwrapCodeFence(content) {
    const fencedMatch = content.match(/^```(?:html|markdown)?\s*([\s\S]*?)\s*```$/i);
    return fencedMatch ? fencedMatch[1].trim() : content;
}

function looksLikeHtml(content) {
    return /<\/?[a-z][\s\S]*>/i.test(content);
}

function convertHtmlToEditorBlocks(html) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(`<div>${html}</div>`, "text/html");
    const root = doc.body.firstElementChild || doc.body;

    return nodesToBlocks(Array.from(root.childNodes));
}

function nodesToBlocks(nodes) {
    const blocks = [];

    for (const node of nodes) {
        blocks.push(...nodeToBlocks(node));
    }

    return blocks.filter(Boolean);
}

function nodeToBlocks(node) {
    if (node.nodeType === Node.TEXT_NODE) {
        const text = normalizeLooseText(node.textContent || "");
        return text ? [createParagraphBlock(text)] : [];
    }

    if (node.nodeType !== Node.ELEMENT_NODE) {
        return [];
    }

    const tag = node.tagName.toUpperCase();

    if (CONTAINER_TAGS.has(tag)) {
        return nodesToBlocks(Array.from(node.childNodes));
    }

    if (tag === "P") {
        const html = sanitizeInlineHtml(node.innerHTML);
        return html ? [createParagraphBlock(html)] : [];
    }

    if (/^H[1-6]$/.test(tag)) {
        const html = sanitizeInlineHtml(node.innerHTML);
        if (!html) {
            return [];
        }

        return [
            {
                type: "header",
                data: {
                    text: html,
                    level: clampHeaderLevel(Number(tag[1])),
                },
            },
        ];
    }

    if (tag === "UL" || tag === "OL") {
        const items = Array.from(node.children)
            .filter((child) => child.tagName?.toUpperCase() === "LI")
            .map((child) => sanitizeInlineHtml(child.innerHTML))
            .filter(Boolean);

        if (items.length === 0) {
            return [];
        }

        return [
            {
                type: "list",
                data: {
                    style: tag === "OL" ? "ordered" : "unordered",
                    items,
                },
            },
        ];
    }

    if (tag === "BLOCKQUOTE") {
        const clone = node.cloneNode(true);
        const captionNode = clone.querySelector("cite, footer");
        const caption = captionNode
            ? sanitizeInlineHtml(captionNode.innerHTML)
            : "";

        if (captionNode) {
            captionNode.remove();
        }

        const html = sanitizeInlineHtml(clone.innerHTML);
        if (!html) {
            return [];
        }

        return [
            {
                type: "quote",
                data: {
                    text: html,
                    caption,
                    alignment: "left",
                },
            },
        ];
    }

    if (tag === "PRE") {
        const code = (node.textContent || "").trim();
        return code
            ? [
                  {
                      type: "code",
                      data: {
                          code,
                          language: "plain",
                      },
                  },
              ]
            : [];
    }

    if (tag === "TABLE") {
        const rows = Array.from(node.querySelectorAll("tr"))
            .map((row) =>
                Array.from(row.children)
                    .filter((cell) => ["TD", "TH"].includes(cell.tagName?.toUpperCase()))
                    .map((cell) => sanitizeInlineHtml(cell.innerHTML))
            )
            .filter((row) => row.length > 0);

        if (rows.length === 0) {
            return [];
        }

        const firstRow = node.querySelector("tr");
        const withHeadings =
            Boolean(node.querySelector("thead")) ||
            Array.from(firstRow?.children || []).every(
                (cell) => cell.tagName?.toUpperCase() === "TH"
            );

        return [
            {
                type: "table",
                data: {
                    content: rows,
                    withHeadings,
                    stretched: true,
                },
            },
        ];
    }

    if (tag === "BR") {
        return [];
    }

    if (DANGEROUS_TAGS.has(tag)) {
        return [];
    }

    const inlineHtml = sanitizeInlineHtml(node.innerHTML);
    if (inlineHtml) {
        return [createParagraphBlock(inlineHtml)];
    }

    return nodesToBlocks(Array.from(node.childNodes));
}

function createParagraphBlock(text) {
    return {
        type: "paragraph",
        data: {
            text,
        },
    };
}

function clampHeaderLevel(level) {
    if (level <= 2) {
        return 2;
    }

    if (level >= 4) {
        return 4;
    }

    return level;
}

function sanitizeInlineHtml(html) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(`<div>${html}</div>`, "text/html");
    const root = doc.body.firstElementChild || doc.body;

    sanitizeNodeTree(root);

    return root.innerHTML.trim();
}

function sanitizeNodeTree(node) {
    for (const child of Array.from(node.childNodes)) {
        if (child.nodeType === Node.COMMENT_NODE) {
            child.remove();
            continue;
        }

        if (child.nodeType === Node.TEXT_NODE) {
            continue;
        }

        if (child.nodeType !== Node.ELEMENT_NODE) {
            child.remove();
            continue;
        }

        const tag = child.tagName.toUpperCase();

        if (DANGEROUS_TAGS.has(tag)) {
            child.remove();
            continue;
        }

        if (!INLINE_TAGS.has(tag)) {
            while (child.firstChild) {
                node.insertBefore(child.firstChild, child);
            }
            child.remove();
            continue;
        }

        sanitizeAttributes(child, tag);
        sanitizeNodeTree(child);
    }
}

function sanitizeAttributes(node, tag) {
    const href = tag === "A" ? (node.getAttribute("href") || "").trim() : "";

    for (const attribute of Array.from(node.attributes)) {
        node.removeAttribute(attribute.name);
    }

    if (tag !== "A") {
        return;
    }

    const safeHref =
        href === "" || /^(https?:|mailto:|tel:|\/|#)/i.test(href) ? href : "";

    if (!safeHref) {
        while (node.firstChild) {
            node.parentNode?.insertBefore(node.firstChild, node);
        }
        node.remove();
        return;
    }

    node.setAttribute("href", safeHref);
    node.setAttribute("target", "_blank");
    node.setAttribute("rel", "noopener noreferrer");
}

function normalizeLooseText(text) {
    const normalized = (text || "").replace(/\s+/g, " ").trim();
    return normalized ? escapeHtml(normalized) : "";
}

function convertMarkdownLikeTextToBlocks(content) {
    const lines = content.split(/\r?\n/);
    const blocks = [];
    const paragraphLines = [];
    let list = null;
    let inCodeBlock = false;
    let codeLines = [];

    const flushParagraph = () => {
        const text = paragraphLines
            .map((line) => line.trim())
            .filter(Boolean)
            .join("<br>");

        if (text) {
            blocks.push(createParagraphBlock(formatInlineMarkdown(text)));
        }

        paragraphLines.length = 0;
    };

    const flushList = () => {
        if (list && list.items.length > 0) {
            blocks.push({
                type: "list",
                data: {
                    style: list.style,
                    items: list.items,
                },
            });
        }

        list = null;
    };

    const flushCode = () => {
        const code = codeLines.join("\n").trim();
        if (code) {
            blocks.push({
                type: "code",
                data: {
                    code,
                    language: "plain",
                },
            });
        }

        codeLines = [];
    };

    for (const rawLine of lines) {
        const line = rawLine.trim();

        if (rawLine.startsWith("```")) {
            flushParagraph();
            flushList();
            if (inCodeBlock) {
                flushCode();
            }
            inCodeBlock = !inCodeBlock;
            continue;
        }

        if (inCodeBlock) {
            codeLines.push(rawLine);
            continue;
        }

        if (line === "") {
            flushParagraph();
            flushList();
            continue;
        }

        const headingMatch = line.match(/^(#{2,4})\s+(.+)$/);
        if (headingMatch) {
            flushParagraph();
            flushList();
            blocks.push({
                type: "header",
                data: {
                    level: clampHeaderLevel(headingMatch[1].length),
                    text: formatInlineMarkdown(headingMatch[2]),
                },
            });
            continue;
        }

        const unorderedMatch = line.match(/^[-*]\s+(.+)$/);
        if (unorderedMatch) {
            flushParagraph();
            if (!list || list.style !== "unordered") {
                flushList();
                list = { style: "unordered", items: [] };
            }
            list.items.push(formatInlineMarkdown(unorderedMatch[1]));
            continue;
        }

        const orderedMatch = line.match(/^\d+\.\s+(.+)$/);
        if (orderedMatch) {
            flushParagraph();
            if (!list || list.style !== "ordered") {
                flushList();
                list = { style: "ordered", items: [] };
            }
            list.items.push(formatInlineMarkdown(orderedMatch[1]));
            continue;
        }

        paragraphLines.push(formatInlineMarkdown(line));
    }

    flushParagraph();
    flushList();
    if (inCodeBlock) {
        flushCode();
    }

    return blocks;
}

function formatInlineMarkdown(text) {
    let formatted = escapeHtml(text);

    formatted = formatted.replace(
        /\[([^\]]+)\]\((https?:\/\/[^\s)]+|mailto:[^\s)]+|tel:[^\s)]+)\)/g,
        '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>'
    );
    formatted = formatted.replace(/\*\*([^*]+)\*\*/g, "<strong>$1</strong>");
    formatted = formatted.replace(/`([^`]+)`/g, "<code>$1</code>");
    formatted = formatted.replace(/(^|[\s(])\*([^*]+)\*(?=[\s).,!?]|$)/g, "$1<em>$2</em>");

    return formatted;
}

function escapeHtml(text) {
    return text
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#39;");
}
