/**
 * Отображение размера файла из уже сохранённого числа байт (без сетевых запросов).
 */
export function formatHumanFileSize(bytes) {
    const n = typeof bytes === "string" ? parseInt(bytes, 10) : Number(bytes);
    if (!Number.isFinite(n) || n < 0) {
        return "";
    }
    if (n < 1024) {
        return `${n}\u00a0Б`;
    }
    const kb = n / 1024;
    if (kb < 1024) {
        const t = kb >= 10 ? kb.toFixed(0) : kb.toFixed(1);
        return `${t}\u00a0КБ`;
    }
    const mb = kb / 1024;
    if (mb < 1024) {
        const t = mb >= 10 ? mb.toFixed(0) : mb.toFixed(1);
        return `${t}\u00a0МБ`;
    }
    const gb = mb / 1024;
    const t = gb >= 10 ? gb.toFixed(0) : gb.toFixed(1);
    return `${t}\u00a0ГБ`;
}
