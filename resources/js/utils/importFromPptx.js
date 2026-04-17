// utils/importFromPptx.js
import JSZip from 'jszip';
import { parseStringPromise } from 'xml2js';

/**
 * Импортирует содержимое из PPTX файла в формат EditorJS
 * @param {File} file - PPTX файл для импорта
 * @param {Function} uploadImageCallback - Функция для загрузки изображений на сервер
 * @returns {Promise<Array>} - Массив блоков в формате EditorJS
 */
export async function importPptxToEditorJS(file, uploadImageCallback) {
    try {
        const zip = new JSZip();
        const zipData = await zip.loadAsync(file);
        
        // Логируем все файлы в архиве для отладки
        Object.keys(zipData.files).forEach(name => {
            if (name.includes('media') || name.includes('slide')) {
            }
        });
        
        // Получаем список всех слайдов
        const slides = [];
        const slideFiles = Object.keys(zipData.files).filter(name => 
            name.match(/ppt\/slides\/slide\d+\.xml$/i)
        );
        
        
        // Сортируем по номерам слайдов
        slideFiles.sort((a, b) => {
            const numA = parseInt(a.match(/\d+/)[0]);
            const numB = parseInt(b.match(/\d+/)[0]);
            return numA - numB;
        });
        
        // Обрабатываем каждый слайд
        for (let i = 0; i < slideFiles.length; i++) {
            const slideFile = slideFiles[i];
            const slideNumber = parseInt(slideFile.match(/\d+/)[0]);
            const slideContent = await zipData.file(slideFile).async('string');
            const slideBlocks = await parseSlide(slideContent, zipData, slideNumber);
            slides.push(...slideBlocks);
        }
        
        // Если нужна загрузка изображений на сервер
        if (uploadImageCallback) {
            for (let i = 0; i < slides.length; i++) {
                const block = slides[i];
                if (block.type === 'image' && block.data.file && block.data.file.url && block.data.file.url.startsWith('blob:')) {
                    try {
                        // Загружаем изображение на сервер
                        const response = await fetch(block.data.file.url);
                        const blob = await response.blob();
                        const uploadedUrl = await uploadImageCallback(blob);
                        if (uploadedUrl) {
                            block.data.url = uploadedUrl;
                            block.data.file.url = uploadedUrl;
                        }
                    } catch (error) {
                    }
                }
            }
        }
        
        // Разворачиваем массив чтобы восстановить правильный порядок
        const reversedSlides = slides.reverse();
        return reversedSlides;
    } catch (error) {
        throw error;
    }
}

/**
 * Парсит один слайд и извлекает текст и изображения
 * @param {string} slideXml - XML содержимое слайда
 * @param {JSZip} zip - Объект ZIP архива для получения изображений
 * @param {number} slideNumber - Номер слайда для правильного пути к relationships
 * @returns {Promise<Array>} - Массив блоков
 */
async function parseSlide(slideXml, zip, slideNumber = 1) {
    try {
        const blocks = [];
        const parsed = await parseStringPromise(slideXml);
        
        const slide = parsed['p:sld'];
        if (!slide || !slide['p:cSld']) return blocks;
        
        const cSld = slide['p:cSld'][0];
        if (!cSld['p:spTree']) return blocks;
        
        const spTree = cSld['p:spTree'][0];
        
        // Функция для преобразования значения в массив
        const ensureArray = (val) => {
            if (!val) return [];
            return Array.isArray(val) ? val : [val];
        };
        
        
        // Обрабатываем все элементы в порядке: фигуры, картинки, таблицы, группы, фреймы
        
        // 1. Все текстовые фигуры (p:sp)
        const shapes = ensureArray(spTree['p:sp']);
        for (let shape of shapes) {
            const block = await parseShape(shape);
            if (block) {
                blocks.push(block);
            }
        }
        
        // 2. Все картинки (p:pic)
        const pictures = ensureArray(spTree['p:pic']);
        for (let picture of pictures) {
            const block = await parsePicture(picture, zip, slideNumber);
            if (block) blocks.push(block);
        }
        
        // 3. Все соединительные фигуры (p:cxnSp)
        const cxnShapes = ensureArray(spTree['p:cxnSp']);
        for (let cxnShape of cxnShapes) {
            const block = await parseShape(cxnShape);
            if (block) blocks.push(block);
        }
        
        // 4. Все таблицы (p:tbl)
        const tables = ensureArray(spTree['p:tbl']);
        for (let table of tables) {
            const block = await parseTable(table);
            if (block) blocks.push(block);
        }
        
        // 5. Все группы (p:grpSp)
        const groups = ensureArray(spTree['p:grpSp']);
        for (let group of groups) {
            const block = await parseGroup(group, zip, slideNumber);
            appendBlocks(blocks, block);
        }
        
        // 6. Все графические фреймы (p:graphicFrame)
        const graphicFrames = ensureArray(spTree['p:graphicFrame']);
        for (let gFrame of graphicFrames) {
            const block = await parseGraphicFrame(gFrame, zip, slideNumber);
            appendBlocks(blocks, block);
        }
        
        return blocks.filter(b => b !== null);
    } catch (error) {
        return [];
    }
}

/**
 * Парсит текстовую фигуру
 * @param {Object} shape - XML объект фигуры
 * @returns {Promise<Object|null>} - EditorJS блок или null
 */
async function parseShape(shape) {
    try {
        const fullText = extractTextsFromTxBody(shape['p:txBody'] && shape['p:txBody'][0]).join('\n').trim();

        if (!fullText) {
            return null;
        }
        
        
        return {
            type: 'paragraph',
            data: {
                text: fullText.replace(/\n/g, '<br>')
            }
        };
    } catch (error) {
        return null;
    }
}

/**
 * Парсит изображение
 * @param {Object} picture - XML объект изображения
 * @param {JSZip} zip - Объект ZIP архива
 * @param {number} slideNumber - Номер слайда
 * @returns {Promise<Object|null>} - EditorJS блок или null
 */
async function parsePicture(picture, zip, slideNumber = 1) {
    try {
        // Ищем blip в разных возможных путях структуры XML
        let blip = null;
        
        // Путь 1: прямой путь к a:blip
        if (picture['p:blipFill'] && picture['p:blipFill'][0] && picture['p:blipFill'][0]['a:blip']) {
            blip = picture['p:blipFill'][0]['a:blip'][0];
        }
        
        // Путь 2: через p:nvPicPr (если первый путь не сработал)
        if (!blip && picture['p:nvPicPr']) {
            // Пробуем еще раз через другой путь
            if (picture['p:blipFill'] && picture['p:blipFill'][0]) {
                const blipFill = picture['p:blipFill'][0];
                if (blipFill['a:blip']) {
                    blip = blipFill['a:blip'][0];
                }
            }
        }
        
        if (!blip || !blip['$'] || !blip['$']['r:embed']) {
            return null;
        }
        
        const rEmbed = blip['$']['r:embed'];
        
        // Получаем информацию о взаимосвязях для конкретного слайда
        const imageBuffer = await getRelationshipTarget(zip, rEmbed, slideNumber);
        
        if (!imageBuffer) {
            return null;
        }
        
        
        // Определяем тип изображения
        let mimeType = 'image/jpeg';
        if (imageBuffer.byteLength > 8) {
            const view = new Uint8Array(imageBuffer, 0, 8);
            // Проверяем PNG сигнатуру
            if (view[0] === 0x89 && view[1] === 0x50 && view[2] === 0x4E && view[3] === 0x47) {
                mimeType = 'image/png';
            }
            // Проверяем GIF сигнатуру
            else if (view[0] === 0x47 && view[1] === 0x49 && view[2] === 0x46) {
                mimeType = 'image/gif';
            }
        }
        
        // Преобразуем изображение в blob URL
        const imageBlob = new Blob([imageBuffer], { type: mimeType });
        const imageUrl = URL.createObjectURL(imageBlob);
        
        return {
            type: 'image',
            data: {
                url: imageUrl,
                caption: '',
                stretched: true,
                withBorder: false,
                withBackground: false,
                file: {
                    url: imageUrl
                }
            }
        };
    } catch (error) {
        return null;
    }
}

/**
 * Получает цель взаимосвязи из файла relationships
 * @param {JSZip} zip - Объект ZIP архива
 * @param {string} rId - ID взаимосвязи
 * @param {number} slideNumber - Номер слайда
 * @returns {Promise<ArrayBuffer|null>} - Содержимое файла или null
 */
async function getRelationshipTarget(zip, rId, slideNumber = 1) {
    try {
        // Правильный путь для relationships в PPTX: ppt/slides/_rels/slideX.xml.rels
        const relsPath = `ppt/slides/_rels/slide${slideNumber}.xml.rels`;
        
        let relsFile = zip.file(relsPath);
        
        if (!relsFile) {
            // Если файл не найден, ищем список всех доступных files
            const allFiles = Object.keys(zip.files);
            const relsFolderFiles = allFiles.filter(name =>
                name.match(/ppt\/slides\/_rels\/slide\d+\.xml\.rels/i)
            );
            
            if (relsFolderFiles.length === 0) {
                return null;
            }
            
            relsFile = zip.file(relsFolderFiles[0]);
        }
        
        const relsContent = await relsFile.async('string');
        
        const parsed = await parseStringPromise(relsContent);
        
        const relationships = parsed['Relationships']['Relationship'] || [];
        
        const relationship = relationships.find(rel => {
            const relId = rel['$'] && rel['$'].Id;
            return relId === rId;
        });
        
        if (!relationship) {
            relationships.forEach(rel => {
                if (rel['$'].Type.includes('image')) {
                }
            });
            return null;
        }
        
        const target = relationship['$'].Target;
        
        // Ищем медиафайл в архиве
        const allFiles = Object.keys(zip.files);
        
        // Строим правильный путь к медиафайлу
        let mediaPath;
        if (target.startsWith('../')) {
            // Относительный путь от папки slides
            mediaPath = `ppt/${target.replace(/^\.\.\//, '')}`;
        } else if (target.startsWith('media/')) {
            mediaPath = `ppt/${target}`;
        } else {
            mediaPath = `ppt/media/${target}`;
        }
        
        
        // Пытаемся найти файл
        let mediaFile = zip.file(mediaPath);
        
        if (!mediaFile) {
            // Пробуем альтернативные пути
            const fileName = target.split('/').pop();
            const alternativePaths = [
                `ppt/media/${fileName}`,
                `ppt/${target}`,
                target
            ];
            
            for (const altPath of alternativePaths) {
                mediaFile = zip.file(altPath);
                if (mediaFile) {
                    mediaPath = altPath;
                    break;
                }
            }
        } else {
        }
        
        if (!mediaFile) {
            // Выводим все медиафайлы для отладки
            return null;
        }
        
        const buffer = await mediaFile.async('arraybuffer');
        return buffer;
    } catch (error) {
        return null;
    }
}

/**
 * Парсит графический фрейм (может содержать диаграммы, SmartArt и другой контент)
 * @param {Object} graphicFrame - XML объект графического фрейма
 * @returns {Promise<Object|null>} - EditorJS блок или null
 */
async function parseGraphicFrame(graphicFrame, zip, slideNumber = 1) {
    try {
        const blocks = [];
        let frameText = '';
        
        // 1. Сначала ищем текст в p:txBody самого фрейма
        const txBody = graphicFrame['p:txBody'];
        if (txBody && txBody[0]) {
            let paragraphs = txBody[0]['a:p'];
            if (!Array.isArray(paragraphs)) {
                paragraphs = paragraphs ? [paragraphs] : [];
            }
            
            for (const para of paragraphs) {
                let paraText = '';
                for (const key in para) {
                    if (key === 'a:r') {
                        let runs = para[key];
                        if (!Array.isArray(runs)) {
                            runs = runs ? [runs] : [];
                        }
                        
                        for (const run of runs) {
                            const text = run['a:t'];
                            if (text) {
                                paraText += Array.isArray(text) ? text[0] : text;
                            }
                        }
                    }
                }
                if (paraText) frameText += paraText + ' ';
            }
        }
        
        // 2. Проверяем, является ли это диаграммой (SmartArt)
        const graphic = graphicFrame['a:graphic'];
        if (graphic && graphic[0]) {
            const graphicData = graphic[0]['a:graphicData'];
            if (graphicData && graphicData[0]) {
                const gData = graphicData[0];
                const graphicUri = gData['$'] && gData['$'].uri;
                
                // Если это диаграмма DrawingML
                if (graphicUri && graphicUri.includes('diagram')) {
                    
                    // Ищем dgm:relIds с ссылками на файлы диаграммы
                    const dgmRelIds = gData['dgm:relIds'] || [];
                    let dgmArray = Array.isArray(dgmRelIds) ? dgmRelIds : [dgmRelIds];
                    
                    for (const relIds of dgmArray) {
                        if (relIds && relIds['$']) {
                            const dmId = relIds['$']['r:dm']; // Ссылка на data файл диаграммы
                            if (dmId) {
                                const diagramText = await loadDiagramData(zip, dmId, slideNumber);
                                if (diagramText) {
                                    frameText += diagramText + ' ';
                                }
                            }
                        }
                    }
                }
            }
        }

        if (graphic && graphic[0] && graphic[0]['a:graphicData'] && graphic[0]['a:graphicData'][0]) {
            const gData = graphic[0]['a:graphicData'][0];
            if (gData['a:tbl']) {
                const tableBlock = await parseTable(gData);
                appendBlocks(blocks, tableBlock);
            }
        }
        
        frameText = frameText.trim();
        if (frameText) {
            blocks.unshift({
                type: 'paragraph',
                data: {
                    text: frameText
                }
            });
        }
        
        if (blocks.length === 0) return null;
        
        return blocks.length === 1 ? blocks[0] : blocks;
    } catch (error) {
        return null;
    }
}

/**
 * Загружает и парсит данные диаграммы (SmartArt)
 * @param {JSZip} zip - Объект ZIP архива
 * @param {string} rId - ID взаимосвязи на файл диаграммы
 * @param {number} slideNumber - Номер слайда
 * @returns {Promise<string|null>} - Извлеченный текст диаграммы
 */
async function loadDiagramData(zip, rId, slideNumber = 1) {
    try {
        // Получаем путь к файлу диаграммы из relationships
        const relsPath = `ppt/slides/_rels/slide${slideNumber}.xml.rels`;
        const relsFile = zip.file(relsPath);
        
        if (!relsFile) {
            return null;
        }
        
        const relsContent = await relsFile.async('string');
        const parsed = await parseStringPromise(relsContent);
        
        const relationships = parsed['Relationships']['Relationship'] || [];
        let relArray = Array.isArray(relationships) ? relationships : [relationships];
        
        // Ищем relationship с нужным ID
        const relationship = relArray.find(rel => rel['$'] && rel['$'].Id === rId);
        
        if (!relationship) {
            return null;
        }
        
        const target = relationship['$'].Target;
        const diagramPath = normalizePath(`ppt/slides/${target}`);
        
        
        const diagramFile = zip.file(diagramPath);
        if (!diagramFile) {
            return null;
        }
        
        const diagramContent = await diagramFile.async('string');
        const diagramParsed = await parseStringPromise(diagramContent);
        
        // Парсим данные диаграммы - текст может быть в разных местах
        let diagramText = '';
        
        // Структура diagram data XML может быть разной, но текст часто в <a:t>
        const extractTextFromDiagram = (obj) => {
            if (!obj) return '';
            
            let text = '';
            
            // Если это строка - это текст
            if (typeof obj === 'string') {
                return obj.trim();
            }
            
            // Если это массив - обрабатываем каждый элемент
            if (Array.isArray(obj)) {
                for (const item of obj) {
                    text += extractTextFromDiagram(item) + ' ';
                }
                return text;
            }
            
            // Если это объект - ищем текстовые элементы
            if (typeof obj === 'object') {
                // Проверяем ключ 'a:t' (DrawingML текст)
                if (obj['a:t']) {
                    const t = obj['a:t'];
                    text += (Array.isArray(t) ? t[0] : t) + ' ';
                }
                
                // Проверяем 'a:r' (text runs)
                if (obj['a:r']) {
                    let runs = obj['a:r'];
                    runs = Array.isArray(runs) ? runs : [runs];
                    for (const run of runs) {
                        if (run['a:t']) {
                            const t = run['a:t'];
                            text += (Array.isArray(t) ? t[0] : t) + ' ';
                        }
                    }
                }
                
                // Проверяем 'a:p' (paragraphs)
                if (obj['a:p']) {
                    let paras = obj['a:p'];
                    paras = Array.isArray(paras) ? paras : [paras];
                    for (const para of paras) {
                        text += extractTextFromDiagram(para) + ' ';
                    }
                }
                
                // Рекурсивно ищем текст во всех остальных свойствах
                for (const key in obj) {
                    if (key.startsWith('$') || key === 'a:t' || key === 'a:r' || key === 'a:p') {
                        continue; // Уже обработали
                    }
                    if (key.startsWith('a:') || key.startsWith('dgm:') || key.startsWith('p:')) {
                        text += extractTextFromDiagram(obj[key]) + ' ';
                    }
                }
            }
            
            return text;
        };
        
        // Начинаем с корневого элемента
        const rootKeys = Object.keys(diagramParsed);
        for (const rootKey of rootKeys) {
            diagramText += extractTextFromDiagram(diagramParsed[rootKey]) + ' ';
        }
        
        diagramText = diagramText.trim().replace(/\s+/g, ' ');
        
        if (diagramText) {
            return diagramText;
        }
        
        return null;
    } catch (error) {
        return null;
    }
}

/**
 * Парсит таблицу и извлекает текст из всех ячеек
 * @param {Object} table - XML объект таблицы
 * @returns {Promise<Object|null>} - EditorJS блок или null
 */
async function parseTable(table) {
    try {
        let tableText = '';
        const tblBody = table['a:tbl'] ? table['a:tbl'][0] : table;
        if (!tblBody) return null;
        
        // Обработка строк как массива или объекта
        let rows = tblBody['a:tr'];
        if (!Array.isArray(rows)) {
            rows = rows ? [rows] : [];
        }
        
        for (const row of rows) {
            // Обработка ячеек как массива или объекта
            let cells = row['a:tc'];
            if (!Array.isArray(cells)) {
                cells = cells ? [cells] : [];
            }
            
            const rowTexts = [];
            
            for (const cell of cells) {
                const txBody = cell['a:txBody'];
                if (txBody && txBody[0]) {
                    // Обработка параграфов как массива или объекта
                    let paragraphs = txBody[0]['a:p'];
                    if (!Array.isArray(paragraphs)) {
                        paragraphs = paragraphs ? [paragraphs] : [];
                    }
                    
                    let cellText = '';
                    
                    for (const para of paragraphs) {
                        let paraText = '';
                        for (const key in para) {
                            if (key === 'a:r') {
                                let runs = para[key];
                                if (!Array.isArray(runs)) {
                                    runs = runs ? [runs] : [];
                                }
                                
                                for (const run of runs) {
                                    const text = run['a:t'];
                                    if (text) {
                                        paraText += Array.isArray(text) ? text[0] : text;
                                    }
                                }
                            }
                        }
                        if (paraText) cellText += paraText + ' ';
                    }
                    
                    rowTexts.push(cellText.trim());
                }
            }
            
            if (rowTexts.length > 0) {
                tableText += rowTexts.join(' | ') + '\n';
            }
        }
        
        tableText = tableText.trim();
        if (!tableText) return null;
        
        
        return {
            type: 'table',
            data: {
                content: tableText.split('\n').map((row) => row.split(' | ')),
                stretched: true,
                withHeadings: false
            }
        };
    } catch (error) {
        return null;
    }
}

/**
 * Парсит группу фигур и извлекает текст из всех элементов
 * @param {Object} group - XML объект группы
 * @returns {Promise<Object|null>} - EditorJS блок или null
 */
async function parseGroup(group, zip, slideNumber = 1) {
    try {
        const blocks = [];
        const ensureArray = (val) => (!val ? [] : Array.isArray(val) ? val : [val]);

        for (const shape of ensureArray(group['p:sp'])) {
            appendBlocks(blocks, await parseShape(shape));
        }

        for (const cxn of ensureArray(group['p:cxnSp'])) {
            appendBlocks(blocks, await parseShape(cxn));
        }

        for (const picture of ensureArray(group['p:pic'])) {
            appendBlocks(blocks, await parsePicture(picture, zip, slideNumber));
        }

        for (const gFrame of ensureArray(group['p:graphicFrame'])) {
            appendBlocks(blocks, await parseGraphicFrame(gFrame, zip, slideNumber));
        }

        for (const nestedGroup of ensureArray(group['p:grpSp'])) {
            appendBlocks(blocks, await parseGroup(nestedGroup, zip, slideNumber));
        }

        return blocks.length ? blocks : null;
    } catch (error) {
        return null;
    }
}

function appendBlocks(target, blockOrBlocks) {
    if (!blockOrBlocks) return;
    if (Array.isArray(blockOrBlocks)) {
        for (const block of blockOrBlocks) {
            if (block) target.push(block);
        }
        return;
    }
    target.push(blockOrBlocks);
}

function extractTextsFromTxBody(txBody) {
    if (!txBody || !txBody['a:p']) return [];

    const paragraphs = Array.isArray(txBody['a:p']) ? txBody['a:p'] : [txBody['a:p']];
    return paragraphs
        .map((para) => extractParagraphText(para).trim())
        .filter(Boolean);
}

function extractParagraphText(para) {
    if (!para) return '';
    let text = '';

    const runs = para['a:r'] ? (Array.isArray(para['a:r']) ? para['a:r'] : [para['a:r']]) : [];
    for (const run of runs) {
        if (run['a:t']) text += readTextNode(run['a:t']);
    }

    const fields = para['a:fld'] ? (Array.isArray(para['a:fld']) ? para['a:fld'] : [para['a:fld']]) : [];
    for (const field of fields) {
        const fieldRuns = field['a:r'] ? (Array.isArray(field['a:r']) ? field['a:r'] : [field['a:r']]) : [];
        for (const run of fieldRuns) {
            if (run['a:t']) text += readTextNode(run['a:t']);
        }
    }

    if (para['a:br']) {
        const breaks = Array.isArray(para['a:br']) ? para['a:br'].length : 1;
        text += '\n'.repeat(Math.max(1, breaks));
    }

    if (!text && para['a:t']) {
        text = readTextNode(para['a:t']);
    }

    return text;
}

function readTextNode(node) {
    if (Array.isArray(node)) {
        return node.map((item) => readTextNode(item)).join('');
    }
    if (typeof node === 'string') return node;
    if (node && typeof node === 'object' && typeof node._ === 'string') return node._;
    return '';
}

function normalizePath(path) {
    const parts = String(path).replace(/\\/g, '/').split('/');
    const result = [];
    for (const part of parts) {
        if (!part || part === '.') continue;
        if (part === '..') {
            result.pop();
            continue;
        }
        result.push(part);
    }
    return result.join('/');
}

/**
 * Валидирует PPTX файл перед импортом
 * @param {File} file - Файл для проверки
 * @returns {boolean} - true если файл валиден
 */
export function validatePptxFile(file) {
    if (!file) {
        throw new Error('Файл не выбран');
    }
    
    if (file.type !== 'application/vnd.openxmlformats-officedocument.presentationml.presentation' &&
        !file.name.toLowerCase().endsWith('.pptx')) {
        throw new Error('Это не PPTX файл. Пожалуйста, выберите файл с расширением .pptx');
    }
    
    const maxSize = 50 * 1024 * 1024; // 50MB
    if (file.size > maxSize) {
        throw new Error(`Размер файла не должен превышать 50 MB. Текущий размер: ${(file.size / 1024 / 1024).toFixed(2)} MB`);
    }
    
    return true;
}
