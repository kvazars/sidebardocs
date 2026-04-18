function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

function getDocumentClone() {
    const source = document.querySelector("#file");
    if (!source) {
        throw new Error("Контейнер документа для экспорта не найден");
    }

    return {
        source,
        clone: source.cloneNode(true),
    };
}

function applyComputedStyles(sourceNode, cloneNode) {
    if (!(sourceNode instanceof Element) || !(cloneNode instanceof Element)) {
        return;
    }

    const computed = window.getComputedStyle(sourceNode);
    const styleProps = [
        "display",
        "width",
        "height",
        "max-width",
        "min-width",
        "max-height",
        "min-height",
        "margin",
        "margin-top",
        "margin-right",
        "margin-bottom",
        "margin-left",
        "padding",
        "padding-top",
        "padding-right",
        "padding-bottom",
        "padding-left",
        "border",
        "border-top",
        "border-right",
        "border-bottom",
        "border-left",
        "border-collapse",
        "border-spacing",
        "border-radius",
        "background",
        "background-color",
        "color",
        "font",
        "font-family",
        "font-size",
        "font-weight",
        "font-style",
        "line-height",
        "letter-spacing",
        "text-align",
        "text-transform",
        "text-decoration",
        "text-indent",
        "vertical-align",
        "white-space",
        "word-break",
        "overflow-wrap",
        "list-style-type",
        "list-style-position",
        "object-fit",
        "object-position",
        "box-sizing",
        "gap",
        "justify-content",
        "align-items",
        "flex-direction",
    ];

    styleProps.forEach((prop) => {
        cloneNode.style.setProperty(prop, computed.getPropertyValue(prop));
    });

    if (cloneNode.tagName === "IMG") {
        cloneNode.style.setProperty("height", "auto");
        cloneNode.style.setProperty("max-width", "100%");
    }

    if (cloneNode.tagName === "TABLE") {
        cloneNode.style.setProperty("width", "100%");
        cloneNode.style.setProperty("border-collapse", "collapse");
    }

    if (cloneNode.tagName === "TD" || cloneNode.tagName === "TH") {
        cloneNode.style.setProperty("vertical-align", "top");
    }

    const sourceChildren = Array.from(sourceNode.children);
    const cloneChildren = Array.from(cloneNode.children);
    for (let index = 0; index < sourceChildren.length; index += 1) {
        applyComputedStyles(sourceChildren[index], cloneChildren[index]);
    }
}

function normalizeClone(clone) {
    clone
        .querySelectorAll("button, .btn, .carousel-indicators, .carousel-control-prev, .carousel-control-next, .vue-easy-lightbox, script")
        .forEach((node) => node.remove());

    clone.querySelectorAll(".carousel, .carousel-inner").forEach((node) => {
        node.className = "";
        node.style.display = "block";
        node.style.width = "100%";
    });

    clone.querySelectorAll(".carousel-item").forEach((node) => {
        node.className = "";
        node.style.display = "block";
        node.style.opacity = "1";
        node.style.marginBottom = "16px";
        node.style.pageBreakInside = "avoid";
    });

    clone.querySelectorAll("iframe").forEach((iframe) => {
        const wrapper = document.createElement("div");
        wrapper.style.margin = "16px 0";
        wrapper.style.padding = "12px";
        wrapper.style.border = "1px solid #d0d7de";
        wrapper.style.borderRadius = "6px";

        const title = document.createElement("div");
        title.textContent = "Встроенный контент";
        title.style.fontWeight = "700";
        title.style.marginBottom = "8px";

        const link = document.createElement("a");
        link.href = iframe.getAttribute("src") || "";
        link.textContent = iframe.getAttribute("src") || "Открыть ссылку";
        link.style.color = "#0d6efd";
        link.style.textDecoration = "underline";

        wrapper.appendChild(title);
        wrapper.appendChild(link);
        iframe.replaceWith(wrapper);
    });

    clone.querySelectorAll("pre").forEach((pre) => {
        pre.style.whiteSpace = "pre-wrap";
        pre.style.wordBreak = "break-word";
        pre.style.background = "#f6f8fa";
        pre.style.border = "1px solid #d0d7de";
        pre.style.padding = "12px";
        pre.style.borderRadius = "6px";
        pre.style.fontFamily =
            "'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace";
    });

    clone.querySelectorAll("a").forEach((link) => {
        const href = link.getAttribute("href");
        if (href) {
            link.setAttribute("href", new URL(href, window.location.origin).href);
        }
        link.style.color = "#0d6efd";
        link.style.textDecoration = "underline";
    });

    clone.querySelectorAll("img").forEach((img) => {
        const src = img.getAttribute("src");
        if (src) {
            img.setAttribute("src", new URL(src, window.location.origin).href);
        }
        img.setAttribute("alt", img.getAttribute("alt") || "image");
        img.style.maxWidth = "100%";
        img.style.height = "auto";
        img.style.pageBreakInside = "avoid";
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
    return new Promise((resolve, reject) => {
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
                if (!response.ok) {
                    return;
                }
                const blob = await response.blob();
                const dataUrl = await blobToDataUrl(blob);
                img.setAttribute("src", dataUrl);
            } catch (error) {
                console.warn("Не удалось встроить изображение в экспорт Word:", src, error);
            }
        })
    );
}

function buildWordHtml(contentHtml, title) {
    return `<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:w="urn:schemas-microsoft-com:office:word"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="utf-8">
    <meta name="ProgId" content="Word.Document">
    <meta name="Generator" content="OpenAI Codex">
    <meta name="Originator" content="OpenAI Codex">
    <title>${escapeHtml(title)}</title>
    <!--[if gte mso 9]>
    <xml>
        <w:WordDocument>
            <w:View>Print</w:View>
            <w:Zoom>100</w:Zoom>
            <w:DoNotOptimizeForBrowser/>
        </w:WordDocument>
    </xml>
    <![endif]-->
    <style>
        @page {
            size: A4;
            margin: 1.6cm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #212529;
        }
        img {
            max-width: 100%;
            height: auto;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        td, th {
            border: 1px solid #adb5bd;
            padding: 6px 8px;
            vertical-align: top;
        }
        pre {
            white-space: pre-wrap;
            word-break: break-word;
        }
    </style>
</head>
<body>
${contentHtml}
</body>
</html>`;
}

export async function exportCurrentDocumentToWord(filename = "document.doc") {
    const { source, clone } = getDocumentClone();
    applyComputedStyles(source, clone);
    normalizeClone(clone);
    await inlineImages(clone);

    const html = buildWordHtml(clone.innerHTML, filename);
    const blob = new Blob(["\ufeff", html], {
        type: "application/msword",
    });

    downloadBlob(blob, filename);
}
