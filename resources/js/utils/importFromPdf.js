/**
 * Валидация PDF файла
 * @param {File} file
 */
export function validatePdfFile(file) {
    if (!file) {
        throw new Error("Файл не выбран");
    }

    const isPdfMime = file.type === "application/pdf";
    const isPdfExt = file.name && file.name.toLowerCase().endsWith(".pdf");
    if (!isPdfMime && !isPdfExt) {
        throw new Error("Выберите корректный PDF файл");
    }

    const maxSize = 50 * 1024 * 1024; // 50MB
    if (file.size > maxSize) {
        throw new Error("PDF файл слишком большой (максимум 50MB)");
    }
}

/**
 * Импортирует содержимое из PDF файла в формат EditorJS
 * @param {File} file - PDF файл для импорта
 * @param {Function|null} uploadImageCallback - функция загрузки изображения на сервер
 * @returns {Promise<Array>} - Массив блоков в формате EditorJS
 */
export async function importPdfToEditorJS(file, uploadImageCallback = null) {
    validatePdfFile(file);

    const [{ GlobalWorkerOptions, getDocument, OPS }, workerModule] = await Promise.all([
        import("pdfjs-dist/legacy/build/pdf.mjs"),
        import("pdfjs-dist/legacy/build/pdf.worker.mjs?url"),
    ]);
    GlobalWorkerOptions.workerSrc = workerModule.default || workerModule;

    const data = await file.arrayBuffer();
    const loadingTask = getDocument({
        data,
        isEvalSupported: false,
    });

    try {
        const pdf = await loadingTask.promise;
        const blocks = [];

        for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber++) {
            const page = await pdf.getPage(pageNumber);
            const textContent = await page.getTextContent();
            const lines = textItemsToLineObjects(textContent.items || []);

            blocks.push(...linesToEditorBlocks(lines));

            // Добавляем только реальные изображения из PDF (не скриншот страницы)
            if (uploadImageCallback) {
                const imageUrls = await extractAndUploadPageImages(
                    page,
                    uploadImageCallback,
                    OPS
                );
                for (const imageUrl of imageUrls) {
                    blocks.push({
                        type: "image",
                        data: {
                            caption: "",
                            stretched: true,
                            withBorder: false,
                            withBackground: false,
                            file: {
                                url: imageUrl,
                            },
                        },
                    });
                }
            }
        }

        return sanitizeImportedBlocks(blocks);
    } finally {
        await loadingTask.destroy();
    }
}

async function extractAndUploadPageImages(page, uploadImageCallback, OPS) {
    const urls = [];

    try {
        const operatorList = await page.getOperatorList();
        const fnArray = operatorList.fnArray || [];
        const argsArray = operatorList.argsArray || [];
        const inlinePayloads = [];
        const xObjectIds = new Set();

        for (let i = 0; i < fnArray.length; i++) {
            const fn = fnArray[i];
            const args = argsArray[i] || [];

            if (fn === OPS.paintInlineImageXObject && args[0]) {
                inlinePayloads.push(args[0]);
            }

            if (
                (fn === OPS.paintImageXObject || fn === OPS.paintJpegXObject) &&
                args[0]
            ) {
                xObjectIds.add(args[0]);
            }
        }

        // Сначала inline-изображения: они уже доступны в operator list.
        for (const payload of inlinePayloads) {
            const blob = await imagePayloadToBlob(payload);
            if (!blob) continue;
            const uploadedUrl = await uploadImageCallback(blob);
            if (uploadedUrl) urls.push(uploadedUrl);
        }

        // Прогреваем ресурсы страницы, чтобы XObject изображения были доступны в page.objs.
        await warmupPageResources(page);

        // Потом XObject-изображения, без повторов по id.
        const payloads = await Promise.all(
            Array.from(xObjectIds).map((objectId) => getImageByObjectId(page, objectId))
        );

        for (const imagePayload of payloads) {
            if (!imagePayload) continue;

            const blob = await imagePayloadToBlob(imagePayload);
            if (!blob) continue;

            const uploadedUrl = await uploadImageCallback(blob);
            if (uploadedUrl) urls.push(uploadedUrl);
        }
    } catch {
        return [];
    }

    return urls;
}

async function warmupPageResources(page) {
    try {
        const viewport = page.getViewport({ scale: 0.2 });
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");
        if (!ctx) return;
        canvas.width = Math.max(1, Math.floor(viewport.width));
        canvas.height = Math.max(1, Math.floor(viewport.height));
        await page.render({
            canvasContext: ctx,
            viewport,
        }).promise;
    } catch {
        // warming is best-effort
    }
}

async function getImageByObjectId(page, objectId) {
    return new Promise((resolve) => {
        let done = false;
        const finish = (val) => {
            if (done) return;
            done = true;
            resolve(val);
        };

        const timeout = setTimeout(() => finish(null), 2000);

        try {
            const maybe = page.objs.get(objectId);
            if (maybe) {
                clearTimeout(timeout);
                finish(maybe);
                return;
            }
        } catch {
            // ignore and continue with callback mode
        }
        try {
            if (page.commonObjs) {
                const maybeCommon = page.commonObjs.get(objectId);
                if (maybeCommon) {
                    clearTimeout(timeout);
                    finish(maybeCommon);
                    return;
                }
            }
        } catch {
            // ignore and continue with callback mode
        }

        try {
            page.objs.get(objectId, (img) => {
                clearTimeout(timeout);
                finish(img || null);
            });
        } catch {
            // ignore and try commonObjs callback
        }
        try {
            if (page.commonObjs) {
                page.commonObjs.get(objectId, (img) => {
                    clearTimeout(timeout);
                    finish(img || null);
                });
            }
        } catch {
            // keep timeout fallback
        }
    });
}

async function imagePayloadToBlob(payload) {
    try {
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");
        if (!ctx) return null;

        const bitmap =
            payload?.bitmap && typeof payload.bitmap.width === "number"
                ? payload.bitmap
                : (typeof ImageBitmap !== "undefined" &&
                  payload &&
                  typeof payload.width === "number" &&
                  typeof payload.height === "number" &&
                  typeof payload.close === "function")
                ? payload
                : null;

        if (bitmap) {
            canvas.width = bitmap.width;
            canvas.height = bitmap.height;
            fillCanvasWhite(ctx, canvas);
            ctx.drawImage(bitmap, 0, 0);
            return await canvasToBlob(canvas);
        }

        const width = payload?.width || payload?.bitmap?.width || 0;
        const height = payload?.height || payload?.bitmap?.height || 0;
        const data = payload?.data || payload?.bitmap?.data || payload?.imgData?.data;
        if (!width || !height || !data) return null;

        const rgba = toRgba(data, width, height);
        if (!rgba) return null;

        const imageData = new ImageData(rgba, width, height);
        canvas.width = width;
        canvas.height = height;
        fillCanvasWhite(ctx, canvas);
        ctx.putImageData(imageData, 0, 0);

        return await canvasToBlob(canvas);
    } catch {
        return null;
    }
}

function toRgba(data, width, height) {
    const pxCount = width * height;
    const len = data.length || 0;

    if (len === pxCount * 4) return flattenRgbaToWhite(data);

    if (len === pxCount * 3) {
        const out = new Uint8ClampedArray(pxCount * 4);
        for (let i = 0, j = 0; i < len; i += 3, j += 4) {
            out[j] = data[i];
            out[j + 1] = data[i + 1];
            out[j + 2] = data[i + 2];
            out[j + 3] = 255;
        }
        return out;
    }

    if (len === pxCount) {
        const out = new Uint8ClampedArray(pxCount * 4);
        for (let i = 0, j = 0; i < len; i++, j += 4) {
            const v = data[i];
            out[j] = v;
            out[j + 1] = v;
            out[j + 2] = v;
            out[j + 3] = 255;
        }
        return out;
    }

    return null;
}

function flattenRgbaToWhite(data) {
    const out = new Uint8ClampedArray(data.length);
    for (let i = 0; i < data.length; i += 4) {
        const a = (data[i + 3] ?? 255) / 255;
        out[i] = Math.round((data[i] ?? 0) * a + 255 * (1 - a));
        out[i + 1] = Math.round((data[i + 1] ?? 0) * a + 255 * (1 - a));
        out[i + 2] = Math.round((data[i + 2] ?? 0) * a + 255 * (1 - a));
        out[i + 3] = 255;
    }
    return out;
}

function fillCanvasWhite(ctx, canvas) {
    ctx.save();
    ctx.globalCompositeOperation = "source-over";
    ctx.fillStyle = "#ffffff";
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.restore();
}

async function canvasToBlob(canvas) {
    return new Promise((resolve) => {
        // JPEG обычно заметно меньше PNG для фотографий и сканов из PDF.
        canvas.toBlob(resolve, "image/jpeg", 0.9);
    });
}

/**
 * Преобразует элементы текста pdf.js в массив строк с метаданными
 * @param {Array} items
 * @returns {Array<{text: string, columns: string[], size: number}>}
 */
function textItemsToLineObjects(items) {
    const prepared = items
        .map((item) => ({
            text: typeof item.str === "string" ? item.str.trim() : "",
            x: Array.isArray(item.transform) ? item.transform[4] : 0,
            y: Array.isArray(item.transform) ? item.transform[5] : 0,
            size: detectFontSize(item),
        }))
        .filter((item) => item.text);

    prepared.sort((a, b) => {
        const dy = b.y - a.y;
        if (Math.abs(dy) > 2) return dy;
        return a.x - b.x;
    });

    const lines = [];
    const lineTolerance = 2;
    let currentY = null;
    let currentLineItems = [];

    for (const item of prepared) {
        if (currentY === null || Math.abs(currentY - item.y) <= lineTolerance) {
            currentLineItems.push(item);
            currentY = currentY === null ? item.y : (currentY + item.y) / 2;
            continue;
        }

        const lineObj = buildLineObject(currentLineItems);
        if (lineObj) lines.push(lineObj);
        currentLineItems = [item];
        currentY = item.y;
    }

    const last = buildLineObject(currentLineItems);
    if (last) lines.push(last);

    return lines;
}

function detectFontSize(item) {
    if (!Array.isArray(item.transform)) return 12;
    const a = Number(item.transform[0]) || 0;
    const b = Number(item.transform[1]) || 0;
    const d = Number(item.transform[3]) || 0;
    const sizeA = Math.hypot(a, b);
    return sizeA > 0 ? sizeA : d || 12;
}

function buildLineObject(lineItems) {
    if (!lineItems.length) return null;

    lineItems.sort((a, b) => a.x - b.x);
    const avgSize =
        lineItems.reduce((sum, item) => sum + item.size, 0) / lineItems.length;

    const columns = [];
    let currentCol = [];
    let prevX = null;
    const xGapThreshold = Math.max(35, avgSize * 2.5);

    for (const item of lineItems) {
        if (prevX !== null && item.x - prevX > xGapThreshold && currentCol.length) {
            const colText = normalizeLine(currentCol);
            if (colText) columns.push(colText);
            currentCol = [];
        }

        currentCol.push(item.text);
        prevX = item.x;
    }

    const lastCol = normalizeLine(currentCol);
    if (lastCol) columns.push(lastCol);

    const text = normalizeLine(columns.length > 1 ? columns : lineItems.map((i) => i.text));
    if (!text) return null;

    return {
        text,
        columns,
        size: avgSize,
    };
}

function normalizeLine(parts) {
    const text = parts.join(" ").replace(/\s+/g, " ").trim();
    return text || "";
}

function linesToEditorBlocks(lines) {
    if (!lines.length) return [];

    const blocks = [];
    const medianFont = getMedian(lines.map((line) => line.size)) || 12;

    for (let i = 0; i < lines.length; ) {
        const table = collectTable(lines, i);
        if (table) {
            blocks.push(table.block);
            i = table.nextIndex;
            continue;
        }

        const list = collectList(lines, i);
        if (list) {
            blocks.push(list.block);
            i = list.nextIndex;
            continue;
        }

        const line = lines[i];
        const headingLevel = detectHeadingLevel(line, medianFont);
        if (headingLevel) {
            blocks.push({
                type: "header",
                data: {
                    text: escapeHtml(line.text),
                    level: headingLevel,
                },
            });
        } else {
            blocks.push({
                type: "paragraph",
                data: {
                    text: escapeHtml(line.text),
                },
            });
        }

        i++;
    }

    return blocks;
}

function collectList(lines, startIndex) {
    const first = parseListItem(lines[startIndex]?.text || "");
    if (!first) return null;

    const items = [escapeHtml(first.text)];
    const style = first.ordered ? "ordered" : "unordered";
    let i = startIndex + 1;

    while (i < lines.length) {
        const next = parseListItem(lines[i].text);
        if (!next || next.ordered !== first.ordered) break;
        items.push(escapeHtml(next.text));
        i++;
    }

    if (!items.length) return null;

    return {
        block: {
            type: "list",
            data: {
                style,
                items,
            },
        },
        nextIndex: i,
    };
}

function collectTable(lines, startIndex) {
    const first = lines[startIndex];
    if (!first || first.columns.length < 2) return null;

    const rows = [first.columns];
    let i = startIndex + 1;

    while (i < lines.length) {
        const line = lines[i];
        if (!line || line.columns.length < 2) break;

        const diff = Math.abs(line.columns.length - rows[0].length);
        if (diff > 1) break;

        rows.push(line.columns);
        i++;
    }

    if (rows.length < 2) return null;

    const columnCount = Math.max(...rows.map((row) => row.length), 0);
    if (!columnCount) return null;

    const normalizedRows = rows.map((row) => {
        const filled = [...row];
        while (filled.length < columnCount) filled.push("");
        if (filled.length > columnCount) filled.length = columnCount;
        return filled.map((cell) => escapeHtml(cell || ""));
    });

    return {
        block: {
            type: "table",
            data: {
                content: normalizedRows,
                withHeadings: false,
                stretched: true,
            },
        },
        nextIndex: i,
    };
}

function parseListItem(text) {
    const normalized = text.trim();
    if (!normalized) return null;

    const unordered = normalized.match(/^([•●▪◦\-–—])\s+(.+)$/u);
    if (unordered) {
        return {
            ordered: false,
            text: unordered[2].trim(),
        };
    }

    const ordered = normalized.match(/^(\d+|[a-zA-Z])[.)]\s+(.+)$/u);
    if (ordered) {
        return {
            ordered: true,
            text: ordered[2].trim(),
        };
    }

    return null;
}

function detectHeadingLevel(line, medianFont) {
    const text = line.text || "";
    if (!text) return null;
    if (text.length > 120) return null;
    if (parseListItem(text)) return null;
    if (line.columns.length > 1) return null;

    const ratio = line.size / medianFont;
    if (ratio < 1.2) return null;

    if (ratio >= 1.8) return 2;
    if (ratio >= 1.45) return 3;
    return 4;
}

function getMedian(values) {
    if (!values.length) return 0;
    const sorted = [...values].sort((a, b) => a - b);
    const middle = Math.floor(sorted.length / 2);
    if (sorted.length % 2) return sorted[middle];
    return (sorted[middle - 1] + sorted[middle]) / 2;
}

function escapeHtml(text) {
    return text
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;");
}

function sanitizeImportedBlocks(blocks) {
    const cleaned = [];

    for (const block of blocks || []) {
        if (!block || !block.type || typeof block.data !== "object") continue;

        if (block.type === "paragraph") {
            const text = (block.data.text || "").toString().trim();
            if (!text) continue;
            cleaned.push({
                type: "paragraph",
                data: { text },
            });
            continue;
        }

        if (block.type === "header") {
            const text = (block.data.text || "").toString().trim();
            const levelRaw = Number(block.data.level);
            const level = Number.isFinite(levelRaw)
                ? Math.min(4, Math.max(2, levelRaw))
                : 3;
            if (!text) continue;
            cleaned.push({
                type: "header",
                data: { text, level },
            });
            continue;
        }

        if (block.type === "list") {
            const style = block.data.style === "ordered" ? "ordered" : "unordered";
            const items = Array.isArray(block.data.items)
                ? block.data.items
                      .map((item) => (item || "").toString().trim())
                      .filter(Boolean)
                : [];
            if (!items.length) continue;
            cleaned.push({
                type: "list",
                data: { style, items },
            });
            continue;
        }

        if (block.type === "table") {
            const rows = Array.isArray(block.data.content) ? block.data.content : [];
            if (!rows.length) continue;

            const width = Math.max(
                ...rows.map((row) => (Array.isArray(row) ? row.length : 0)),
                0
            );
            if (!width) continue;

            const content = rows
                .map((row) => (Array.isArray(row) ? row : []))
                .map((row) => {
                    const fixed = row
                        .slice(0, width)
                        .map((cell) => (cell || "").toString());
                    while (fixed.length < width) fixed.push("");
                    return fixed;
                })
                .filter((row) => row.some((cell) => cell.trim() !== ""));

            if (!content.length) continue;

            cleaned.push({
                type: "table",
                data: {
                    content,
                    withHeadings: Boolean(block.data.withHeadings),
                    stretched: true,
                },
            });
            continue;
        }

        if (block.type === "image") {
            const url = block?.data?.file?.url || "";
            if (!url) continue;
            cleaned.push({
                type: "image",
                data: {
                    caption: (block.data.caption || "").toString(),
                    stretched: Boolean(block.data.stretched),
                    withBorder: Boolean(block.data.withBorder),
                    withBackground: Boolean(block.data.withBackground),
                    file: { url },
                },
            });
            continue;
        }
    }

    return cleaned;
}
