// Базовый URL API - замените на ваш реальный URL
const API_BASE_URL = window.location.origin + "/api";
// Общие настройки для запросов
const defaultHeaders = {
    "Content-Type": "application/json",
    Accept: "application/json",
};

// Вспомогательная функция для выполнения запросов
async function apiRequest(endpoint, options = {}) {
    const url = `${API_BASE_URL}${endpoint}`;

    const config = {
        headers: { ...defaultHeaders, ...options.headers },
        ...options,
    };

    if (config.body && typeof config.body === "object") {
        config.body = JSON.stringify(config.body);
    }

    try {
        const response = await fetch(url, config);

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(
                errorData.message || `HTTP error! status: ${response.status}`
            );
        }

        return await response.json();
    } catch (error) {
        console.error("API request failed:", error);
        throw new Error(
            "Не удалось подключиться к серверу. Проверьте подключение и попробуйте снова."
        );
    }
}

// ==================== ТЕСТЫ ====================

/**
 * Загрузка всех тестов
 * @returns {Promise<Array>} Массив тестов
 */
export const loadTests = async () => {
    try {
        const response = await apiRequest("/tests");
        return response.data || response;
    } catch (error) {
        console.error("Ошибка загрузки тестов:", error);
        throw error;
    }
};

/**
 * Сохранение теста (создание или обновление)
 * @param {Object} test Данные теста
 * @returns {Promise<Object>} Сохраненный тест
 */
export const saveTest = async (test) => {
    try {
        // Определяем, новый это тест или существующий
        const isNewTest = !test.id || test.id > 1000000;

        if (isNewTest) {
            // Новый тест
            const response = await apiRequest("/tests", {
                method: "POST",
                body: test,
            });
            return response.data;
        } else {
            // Обновление существующего теста
            const response = await apiRequest(`/tests/${test.id}`, {
                method: "PUT",
                body: test,
            });
            return response.data;
        }
    } catch (error) {
        console.error("Ошибка сохранения теста:", error);
        throw error;
    }
};

/**
 * Удаление теста
 * @param {number} testId ID теста
 * @returns {Promise<boolean>} true при успешном удалении
 */
export const deleteTest = async (testId) => {
    try {
        await apiRequest(`/tests/${testId}`, {
            method: "DELETE",
        });
        return true;
    } catch (error) {
        console.error("Ошибка удаления теста:", error);
        throw error;
    }
};

/**
 * Получение теста по ID
 * @param {number} testId ID теста
 * @returns {Promise<Object>} Данные теста
 */
export const getTest = async (testId) => {
    try {
        const response = await apiRequest(`/tests/${testId}`);
        return response.data;
    } catch (error) {
        console.error("Ошибка загрузки теста:", error);
        throw error;
    }
};

// ==================== РЕЗУЛЬТАТЫ ====================

/**
 * Загрузка всех результатов
 * @returns {Promise<Array>} Массив результатов
 */
export const loadResults = async () => {
    try {
        const response = await apiRequest("/results");
        return response.data || response;
    } catch (error) {
        console.error("Ошибка загрузки результатов:", error);
        throw error;
    }
};

/**
 * Сохранение результата теста
 * @param {Object} result Данные результата
 * @returns {Promise<Object>} Сохраненный результат
 */
export const saveResult = async (result) => {
    try {
        const response = await apiRequest("/results", {
            method: "POST",
            body: result,
        });
        return response.data;
    } catch (error) {
        console.error("Ошибка сохранения результата:", error);
        throw error;
    }
};

/**
 * Получение результатов конкретного теста
 * @param {number} testId ID теста
 * @returns {Promise<Array>} Массив результатов теста
 */
export const getTestResults = async (testId) => {
    try {
        const response = await apiRequest(`/tests/${testId}/results`);
        return response.data || response;
    } catch (error) {
        console.error("Ошибка загрузки результатов теста:", error);
        throw error;
    }
};

// ==================== ИМПОРТ/ЭКСПОРТ ====================

/**
 * Импорт теста из JSON файла
 * @param {File} file Файл для импорта
 * @returns {Promise<Object>} Импортированный тест
 */
export const importTest = async (file) => {
    try {
        const formData = new FormData();
        formData.append("file", file);

        const response = await fetch(`${API_BASE_URL}/tests/import`, {
            method: "POST",
            headers: {
                Accept: "application/json",
            },
            body: formData,
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(
                errorData.message || `HTTP error! status: ${response.status}`
            );
        }

        const data = await response.json();
        return data.data;
    } catch (error) {
        console.error("Ошибка импорта теста:", error);
        throw new Error(
            error.message ||
                "Не удалось импортировать тест. Проверьте формат файла."
        );
    }
};

/**
 * Экспорт теста в JSON файл
 * @param {Object} test Тест для экспорта
 * @returns {Promise<Blob>} Blob с данными теста
 */
export const exportTest = async (test) => {
    try {
        const response = await fetch(`${API_BASE_URL}/tests/${test.id}/export`);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const blob = await response.blob();
        return blob;
    } catch (error) {
        console.error("Ошибка экспорта теста:", error);
        throw new Error("Не удалось экспортировать тест.");
    }
};

// Экспорт базового URL для использования в других частях приложения
export { API_BASE_URL };
