import { saveAs } from "file-saver";
import {
    AlignmentType,
    BorderStyle,
    Document,
    ExternalHyperlink,
    HeadingLevel,
    ImageRun,
    Packer,
    Paragraph,
    ShadingType,
    Table,
    TableCell,
    TableRow,
    TextRun,
    WidthType,
} from "docx";

function sanitizeFileName(filename) {
    return String(filename || "document.docx").replace(/[\\/:*?"<>|]+/g, "_");
}

function getDocumentClone() {
    const source = document.querySelector("#file");
    if (!source) {
        throw new Error("Контейнер документа для экспорта не найден");
    }

    const clone = source.cloneNode(true);
    return { source, clone };
}

function applyComputedStyles(sourceNode, cloneNode) {
    if (!(sourceNode instanceof Element) || !(cloneNode instanceof Element)) {
        return;
    }

    const computed = window.getComputedStyle(sourceNode);
    const props = [
        "text-align",
        "font-weight",
        "font-style",
        "text-decoration",
        "color",
        "background-color",
        "font-size",
        "font-family",
        "line-height",
        "margin-top",
        "margin-bottom",
        "padding-top",
        "padding-bottom",
        "border-top-width",
        "border-right-width",
        "border-bottom-width",
        "border-left-width",
    ];

    props.forEach((prop) => {
        cloneNode.style.setProperty(prop, computed.getPropertyValue(prop));
    });

    const sourceChildren = Array.from(sourceNode.children);
    const cloneChildren = Array.from(cloneNode.children);
    for (let index = 0; index < sourceChildren.length; index += 1) {
        applyComputedStyles(sourceChildren[index], cloneChildren[index]);
    }
}

function normalizeClone(clone) {
    clone.querySelectorAll("button, .btn, .carousel-indicators, .carousel-control-prev, .carousel-control-next, .vue-easy-lightbox, script").forEach((node) => {
        node.remove();
    });

    clone.querySelectorAll(".carousel, .carousel-inner").forEach((node) => {
        node.className = "";
    });

    clone.querySelectorAll(".carousel-item").forEach((node) => {
        node.className = "";
        node.style.display = "block";
        node.style.opacity = "1";
    });

    clone.querySelectorAll("iframe").forEach((iframe) => {
        const wrapper = document.createElement("div");
        wrapper.setAttribute("data-export-type", "iframe-link");
        wrapper.setAttribute("data-export-href", iframe.getAttribute("src") || "");
        wrapper.textContent = iframe.getAttribute("src") || "Встроенный контент";
        iframe.replaceWith(wrapper);
    });

    clone.querySelectorAll("a").forEach((link) => {
        const href = link.getAttribute("href");
        if (href) {
            link.setAttribute("href", new URL(href, window.location.origin).href);
        }
    });

    clone.querySelectorAll("img").forEach((img) => {
        const src = img.getAttribute("src");
        if (src) {
            img.setAttribute("src", new URL(src, window.location.origin).href);
        }
    });

    clone.querySelectorAll("*").forEach((node) => {
        node.removeAttribute("id");
        node.removeAttribute("data-bs-target");
        node.removeAttribute("data-bs-slide");
        node.removeAttribute("data-bs-slide-to");
        node.removeAttribute("data-coreui-target");
        node.removeAttribute("aria-current");
    });
}

async function blobToDataUrl(blob) {
    return await new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onloadend = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(blob);
    });
}

async function inlineImages(container) {
    const images = Array.from(container.querySelectorAll("img"));
    await Promise.all(
        images.map(async (img) => {
            const src = img.getAttribute("src");
            if (!src || src.startsWith("data:")) {
                return;
            }

            try {
                const response = await fetch(src, { mode: "cors" });
                if (!response.ok) return;
                const blob = await response.blob();
                const dataUrl = await blobToDataUrl(blob);
                img.setAttribute("src", dataUrl);
            } catch (error) {
                console.warn("Не удалось встроить изображение в DOCX:", src, error);
            }
        })
    );
}

function dataUrlToUint8Array(dataUrl) {
    const match = String(dataUrl).match(/^data:([^;]+);base64,(.+)$/);
    if (!match) return null;

    const binary = atob(match[2]);
    const bytes = new Uint8Array(binary.length);
    for (let index = 0; index < binary.length; index += 1) {
        bytes[index] = binary.charCodeAt(index);
    }

    return {
        bytes,
        mime: match[1],
    };
}

async function getImageInfo(src) {
    if (!src) return null;

    const parsed = dataUrlToUint8Array(src);
    if (!parsed) return null;

    const dimensions = await new Promise((resolve) => {
        const img = new Image();
        img.onload = () =>
            resolve({
                width: img.naturalWidth || 800,
                height: img.naturalHeight || 600,
            });
        img.onerror = () =>
            resolve({
                width: 800,
                height: 600,
            });
        img.src = src;
    });

    const maxWidth = 520;
    const ratio = dimensions.width > maxWidth ? maxWidth / dimensions.width : 1;

    return {
        data: parsed.bytes,
        width: Math.max(1, Math.round(dimensions.width * ratio)),
        height: Math.max(1, Math.round(dimensions.height * ratio)),
    };
}

function parseColor(value) {
    if (!value || value === "transparent") return undefined;
    const rgbMatch = String(value).match(/\d+/g);
    if (!rgbMatch || rgbMatch.length < 3) return undefined;

    return rgbMatch
        .slice(0, 3)
        .map((part) => Number(part).toString(16).padStart(2, "0"))
        .join("")
        .toUpperCase();
}

function getAlignment(node) {
    const textAlign = node?.style?.textAlign || "";
    if (textAlign === "center") return AlignmentType.CENTER;
    if (textAlign === "right" || textAlign === "end") return AlignmentType.RIGHT;
    if (textAlign === "justify") return AlignmentType.JUSTIFIED;
    return AlignmentType.LEFT;
}

function getSpacing(node, fallbackAfter = 120) {
    const marginTop = parseInt(node?.style?.marginTop || "0", 10);
    const marginBottom = parseInt(node?.style?.marginBottom || "0", 10);
    return {
        before: Number.isNaN(marginTop) ? 0 : marginTop * 5,
        after: Number.isNaN(marginBottom) ? fallbackAfter : Math.max(marginBottom * 5, fallbackAfter),
    };
}

function mergeTextStyle(baseStyle = {}, node) {
    if (!(node instanceof Element)) {
        return baseStyle;
    }

    const merged = { ...baseStyle };
    const fontWeight = node.style.fontWeight || "";
    const fontStyle = node.style.fontStyle || "";
    const textDecoration = node.style.textDecoration || "";
    const color = parseColor(node.style.color);

    if (fontWeight === "bold" || Number(fontWeight) >= 600 || ["B", "STRONG"].includes(node.tagName)) {
        merged.bold = true;
    }
    if (fontStyle === "italic" || ["I", "EM"].includes(node.tagName)) {
        merged.italics = true;
    }
    if (textDecoration.includes("underline") || node.tagName === "U") {
        merged.underline = {};
    }
    if (node.tagName === "CODE") {
        merged.font = "Courier New";
        merged.size = 20;
        merged.shading = {
            type: ShadingType.CLEAR,
            color: "auto",
            fill: "F3F4F6",
        };
    }
    if (color) {
        merged.color = color;
    }

    return merged;
}

async function convertInlineNodes(nodes, textStyle = {}) {
    const children = [];

    for (const node of nodes) {
        if (node.nodeType === Node.TEXT_NODE) {
            const text = node.textContent || "";
            if (text) {
                children.push(new TextRun({ text, ...textStyle }));
            }
            continue;
        }

        if (!(node instanceof Element)) {
            continue;
        }

        if (node.tagName === "BR") {
            children.push(new TextRun({ text: "", break: 1, ...textStyle }));
            continue;
        }

        if (node.tagName === "IMG") {
            const imageInfo = await getImageInfo(node.getAttribute("src"));
            if (imageInfo) {
                children.push(
                    new ImageRun({
                        data: imageInfo.data,
                        transformation: {
                            width: imageInfo.width,
                            height: imageInfo.height,
                        },
                    })
                );
            }
            continue;
        }

        const nextStyle = mergeTextStyle(textStyle, node);

        if (node.tagName === "A") {
            const href = node.getAttribute("href") || "";
            const linkChildren = await convertInlineNodes(
                Array.from(node.childNodes),
                { ...nextStyle, style: "Hyperlink" }
            );

            if (href) {
                children.push(
                    new ExternalHyperlink({
                        link: href,
                        children:
                            linkChildren.length > 0
                                ? linkChildren
                                : [new TextRun({ text: href, style: "Hyperlink" })],
                    })
                );
            }
            continue;
        }

        const nestedChildren = await convertInlineNodes(
            Array.from(node.childNodes),
            nextStyle
        );
        children.push(...nestedChildren);
    }

    return children;
}

async function createParagraphFromElement(element, options = {}) {
    const children = await convertInlineNodes(Array.from(element.childNodes));
    return new Paragraph({
        children:
            children.length > 0
                ? children
                : [new TextRun({ text: element.textContent || "" })],
        heading: options.heading,
        alignment: options.alignment || getAlignment(element),
        bullet: options.bullet,
        spacing: options.spacing || getSpacing(element, options.after ?? 120),
        indent: options.indent,
        border: options.border,
        shading: options.shading,
    });
}

async function convertImageElement(element, alignment = AlignmentType.CENTER) {
    const imageInfo = await getImageInfo(element.getAttribute("src"));
    if (!imageInfo) return [];

    const result = [
        new Paragraph({
            children: [
                new ImageRun({
                    data: imageInfo.data,
                    transformation: {
                        width: imageInfo.width,
                        height: imageInfo.height,
                    },
                }),
            ],
            alignment,
            spacing: { before: 80, after: 80 },
        }),
    ];

    const captionNode = element.parentElement?.querySelector("p.fst-italic");
    if (captionNode && captionNode.textContent?.trim()) {
        result.push(
            new Paragraph({
                children: [
                    new TextRun({
                        text: captionNode.textContent.trim(),
                        italics: true,
                        color: "6B7280",
                    }),
                ],
                alignment: AlignmentType.CENTER,
                spacing: { after: 160 },
            })
        );
    }

    return result;
}

async function convertTableElement(table) {
    const directRows = [];
    Array.from(table.children).forEach((child) => {
        if (child.tagName === "TR") {
            directRows.push(child);
            return;
        }

        if (["THEAD", "TBODY", "TFOOT"].includes(child.tagName)) {
            Array.from(child.children)
                .filter((row) => row.tagName === "TR")
                .forEach((row) => directRows.push(row));
        }
    });

    const rows = [];
    const trNodes = directRows.length > 0 ? directRows : Array.from(table.querySelectorAll(":scope > tr"));

    for (const tr of trNodes) {
        const cells = [];
        const cellNodes = Array.from(tr.children).filter((node) =>
            ["TD", "TH"].includes(node.tagName)
        );

        for (const cell of cellNodes) {
            const convertedChildren = await convertContainerChildren(cell);
            const paragraphChildren = convertedChildren.filter(
                (child) => child instanceof Paragraph
            );

            if (paragraphChildren.length === 0) {
                paragraphChildren.push(
                    new Paragraph({
                        children: [
                            new TextRun({
                                text: cell.textContent || "",
                                bold: cell.tagName === "TH",
                            }),
                        ],
                    })
                );
            }

            cells.push(
                new TableCell({
                    children: paragraphChildren,
                })
            );
        }

        rows.push(new TableRow({ children: cells }));
    }

    return [
        new Table({
            rows,
            width: { size: 9000, type: WidthType.DXA },
            borders: {
                top: { style: BorderStyle.SINGLE, size: 4, color: "BFC5CC" },
                bottom: { style: BorderStyle.SINGLE, size: 4, color: "BFC5CC" },
                left: { style: BorderStyle.SINGLE, size: 4, color: "BFC5CC" },
                right: { style: BorderStyle.SINGLE, size: 4, color: "BFC5CC" },
                insideHorizontal: { style: BorderStyle.SINGLE, size: 4, color: "D8DDE3" },
                insideVertical: { style: BorderStyle.SINGLE, size: 4, color: "D8DDE3" },
            },
        }),
        new Paragraph({ text: "", spacing: { after: 120 } }),
    ];
}

async function convertListElement(listElement, ordered = false) {
    const result = [];
    const items = Array.from(listElement.children).filter((node) => node.tagName === "LI");

    for (const [index, item] of items.entries()) {
        const inlineChildren = await convertInlineNodes(Array.from(item.childNodes));
        result.push(
            new Paragraph({
                children:
                    inlineChildren.length > 0
                        ? [
                              new TextRun({
                                  text: ordered ? `${index + 1}. ` : "• ",
                                  bold: true,
                              }),
                              ...inlineChildren,
                          ]
                        : [new TextRun({ text: ordered ? `${index + 1}.` : "•" })],
                spacing: { after: 60 },
                indent: { left: 360 },
            })
        );
    }

    result.push(new Paragraph({ text: "", spacing: { after: 100 } }));
    return result;
}

async function convertPreElement(pre) {
    return [
        new Paragraph({
            children: [
                new TextRun({
                    text: pre.textContent || "",
                    font: "Courier New",
                    size: 20,
                }),
            ],
            shading: {
                type: ShadingType.CLEAR,
                color: "auto",
                fill: "F6F8FA",
            },
            border: {
                top: { style: BorderStyle.SINGLE, size: 1, color: "D0D7DE" },
                right: { style: BorderStyle.SINGLE, size: 1, color: "D0D7DE" },
                bottom: { style: BorderStyle.SINGLE, size: 1, color: "D0D7DE" },
                left: { style: BorderStyle.SINGLE, size: 1, color: "D0D7DE" },
            },
            spacing: { before: 80, after: 160 },
        }),
    ];
}

async function convertSpecialDiv(element) {
    if (element.dataset.exportType === "iframe-link") {
        const href = element.getAttribute("data-export-href") || "";
        return [
            new Paragraph({
                children: [
                    new TextRun({ text: "Встроенный контент: ", bold: true }),
                    new ExternalHyperlink({
                        link: href,
                        children: [new TextRun({ text: href || "Открыть ссылку", style: "Hyperlink" })],
                    }),
                ],
                spacing: { after: 160 },
            }),
        ];
    }

    if (element.classList.contains("alert")) {
        const colorMap = {
            "alert-success": "E8F5E9",
            "alert-danger": "FDECEC",
            "alert-warning": "FFF4DB",
            "alert-info": "EAF4FF",
            "alert-primary": "EAF1FF",
        };
        const fill =
            Object.entries(colorMap).find(([className]) =>
                element.classList.contains(className)
            )?.[1] || "F3F4F6";

        const paragraph = await createParagraphFromElement(element, {
            shading: {
                type: ShadingType.CLEAR,
                color: "auto",
                fill,
            },
            border: {
                top: { style: BorderStyle.SINGLE, size: 1, color: "D0D7DE" },
                right: { style: BorderStyle.SINGLE, size: 1, color: "D0D7DE" },
                bottom: { style: BorderStyle.SINGLE, size: 1, color: "D0D7DE" },
                left: { style: BorderStyle.SINGLE, size: 1, color: "D0D7DE" },
            },
        });
        return [paragraph];
    }

    return null;
}

async function convertElement(element) {
    if (!(element instanceof Element)) {
        return [];
    }

    switch (element.tagName) {
        case "H1":
            return [
                await createParagraphFromElement(element, {
                    heading: HeadingLevel.TITLE,
                    alignment: getAlignment(element),
                    spacing: { after: 240 },
                }),
            ];
        case "H2":
            return [
                await createParagraphFromElement(element, {
                    heading: HeadingLevel.HEADING_1,
                    spacing: { before: 160, after: 120 },
                }),
            ];
        case "H3":
            return [
                await createParagraphFromElement(element, {
                    heading: HeadingLevel.HEADING_2,
                    spacing: { before: 140, after: 100 },
                }),
            ];
        case "H4":
            return [
                await createParagraphFromElement(element, {
                    heading: HeadingLevel.HEADING_3,
                    spacing: { before: 120, after: 80 },
                }),
            ];
        case "P":
            return [await createParagraphFromElement(element)];
        case "IMG":
            return await convertImageElement(element);
        case "TABLE":
            return await convertTableElement(element);
        case "UL":
            return await convertListElement(element, false);
        case "OL":
            return await convertListElement(element, true);
        case "PRE":
            return await convertPreElement(element);
        case "FIGURE": {
            const result = [];
            const quote = element.querySelector("blockquote");
            const caption = element.querySelector("figcaption");
            if (quote) {
                result.push(
                    await createParagraphFromElement(quote, {
                        border: {
                            left: { style: BorderStyle.SINGLE, size: 6, color: "9CA3AF" },
                        },
                        indent: { left: 320 },
                        spacing: { before: 60, after: 80 },
                    })
                );
            }
            if (caption) {
                result.push(
                    new Paragraph({
                        children: [
                            new TextRun({
                                text: caption.textContent || "",
                                italics: true,
                                color: "6B7280",
                            }),
                        ],
                        alignment: getAlignment(caption),
                        spacing: { after: 160 },
                    })
                );
            }
            return result;
        }
        case "DIV": {
            const specialResult = await convertSpecialDiv(element);
            if (specialResult) {
                return specialResult;
            }
            return await convertContainerChildren(element);
        }
        default:
            return await convertContainerChildren(element);
    }
}

async function convertContainerChildren(container) {
    const result = [];
    const nodes = Array.from(container.childNodes);

    for (const node of nodes) {
        if (node.nodeType === Node.TEXT_NODE) {
            const text = node.textContent?.trim();
            if (text) {
                result.push(
                    new Paragraph({
                        children: [new TextRun({ text })],
                        spacing: { after: 100 },
                    })
                );
            }
            continue;
        }

        if (node instanceof Element) {
            const converted = await convertElement(node);
            result.push(...converted);
        }
    }

    return result;
}

function createDocument(children) {
    return new Document({
        styles: {
            default: {
                document: {
                    run: {
                        font: "Arial",
                        size: 22,
                    },
                    paragraph: {
                        spacing: {
                            line: 276,
                        },
                    },
                },
            },
        },
        sections: [
            {
                properties: {
                    page: {
                        margin: {
                            top: 900,
                            right: 900,
                            bottom: 900,
                            left: 900,
                        },
                    },
                },
                children,
            },
        ],
    });
}

export async function exportCurrentDocumentToDocx(filename = "document.docx") {
    const { source, clone } = getDocumentClone();
    applyComputedStyles(source, clone);
    normalizeClone(clone);
    await inlineImages(clone);

    const children = await convertContainerChildren(clone);
    const doc = createDocument(children);
    const blob = await Packer.toBlob(doc);
    saveAs(blob, sanitizeFileName(filename));
}
