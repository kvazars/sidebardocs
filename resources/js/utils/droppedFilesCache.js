/**
 * Кэш содержимого перетащенных в редактор файлов.
 *
 * File-объекты из dataTransfer часто ссылаются на временные файлы
 * (перетаскивание из Telegram, Outlook, WinRAR/7-Zip, панели загрузок браузера).
 * К моменту, когда Editor.js инициирует загрузку (асинхронно, через
 * requestIdleCallback), такой файл может быть уже недоступен, и браузер
 * обрывает запрос с net::ERR_FAILED.
 *
 * Поэтому содержимое читается в память сразу, во время события drop,
 * а инструменты загружают уже прочитанную копию.
 */
const droppedFiles = new WeakMap();

/**
 * Читает все файлы из dataTransfer в память. Вызывать синхронно
 * в обработчике события drop.
 *
 * @param {DataTransfer} dataTransfer - dataTransfer события drop
 */
export function cacheDroppedFiles(dataTransfer) {
    if (!dataTransfer || !dataTransfer.files) {
        return;
    }

    Array.from(dataTransfer.files).forEach((file) => {
        if (droppedFiles.has(file)) {
            return;
        }

        droppedFiles.set(
            file,
            (async () => {
                const buffer = await file.arrayBuffer();
                return new File([buffer], file.name, {
                    type: file.type,
                    lastModified: file.lastModified,
                });
            })()
        );
    });
}

/**
 * Возвращает in-memory копию перетащенного файла, если она была
 * закэширована при drop, иначе — исходный файл как есть.
 *
 * @param {File} file - файл из paste-события Editor.js
 * @returns {Promise<File>}
 */
export async function resolveDroppedFile(file) {
    const cached = droppedFiles.get(file);

    if (!cached) {
        return file;
    }

    try {
        return await cached;
    } catch (error) {
        // не удалось прочитать в память — пробуем загрузить исходный
        return file;
    }
}
