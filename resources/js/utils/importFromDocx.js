// utils/importFromDocx.js

/**
 * Импортирует содержимое из DOCX файла в формат EditorJS
 * @param {File} file - DOCX файл для импорта
 * @returns {Promise<Array>} - Массив блоков в формате EditorJS
 */
export async function importDocxToEditorJS(file) {
    try {
        // Динамически импортируем mammoth для получения правильного API
        const mammoth = await import('mammoth');

        // Используем convert функцию mammoth с файлом
        const result = await mammoth.convert({
            arrayBuffer: await file.arrayBuffer()
        });

        // Преобразуем HTML результат в EditorJS блоки
        const blocks = await htmlToEditorJSBlocks(result.value);

        return blocks;
    } catch (error) {
        console.error('Ошибка импорта DOCX:', error);
        throw error;
    }
}

/**
 * Преобразует HTML в EditorJS блоки
 * @param {string} html - HTML содержимое
 * @returns {Promise<Array>} - Массив EditorJS блоков
 */
async function htmlToEditorJSBlocks(html) {
    const blocks = [];

    // Создаем временный DOM элемент для парсинга HTML
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    const container = doc.body;

    // Проходим по всем элементам
    Array.from(container.childNodes).forEach((node) => {
        const block = parseNodeToEditorJSBlock(node);
        if (block) {
            blocks.push(block);
        }
    });

    return blocks.filter(block => block !== null);
}

/**
 * Преобразует DOM ноду в EditorJS блок
 * @param {Node} node - DOM нода
 * @returns {Object|null} - EditorJS блок или null
 */
function parseNodeToEditorJSBlock(node) {
    // Пропускаем текстовые ноды с только пробелами
    if (node.nodeType === Node.TEXT_NODE) {
        const text = node.textContent.trim();
        if (!text) return null;

        return {
            type: 'paragraph',
            data: {
                text: text
            }
        };
    }

    if (node.nodeType !== Node.ELEMENT_NODE) return null;

    const tag = node.tagName.toLowerCase();

    switch (tag) {
        case 'h1':
        case 'h2':
        case 'h3':
        case 'h4':
        case 'h5':
        case 'h6':
            const heading = node.textContent.trim();
            if (!heading) return null;

            // Преобразуем h5 и h6 в h4
            let level = parseInt(tag[1]);
            if (level > 4) level = 4;

            return {
                type: 'header',
                data: {
                    text: node.innerHTML.trim(),
                    level: level
                }
            };

        case 'p':
            const text = node.innerHTML.trim();
            if (!text) return null;

            return {
                type: 'paragraph',
                data: {
                    text: text
                }
            };

        case 'ul':
            const ulItems = Array.from(node.querySelectorAll('li')).map(li => li.innerHTML.trim()).filter(item => item);
            if (ulItems.length === 0) return null;

            return {
                type: 'list',
                data: {
                    style: 'unordered',
                    items: ulItems
                }
            };

        case 'ol':
            const olItems = Array.from(node.querySelectorAll('li')).map(li => li.innerHTML.trim()).filter(item => item);
            if (olItems.length === 0) return null;

            return {
                type: 'list',
                data: {
                    style: 'ordered',
                    items: olItems
                }
            };

        case 'blockquote':
            const quoteText = node.textContent.trim();
            if (!quoteText) return null;

            return {
                type: 'quote',
                data: {
                    text: node.innerHTML.trim(),
                    caption: '',
                    alignment: 'left'
                }
            };

        case 'table':
            const rows = Array.from(node.querySelectorAll('tr')).map(tr => {
                return Array.from(tr.querySelectorAll('td, th')).map(cell => cell.innerHTML.trim());
            });

            if (rows.length === 0) return null;

            return {
                type: 'table',
                data: {
                    content: rows,
                    stretched: true,
                    withHeadings: false
                }
            };

        case 'img':
            // Для изображений из DOCX требуется особая обработка
            // Пока пропускаем, так как нужна загрузка на сервер
            return null;

        case 'br':
            return null;

        case 'hr':
            return null;

        case 'strong':
        case 'b':
        case 'em':
        case 'i':
        case 'u':
        case 'span':
        case 'div':
            // Для встроенных элементов форматирования пытаемся извлечь текст как параграф
            const content = node.innerHTML.trim();
            if (!content || content.length === 0) return null;

            return {
                type: 'paragraph',
                data: {
                    text: content
                }
            };

        default:
            // Для неизвестных тегов пытаемся извлечь текст
            const defaultContent = node.innerHTML.trim();
            if (!defaultContent) return null;

            return {
                type: 'paragraph',
                data: {
                    text: defaultContent
                }
            };
    }
}

/**
 * Валидирует DOCX файл перед импортом
 * @param {File} file - Файл для проверки
 * @returns {boolean} - true если файл валиден
 */
export function validateDocxFile(file) {
    if (!file) {
        throw new Error('Файл не выбран');
    }

    if (file.type !== 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' &&
        !file.name.toLowerCase().endsWith('.docx')) {
        throw new Error('Это не DOCX файл. Пожалуйста, выберите файл с расширением .docx');
    }

    const maxSize = 10 * 1024 * 1024; // 10MB
    if (file.size > maxSize) {
        throw new Error(`Размер файла не должен превышать 10 MB. Текущий размер: ${(file.size / 1024 / 1024).toFixed(2)} MB`);
    }

    return true;
}
