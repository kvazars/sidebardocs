<template>
    <div class="test-management">
        <!-- Уведомление об ошибке -->
        <div
            v-if="error"
            class="alert alert-danger alert-dismissible fade show mb-4"
            role="alert"
        >
            <i class="bi bi-exclamation-triangle"></i> {{ error }}
            <button
                type="button"
                class="btn-close"
                @click="error = ''"
            ></button>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><i class="bi bi-gear"></i> Управление тестами</h2>
            <div>
                <button
                    @click="showImportModal"
                    class="btn btn-success me-2"
                    :disabled="loading"
                >
                    <i class="bi bi-upload"></i> Импорт теста
                </button>
                <button
                    @click="editTest(null)"
                    class="btn btn-primary"
                    :disabled="loading"
                >
                    <i class="bi bi-plus-circle"></i> Создать новый тест
                </button>
            </div>
        </div>

        <!-- Загрузка -->
        <div
            v-if="loading"
            class="d-flex justify-content-center align-items-center py-5"
        >
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
        </div>

        <div
            v-else-if="tests.length === 0"
            class="alert alert-info text-center"
        >
            <i class="bi bi-info-circle"></i> Нет созданных тестов. Создайте
            первый тест!
        </div>

        <div v-else class="row">
            <div
                v-for="test in tests"
                :key="test.id"
                class="col-md-6 col-lg-4 mb-4"
            >
                <div class="test-management-card card h-100">
                    <div
                        class="card-header bg-light d-flex justify-content-between align-items-center"
                    >
                        <h6 class="mb-0 text-truncate" :title="test.title">
                            {{ test.title }}
                        </h6>
                        <span class="badge bg-secondary">{{
                            test.questions.length
                        }}</span>
                    </div>

                    <div class="card-body">
                        <p
                            class="card-text text-muted small"
                            :title="test.description"
                        >
                            {{ test.description || "Описание отсутствует" }}
                        </p>

                        <div class="test-meta mb-3">
                            <div class="row text-center">
                                <div class="col-4">
                                    <small class="text-muted">Время</small>
                                    <div class="fw-bold">
                                        {{ test.timeLimit }} мин
                                    </div>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted">Баллы</small>
                                    <div class="fw-bold">
                                        {{ getTotalPoints(test) }}
                                    </div>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted">Вопросы</small>
                                    <div class="fw-bold">
                                        {{ test.questions.length }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="test-settings mb-3">
                            <div
                                v-if="test.settings.requireUserName"
                                class="badge bg-info me-1 mb-1"
                            >
                                <i class="bi bi-person"></i> Имя
                            </div>
                            <div
                                v-if="test.settings.shuffleQuestions"
                                class="badge bg-primary me-1 mb-1"
                            >
                                <i class="bi bi-shuffle"></i> Вопросы
                            </div>
                            <div
                                v-if="test.settings.shuffleAnswers"
                                class="badge bg-success me-1 mb-1"
                            >
                                <i class="bi bi-shuffle"></i> Ответы
                            </div>
                            <div class="badge bg-secondary me-1 mb-1">
                                {{ getQuestionTypesCount(test) }} типов
                            </div>
                        </div>

                        <div class="test-stats small text-muted">
                            <div>
                                Создан:
                                {{ formatDate(test.created_at || test.id) }}
                            </div>
                            <div>
                                Время прохождения: ~{{
                                    calculateTestDuration(test)
                                }}
                                мин
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="btn-group w-100">
                            <button
                                @click="editTest(test.id)"
                                class="btn btn-outline-primary btn-sm"
                                :disabled="actionLoading"
                            >
                                <i class="bi bi-pencil"></i> Редактировать
                            </button>
                            <button
                                @click="exportTest(test)"
                                class="btn btn-outline-success btn-sm"
                                :disabled="actionLoading"
                            >
                                <i class="bi bi-download"></i> Экспорт
                            </button>
                            <button
                                @click="confirmDelete(test)"
                                class="btn btn-outline-danger btn-sm"
                                :disabled="actionLoading"
                            >
                                <i class="bi bi-trash"></i> Удалить
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Статистика -->
        <div v-if="tests.length > 0" class="card mt-4">
            <div class="card-header bg-light">
                <h6 class="mb-0">Общая статистика</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <h4 class="text-primary">{{ tests.length }}</h4>
                        <small class="text-muted">Всего тестов</small>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-success">{{ totalQuestions }}</h4>
                        <small class="text-muted">Всего вопросов</small>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-info">{{ totalPoints }}</h4>
                        <small class="text-muted">Всего баллов</small>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-warning">{{ averageQuestions }}</h4>
                        <small class="text-muted">Вопросов в среднем</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно импорта -->
        <div class="modal fade" id="importModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-upload"></i> Импорт теста
                        </h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label"
                                >Выберите JSON файл теста:</label
                            >
                            <input
                                type="file"
                                ref="fileInput"
                                @change="handleFileSelect"
                                accept=".json,application/json"
                                class="form-control"
                                :disabled="actionLoading"
                            />
                            <div class="form-text">
                                Выберите файл теста в формате JSON, который был
                                экспортирован из системы
                            </div>
                        </div>

                        <!-- Предпросмотр теста -->
                        <div v-if="importPreview" class="import-preview card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Предпросмотр теста</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Название:</strong>
                                    {{ importPreview.title }}
                                </div>
                                <div class="mb-2">
                                    <strong>Описание:</strong>
                                    {{
                                        importPreview.description ||
                                        "Не указано"
                                    }}
                                </div>
                                <div class="mb-2">
                                    <strong>Вопросов:</strong>
                                    {{ importPreview.questions?.length || 0 }}
                                </div>
                                <div class="mb-2">
                                    <strong>Время:</strong>
                                    {{ importPreview.timeLimit }} минут
                                </div>
                                <div v-if="importPreview.settings" class="mb-2">
                                    <strong>Настройки:</strong>
                                    <span
                                        v-if="
                                            importPreview.settings
                                                .requireUserName
                                        "
                                        class="badge bg-info ms-1"
                                        >Имя</span
                                    >

                                    <span
                                        v-if="
                                            importPreview.settings
                                                .shuffleQuestions
                                        "
                                        class="badge bg-primary ms-1"
                                        >Перемеш. вопросы</span
                                    >
                                    <span
                                        v-if="
                                            importPreview.settings
                                                .shuffleAnswers
                                        "
                                        class="badge bg-success ms-1"
                                        >Перемеш. ответы</span
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Ошибки валидации -->
                        <div
                            v-if="importErrors.length > 0"
                            class="alert alert-danger mt-3"
                        >
                            <h6 class="alert-heading">Ошибки в файле теста:</h6>
                            <ul class="mb-0">
                                <li v-for="error in importErrors" :key="error">
                                    {{ error }}
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal"
                            :disabled="actionLoading"
                        >
                            Отмена
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            @click="importTest"
                            :disabled="
                                !importPreview ||
                                importErrors.length > 0 ||
                                actionLoading
                            "
                        >
                            <span
                                v-if="actionLoading"
                                class="spinner-border spinner-border-sm me-2"
                                role="status"
                            ></span>
                            <i v-else class="bi bi-check-circle"></i>
                            Импортировать тест
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { importTest, exportTest } from "../utils/storage.js";
import bootstrap from "bootstrap/dist/js/bootstrap.bundle.min.js";
export default {
    name: "TestManagement",
    props: ["tests", "datasend", "loadData"],

    emits: ["edit-test", "test-deleted", "test-imported", "error"],
    data() {
        return {
            importPreview: null,
            importErrors: [],
            selectedFile: null,
            loading: false,
            actionLoading: false,
            error: "",
        };
    },
    computed: {
        totalQuestions() {
            return this.tests.reduce(
                (sum, test) => sum + test.questions.length,
                0
            );
        },
        totalPoints() {
            return this.tests.reduce(
                (sum, test) => sum + this.getTotalPoints(test),
                0
            );
        },
        averageQuestions() {
            return this.tests.length > 0
                ? Math.round(this.totalQuestions / this.tests.length)
                : 0;
        },
    },
    methods: {
        editTest(testId) {
            this.$emit("edit-test", testId);
        },

        async confirmDelete(test) {
            if (
                confirm(
                    `Вы уверены, что хотите удалить тест "${test.title}"? Все результаты этого теста также будут удалены.`
                )
            ) {
                this.actionLoading = true;
                this.error = "";

                this.datasend(`tests/${test.id}`, "DELETE", this.test).then(
                    (response) => {
                        this.loadData();
                        this.showToast(response.message, "success");
                    }
                );
            }
        },

        getTotalPoints(test) {
            return test.questions.reduce(
                (sum, q) => sum + (parseInt(q.points) || 0),
                0
            );
        },

        getQuestionTypesCount(test) {
            const types = new Set(test.questions.map((q) => q.type));
            return types.size;
        },

        formatDate(timestamp) {
            if (!timestamp) return "Неизвестно";
            // Если timestamp - это ID (число), преобразуем в дату
            if (typeof timestamp === "number" && timestamp > 1000000) {
                return new Date(timestamp).toLocaleDateString("ru-RU");
            }
            // Если это строка даты
            return new Date(timestamp).toLocaleDateString("ru-RU");
        },

        calculateTestDuration(test) {
            return Math.ceil(test.questions.length * 1 + 2);
        },

        async exportTest(test) {
            this.actionLoading = true;
            this.error = "";

            try {
                const blob = await exportTest(test);
                const url = URL.createObjectURL(blob);
                const link = document.createElement("a");
                link.href = url;
                link.download = `test-${test.title || "export"}.json`;
                link.click();
                URL.revokeObjectURL(url);
                this.showToast("Тест успешно экспортирован!", "success");
            } catch (error) {
                this.error = error.message;
                this.$emit("error", error.message);
            } finally {
                this.actionLoading = false;
            }
        },

        showImportModal() {
            this.importPreview = null;
            this.importErrors = [];
            this.selectedFile = null;
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = "";
            }

            this.$nextTick(() => {
                const modalElement = document.getElementById("importModal");
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                }
            });
        },

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.selectedFile = file;
            this.importPreview = null;
            this.importErrors = [];

            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const testData = JSON.parse(e.target.result);
                    this.validateTestFile(testData);
                    this.importPreview = testData;
                } catch (error) {
                    this.importErrors = [
                        "Ошибка чтения файла: неверный формат JSON",
                    ];
                    console.error("Ошибка импорта:", error);
                }
            };
            reader.readAsText(file);
        },

        validateTestFile(testData) {
            this.importErrors = [];

            const requiredFields = ["title", "timeLimit", "questions"];
            requiredFields.forEach((field) => {
                if (!testData[field]) {
                    this.importErrors.push(
                        `Отсутствует обязательное поле: ${field}`
                    );
                }
            });

            if (testData.title && typeof testData.title !== "string") {
                this.importErrors.push('Поле "title" должно быть строкой');
            }

            if (testData.timeLimit && typeof testData.timeLimit !== "number") {
                this.importErrors.push('Поле "timeLimit" должно быть числом');
            }

            if (testData.questions && !Array.isArray(testData.questions)) {
                this.importErrors.push('Поле "questions" должно быть массивом');
            }

            if (testData.questions && Array.isArray(testData.questions)) {
                testData.questions.forEach((question, index) => {
                    if (!question.text) {
                        this.importErrors.push(
                            `Вопрос ${index + 1}: отсутствует текст вопроса`
                        );
                    }

                    if (!question.type) {
                        this.importErrors.push(
                            `Вопрос ${index + 1}: отсутствует тип вопроса`
                        );
                    }

                    if (
                        !question.points ||
                        typeof question.points !== "number"
                    ) {
                        this.importErrors.push(
                            `Вопрос ${index + 1}: неверные баллы`
                        );
                    }
                });
            }
        },

        async importTest() {
            if (!this.importPreview || this.importErrors.length > 0) return;

            this.actionLoading = true;
            this.error = "";

            try {
                await importTest(this.selectedFile);
                this.$emit("test-imported");

                const modalElement = document.getElementById("importModal");
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                }

                this.showToast("Тест успешно импортирован!", "success");
            } catch (error) {
                this.error = error.message;
                this.$emit("error", error.message);
            } finally {
                this.actionLoading = false;
            }
        },

        showToast(message, type = "info") {
            const toast = document.createElement("div");
            toast.className = `alert alert-${type} alert-dismissible fade show`;
            toast.innerHTML = `
          ${message}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
            toast.style.position = "fixed";
            toast.style.top = "20px";
            toast.style.right = "20px";
            toast.style.zIndex = "1060";
            document.body.appendChild(toast);

            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 3000);
        },
    },
};
</script>

<style scoped>
.test-management-card {
    transition: all 0.3s ease;
}

.test-management-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border-color: #0d6efd;
}

.test-meta {
    border-top: 1px solid #e9ecef;
    border-bottom: 1px solid #e9ecef;
    padding: 10px 0;
}

.test-settings {
    min-height: 40px;
}

.test-stats {
    font-size: 0.8rem;
}

.btn-group .btn {
    flex: 1;
}

.import-preview {
    max-height: 300px;
    overflow-y: auto;
}
</style>
