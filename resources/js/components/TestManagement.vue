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

        <div
            class="d-flex justify-content-between align-items-center mb-4 mt-2"
            v-if="tests.length === 0"
        >
            <div>
                <button
                    @click="showImportModal"
                    class="btn btn-success me-2 text-white"
                >
                    <i class="bi bi-upload"></i> Импорт теста
                </button>
                <button @click="editTest(null)" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Создать новый тест
                </button>
            </div>
        </div>

        <div v-if="tests.length === 0" class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i> Нет созданных тестов. Создайте
            первый тест!
        </div>

        <div v-else class="row">
            <div
                v-for="test in tests"
                :key="test.id"
                class="col-md-12 col-lg-12 mt-4"
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
                            >
                                <i class="bi bi-pencil"></i> Редактировать
                            </button>
                            <button
                                @click="showExportModal(test)"
                                class="btn btn-outline-success btn-sm"
                            >
                                <i class="bi bi-download"></i> Экспорт
                            </button>
                            <button
                                @click="confirmDelete(test)"
                                class="btn btn-outline-danger btn-sm"
                            >
                                <i class="bi bi-trash"></i> Удалить
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно импорта -->
        <!-- <div class="modal fade" id="importModal" tabindex="-1">
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
                                
                            />
                            <div class="form-text">
                                Выберите файл теста в формате JSON, который был
                                экспортирован из системы
                            </div>
                        </div>

                      
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
        </div> -->

        <!-- Модальное окно экспорта -->
        <div class="modal fade" id="exportModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-download"></i> Экспорт теста
                        </h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">
                            Выберите формат для экспорта теста "
                            <strong>{{ currentExportTest?.title }}</strong
                            >":
                        </p>

                        <div class="list-group">
                            <!-- JSON формат -->
                            <button
                                @click="exportJson"
                                class="list-group-item list-group-item-action d-flex align-items-center"
                            >
                                <div class="me-3">
                                    <i
                                        class="bi bi-file-code fs-4 text-primary"
                                    ></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold">JSON формат</div>
                                    <small class="text-muted">
                                        Для импорта обратно в систему или обмена
                                        между системами
                                    </small>
                                </div>
                                <div>
                                    <i class="bi bi-chevron-right"></i>
                                </div>
                            </button>

                            <!-- Word формат -->
                            <button
                                @click="exportWord"
                                class="list-group-item list-group-item-action d-flex align-items-center"
                            >
                                <div class="me-3">
                                    <i
                                        class="bi bi-file-word fs-4 text-info"
                                    ></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold">Microsoft Word</div>
                                    <small class="text-muted">
                                        Для печати, редактирования в Word или
                                        отправки преподавателям
                                    </small>
                                </div>
                                <div>
                                    <i class="bi bi-chevron-right"></i>
                                </div>
                            </button>
                        </div>

                        <!-- Выбор типа экспорта Word -->
                        <div v-if="showWordOptions" class="mt-4">
                            <h6 class="mb-3">Настройки экспорта Word:</h6>
                            <div class="form-check mb-2">
                                <input
                                    v-model="wordExportType"
                                    class="form-check-input"
                                    type="radio"
                                    id="withAnswers"
                                    value="withAnswers"
                                />
                                <label
                                    class="form-check-label"
                                    for="withAnswers"
                                >
                                    <strong>С правильными ответами</strong>
                                    <div class="form-text">
                                        Правильные ответы будут выделены
                                        <strong>жирным</strong> шрифтом в самом
                                        вопросе
                                    </div>
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input
                                    v-model="wordExportType"
                                    class="form-check-input"
                                    type="radio"
                                    id="withoutAnswers"
                                    value="withoutAnswers"
                                />
                                <label
                                    class="form-check-label"
                                    for="withoutAnswers"
                                >
                                    <strong>Без правильных ответов</strong>
                                    <div class="form-text">
                                        Версия для студентов (правильные ответы
                                        скрыты)
                                    </div>
                                </label>
                            </div>
                            <div class="form-check">
                                <input
                                    v-model="wordExportType"
                                    class="form-check-input"
                                    type="radio"
                                    id="separateAnswers"
                                    value="separateAnswers"
                                />
                                <label
                                    class="form-check-label"
                                    for="separateAnswers"
                                >
                                    <strong
                                        >С ответами на отдельной
                                        странице</strong
                                    >
                                    <div class="text-warning small">
                                        <i
                                            class="bi bi-exclamation-triangle"
                                        ></i>
                                        Только для преподавателей!
                                    </div>
                                </label>
                            </div>

                            <!-- Кнопка подтверждения экспорта Word -->
                            <div class="d-flex gap-2 mt-4">
                                <button
                                    @click="cancelWordOptions"
                                    class="btn btn-outline-secondary"
                                >
                                    <i class="bi bi-arrow-left"></i> Назад
                                </button>
                                <button
                                    @click="performWordExport"
                                    class="btn btn-primary flex-grow-1"
                                >
                                    <i class="bi bi-download me-2"></i>
                                    Экспортировать в Word
                                </button>
                            </div>
                        </div>

                        <!-- Информация о тесте -->
                        <div
                            class="card mt-4"
                            :class="{ 'mt-3': !showWordOptions }"
                        >
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted"
                                            >Вопросов:</small
                                        >
                                        <div class="fw-bold">
                                            {{
                                                currentExportTest?.questions
                                                    ?.length || 0
                                            }}
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted"
                                            >Баллов:</small
                                        >
                                        <div class="fw-bold">
                                            {{
                                                currentExportTest
                                                    ? getTotalPoints(
                                                          currentExportTest
                                                      )
                                                    : 0
                                            }}
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <small class="text-muted">Время:</small>
                                        <div class="fw-bold">
                                            {{
                                                currentExportTest?.timeLimit ||
                                                0
                                            }}
                                            минут
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted"
                                            >Типов вопросов:</small
                                        >
                                        <div class="fw-bold">
                                            {{
                                                currentExportTest
                                                    ? getQuestionTypesCount(
                                                          currentExportTest
                                                      )
                                                    : 0
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal"
                        >
                            Отмена
                        </button>
                        <div v-if="!showWordOptions" class="text-muted">
                            <span
                                class="spinner-border spinner-border-sm me-2"
                            ></span>
                            Экспорт выполняется...
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
                        <!-- Выбор формата файла -->
                        <div class="mb-3">
                            <label class="form-label"
                                >Выберите формат импорта:</label
                            >
                            <div class="form-check">
                                <input
                                    v-model="importFormat"
                                    type="radio"
                                    value="json"
                                    class="form-check-input"
                                    id="formatJson"
                                />
                                <label
                                    class="form-check-label"
                                    for="formatJson"
                                >
                                    JSON формат (система тестирования)
                                </label>
                            </div>
                            <div class="form-check">
                                <input
                                    v-model="importFormat"
                                    type="radio"
                                    value="xml"
                                    class="form-check-input"
                                    id="formatXml"
                                />
                                <label class="form-check-label" for="formatXml">
                                    Moodle XML формат
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"
                                >Выберите файл теста:</label
                            >
                            <input
                                type="file"
                                ref="fileInput"
                                @change="handleFileSelect"
                                :accept="
                                    importFormat === 'json'
                                        ? '.json,application/json'
                                        : '.xml,text/xml'
                                "
                                class="form-control"
                            />
                            <div class="form-text">
                                <span v-if="importFormat === 'json'">
                                    Выберите файл теста в формате JSON, который
                                    был экспортирован из системы
                                </span>
                                <span v-if="importFormat === 'xml'">
                                    Выберите файл теста в формате Moodle XML
                                    (поддерживаются вопросы: multichoice,
                                    matching, shortanswer, essay, truefalse)
                                </span>
                            </div>
                        </div>

                        <!-- Предпросмотр теста -->
                        <div v-if="importPreview" class="import-preview card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Предпросмотр теста</h6>
                                <small class="text-muted"
                                    >Формат:
                                    {{ importFormat.toUpperCase() }}</small
                                >
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Название:</strong>
                                    {{ importPreview.title || "Не указано" }}
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
                                    {{ importPreview.timeLimit || 60 }} минут
                                </div>
                                <div class="mb-2">
                                    <strong>Всего баллов:</strong>
                                    {{ getTotalPointsPreview(importPreview) }}
                                </div>
                                <div class="mb-2">
                                    <strong>Типы вопросов:</strong>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        <span
                                            v-for="type in getUniqueQuestionTypes(
                                                importPreview
                                            )"
                                            :key="type"
                                            class="badge bg-secondary"
                                        >
                                            {{ type }}
                                        </span>
                                    </div>
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

                                <!-- Детальная информация о вопросах -->
                                <div
                                    v-if="
                                        importPreview.questions &&
                                        importPreview.questions.length > 0
                                    "
                                    class="mt-3"
                                >
                                    <h6 class="mb-2">Детали вопросов:</h6>
                                    <div
                                        class="accordion"
                                        id="questionPreviewAccordion"
                                    >
                                        <div
                                            class="accordion-item"
                                            v-for="(
                                                question, idx
                                            ) in importPreview.questions.slice(
                                                0,
                                                5
                                            )"
                                            :key="question.id"
                                        >
                                            <h2 class="accordion-header">
                                                <button
                                                    class="accordion-button collapsed"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    :data-bs-target="
                                                        '#questionPreview' + idx
                                                    "
                                                >
                                                    Вопрос {{ idx + 1 }}:
                                                    {{ question.type }} ({{
                                                        question.points
                                                    }}
                                                    баллов)
                                                </button>
                                            </h2>
                                            <div
                                                :id="'questionPreview' + idx"
                                                class="accordion-collapse collapse"
                                                data-bs-parent="#questionPreviewAccordion"
                                            >
                                                <div class="accordion-body">
                                                    <div
                                                        v-html="
                                                            question.text.substring(
                                                                0,
                                                                200
                                                            ) +
                                                            (question.text
                                                                .length > 200
                                                                ? '...'
                                                                : '')
                                                        "
                                                    ></div>
                                                    <div
                                                        v-if="question.answers"
                                                        class="mt-2"
                                                    >
                                                        <small
                                                            class="text-muted"
                                                            >Вариантов ответов:
                                                            {{
                                                                question.answers
                                                                    .length
                                                            }}</small
                                                        >
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            v-if="
                                                importPreview.questions.length >
                                                5
                                            "
                                            class="text-center mt-2"
                                        >
                                            <small class="text-muted"
                                                >... и еще
                                                {{
                                                    importPreview.questions
                                                        .length - 5
                                                }}
                                                вопросов</small
                                            >
                                        </div>
                                    </div>
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
                        >
                            Отмена
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            @click="importTest"
                            :disabled="!importPreview"
                        >
                            <i class="bi bi-check-circle"></i>
                            Импортировать тест
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import bootstrap from "bootstrap/dist/js/bootstrap.bundle.min.js";
import {
    exportTestToWord,
    exportTestToWordWithoutAnswers,
    exportTestToWordWithSeparateAnswers,
} from "@/utils/exportToWord";

export default {
    name: "TestManagement",
    props: ["datasend", "showToast", "changeCurrentView"],

    emits: ["error"],
    data() {
        return {
            importPreview: null,
            importErrors: [],
            selectedFile: null,
            error: "",
            tests: [],
            currentExportTest: null, // Текущий тест для экспорта
            showWordOptions: false, // Показывать ли опции Word
            wordExportType: "withAnswers", // Тип экспорта Word
            importFormat: "json",
        };
    },
    mounted() {
        this.getTests();
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
        getTests() {
            this.datasend(`tests/${this.$route.params.id}/get`, "GET").then(
                (response) => {
                    this.tests = response.data;
                }
            );
        },
        editTest(testId) {
            this.changeCurrentView("creator", testId);
        },

        async confirmDelete(test) {
            if (
                confirm(
                    `Вы уверены, что хотите удалить тест "${test.title}"? Все результаты этого теста также будут удалены.`
                )
            ) {
                this.error = "";

                this.datasend(`tests/${test.id}`, "DELETE", this.test).then(
                    (response) => {
                        this.getTests();
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

        // Показать модальное окно экспорта
        showExportModal(test) {
            this.currentExportTest = test;
            this.showWordOptions = false;
            this.wordExportType = "withAnswers";

            this.$nextTick(() => {
                const modalElement = document.getElementById("exportModal");
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                }
            });
        },

        // Экспорт в JSON
        async exportJson() {
            if (!this.currentExportTest) return;

            this.error = "";

            try {
                const blob = await this.exportJsonBlob(this.currentExportTest);
                const url = URL.createObjectURL(blob);
                const link = document.createElement("a");
                link.href = url;
                link.download = `test-${
                    this.currentExportTest.title || "export"
                }.json`;
                link.click();
                URL.revokeObjectURL(url);
                this.showToast("Тест успешно экспортирован в JSON!", "success");

                // Закрыть модальное окно
                this.hideExportModal();
            } catch (error) {
                console.error("Ошибка экспорта в JSON:", error);
                this.error = "Ошибка при экспорте в JSON";
                this.showToast("Ошибка при экспорте теста", "danger");
            } finally {
            }
        },

        // Экспорт в Word
        async exportWord() {
            if (!this.currentExportTest) return;

            // Показываем опции Word
            this.showWordOptions = true;

            // Если уже загружается, не показываем опции
        },

        // Подтвержденный экспорт в Word
        async performWordExport() {
            if (!this.currentExportTest) return;

            this.error = "";

            try {
                let blob;

                // Выбираем тип экспорта
                switch (this.wordExportType) {
                    case "withAnswers":
                        blob = await exportTestToWord(this.currentExportTest);
                        break;
                    case "withoutAnswers":
                        blob = await exportTestToWordWithoutAnswers(
                            this.currentExportTest
                        );
                        break;
                    case "separateAnswers":
                        blob = await exportTestToWordWithSeparateAnswers(
                            this.currentExportTest
                        );
                        break;
                    default:
                        blob = await exportTestToWord(this.currentExportTest);
                }

                /*
                const url = URL.createObjectURL( blob ?? new Blob([]) );
                
                const link = document.createElement("a"); 
                link.href = url;
                link.download = `test-${
                    this.currentExportTest.title || "export"
                }.docx`;
                link.click();
                URL.revokeObjectURL(url);*/

                let message = "Тест успешно экспортирован в Word!";
                if (this.wordExportType === "withoutAnswers") {
                    message += " (без ответов)";
                } else if (this.wordExportType === "separateAnswers") {
                    message += " (с ответами на отдельной странице)";
                }

                this.showToast(message, "success");
                this.hideExportModal();
            } catch (error) {
                console.error("Ошибка экспорта в Word:", error);
                this.error = "Ошибка при экспорте в Word";
                this.showToast("Ошибка при экспорте теста", "danger");
            } finally {
            }
        },

        // Скрыть модальное окно экспорта
        hideExportModal() {
            const modalElement = document.getElementById("exportModal");
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
            }
        },

        // Метод для получения JSON blob
        async exportJsonBlob(test) {
            return this.datasend(
                `tests/${test.id}/export`,
                "GET",
                null,
                false,
                true
            ).then((response) => {
                return response;
            });
        },

        // Старый метод экспорта (оставляем для обратной совместимости)
        exportTest(test) {
            this.error = "";

            try {
                this.datasend(
                    `tests/${test.id}/export`,
                    "GET",
                    null,
                    false,
                    true
                ).then((response) => {
                    const blob = response;
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement("a");
                    link.href = url;
                    link.download = `test-${test.title || "export"}.json`;
                    link.click();
                    URL.revokeObjectURL(url);
                    this.showToast("Тест успешно экспортирован!", "success");
                });
            } catch (error) {
                this.error = error.message;
                this.$emit("error", error.message);
            } finally {
            }
        },

        // Импорт
        // showImportModal() {
        //     this.importPreview = null;
        //     this.importErrors = [];
        //     this.selectedFile = null;
        //     if (this.$refs.fileInput) {
        //         this.$refs.fileInput.value = "";
        //     }

        //     this.$nextTick(() => {
        //         const modalElement = document.getElementById("importModal");
        //         if (modalElement) {
        //             const modal = new bootstrap.Modal(modalElement);
        //             modal.show();
        //         }
        //     });
        // },

        async handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.selectedFile = file;
            this.importPreview = null;
            this.importErrors = [];

            const reader = new FileReader();
            reader.onload = async (e) => {
                try {
                    if (this.importFormat === "json") {
                        const testData = JSON.parse(e.target.result);
                        this.validateTestFile(testData);
                        this.importPreview = testData;
                    } else if (this.importFormat === "xml") {
                        // Парсим XML на клиенте
                        const xmlText = e.target.result;
                        const testData = await this.parseMoodleXml(
                            xmlText,
                            file.name
                        );
                        this.importPreview = testData;
                        // Для XML проводим упрощенную валидацию
                        this.validateXmlTestFile(testData);
                    }
                } catch (error) {
                    this.importErrors = [
                        `Ошибка чтения файла: ${error.message}`,
                    ];
                    console.error("Ошибка импорта:", error);
                }
            };

            reader.readAsText(file);
        },

        // Парсер Moodle XML на клиенте
        async parseMoodleXml(xmlText, fileName) {
            return new Promise((resolve, reject) => {
                try {
                    const parser = new DOMParser();
                    const xmlDoc = parser.parseFromString(xmlText, "text/xml");

                    // Проверка на ошибки парсинга
                    const parserError =
                        xmlDoc.getElementsByTagName("parsererror");
                    if (parserError.length > 0) {
                        throw new Error("Некорректный XML формат");
                    }

                    const quizElement = xmlDoc.getElementsByTagName("quiz")[0];
                    if (!quizElement) {
                        throw new Error(
                            "Файл не содержит тестовых данных (отсутствует тег quiz)"
                        );
                    }

                    const questions = [];
                    const questionElements =
                        xmlDoc.getElementsByTagName("question");

                    for (let i = 0; i < questionElements.length; i++) {
                        const qElement = questionElements[i];
                        const type = qElement.getAttribute("type");

                        // Пропускаем категории
                        if (type === "category") continue;

                        try {
                            const question = this.parseQuestion(
                                qElement,
                                type,
                                i
                            );
                            if (question) {
                                questions.push(question);
                            }
                        } catch (error) {
                            console.warn(
                                `Ошибка парсинга вопроса ${i + 1}:`,
                                error
                            );
                            this.importErrors.push(
                                `Вопрос ${i + 1}: ${error.message}`
                            );
                        }
                    }

                    if (questions.length === 0) {
                        throw new Error(
                            "Не найдено ни одного валидного вопроса"
                        );
                    }

                    // Генерируем название из имени файла
                    const testName = fileName.replace(/\.[^/.]+$/, ""); // Убираем расширение
                    const cleanName = testName.replace(/[^\w\s]/gi, " ").trim();

                    const testData = {
                        title: cleanName || "Импортированный тест",
                        description: `Импортировано из Moodle XML файла: ${fileName}`,
                        timeLimit: 60, // Значение по умолчанию
                        questions: questions,
                        settings: {
                            requireUserName: true,
                            shuffleQuestions: false,
                            shuffleAnswers: false,
                        },
                    };

                    resolve(testData);
                } catch (error) {
                    reject(error);
                }
            });
        },

        parseQuestion(qElement, type, index) {
            const nameElement = this.getChildElement(qElement, "name");
            const questionTextElement = this.getChildElement(
                qElement,
                "questiontext"
            );

            if (!questionTextElement) {
                throw new Error("Отсутствует текст вопроса");
            }

            const questionText =
                this.extractTextWithImages(questionTextElement);
            const questionName = nameElement
                ? this.extractText(this.getChildElement(nameElement, "text"))
                : `Вопрос ${index + 1}`;

            // Баллы по умолчанию
            const defaultGradeElement = this.getChildElement(
                qElement,
                "defaultgrade"
            );
            const points = defaultGradeElement
                ? parseFloat(this.extractText(defaultGradeElement)) || 1
                : 1;

            // В зависимости от типа вопроса
            switch (type) {
                case "multichoice":
                    return this.parseMultichoiceQuestion(
                        qElement,
                        questionName,
                        questionText,
                        points
                    );
                case "matching":
                    return this.parseMatchingQuestion(
                        qElement,
                        questionName,
                        questionText,
                        points
                    );
                case "shortanswer":
                    return this.parseShortanswerQuestion(
                        qElement,
                        questionName,
                        questionText,
                        points
                    );
                case "essay":
                    return this.parseEssayQuestion(
                        qElement,
                        questionName,
                        questionText,
                        points
                    );
                case "truefalse":
                    return this.parseTruefalseQuestion(
                        qElement,
                        questionName,
                        questionText,
                        points
                    );
                default:
                    console.warn(`Неподдерживаемый тип вопроса: ${type}`);
                    return this.parseFallbackQuestion(
                        qElement,
                        questionName,
                        questionText,
                        points,
                        type
                    );
            }
        },

        parseMultichoiceQuestion(qElement, name, text, points) {
            const answerElements = qElement.getElementsByTagName("answer");
            const answers = [];
            let correctAnswersCount = 0;

            for (let i = 0; i < answerElements.length; i++) {
                const answerElement = answerElements[i];
                const answerText = this.extractTextWithImages(answerElement);
                const fraction = parseFloat(
                    answerElement.getAttribute("fraction") || "0"
                );
                const isCorrect = fraction > 0;

                if (isCorrect) correctAnswersCount++;

                answers.push({
                    id: i + 1,
                    text: answerText,
                    correct: isCorrect,
                    points: isCorrect ? points : 0,
                });
            }

            // Определяем тип вопроса (single/multiple) на основе наличия single="false"
            const singleAttr = qElement.getAttribute("single");
            const isSingleChoice =
                singleAttr !== "false" && correctAnswersCount <= 1;

            // Проверяем shuffleanswers
            const shuffleAnswers =
                qElement.getElementsByTagName("shuffleanswers").length > 0;

            return {
                id: this.generateId(),
                type: isSingleChoice ? "single" : "multiple",
                text: text,
                points: points,
                answers: answers,
                explanation: "",
                settings: {
                    shuffleAnswers: shuffleAnswers,
                },
            };
        },

        parseMatchingQuestion(qElement, name, text, points) {
            const subQuestions = qElement.getElementsByTagName("subquestion");
            const pairs = [];

            for (let i = 0; i < subQuestions.length; i++) {
                const subQ = subQuestions[i];
                const questionText = this.extractTextWithImages(subQ);
                const answerElement = subQ.getElementsByTagName("answer")[0];

                if (answerElement) {
                    const answerText =
                        this.extractTextWithImages(answerElement);
                    pairs.push({
                        question: questionText,
                        answer: answerText,
                    });
                }
            }

            return {
                id: this.generateId(),
                type: "matching",
                text: text,
                points: points,
                pairs: pairs,
                explanation: "",
            };
        },

        parseShortanswerQuestion(qElement, name, text, points) {
            const answerElements = qElement.getElementsByTagName("answer");
            const correctAnswers = [];

            for (let i = 0; i < answerElements.length; i++) {
                const answerText = this.extractText(answerElements[i]);
                if (answerText && answerText.trim()) {
                    correctAnswers.push(answerText.trim());
                }
            }

            return {
                id: this.generateId(),
                type: "short",
                text: text,
                points: points,
                correctAnswer: correctAnswers.join("; "),
                explanation: "",
            };
        },

        parseEssayQuestion(qElement, name, text, points) {
            return {
                id: this.generateId(),
                type: "essay",
                text: text,
                points: points,
                explanation: "",
            };
        },

        parseTruefalseQuestion(qElement, name, text, points) {
            const answerElements = qElement.getElementsByTagName("answer");
            let correctAnswer = null;

            for (let i = 0; i < answerElements.length; i++) {
                const answerElement = answerElements[i];
                const fraction = parseFloat(
                    answerElement.getAttribute("fraction") || "0"
                );
                if (fraction > 0) {
                    const answerText = this.extractText(answerElement);
                    correctAnswer =
                        answerText.toLowerCase() === "true" ||
                        answerText === "1";
                    break;
                }
            }

            return {
                id: this.generateId(),
                type: "single",
                text: text,
                points: points,
                answers: [
                    {
                        id: 1,
                        text: "Верно",
                        correct: correctAnswer === true,
                        points: correctAnswer === true ? points : 0,
                    },
                    {
                        id: 2,
                        text: "Неверно",
                        correct: correctAnswer === false,
                        points: correctAnswer === false ? points : 0,
                    },
                ],
                explanation: "",
            };
        },

        parseFallbackQuestion(qElement, name, text, points, originalType) {
            // Fallback для неподдерживаемых типов - преобразуем в текстовый вопрос
            return {
                id: this.generateId(),
                type: "essay",
                text: `${text}<br><small><i>Тип вопроса Moodle: ${originalType}</i></small>`,
                points: points,
                explanation: "",
            };
        },

        // Вспомогательные методы для работы с DOM
        getChildElement(parent, tagName) {
            const elements = parent.getElementsByTagName(tagName);
            return elements.length > 0 ? elements[0] : null;
        },

        extractText(element) {
            if (!element) return "";
            const textElement = element.getElementsByTagName("text")[0];
            if (!textElement) return element.textContent || "";

            let text = textElement.textContent || textElement.innerHTML || "";

            // Очищаем HTML теги, но сохраняем переносы строк
            text = text.replace(/<br\s*\/?>/gi, "\n");
            text = text.replace(/<\/?[^>]+(>|$)/g, "");

            return text.trim();
        },

        extractTextWithImages(element) {
            if (!element) return "";
            const textElement = element.getElementsByTagName("text")[0];
            if (!textElement) return "";

            let html = textElement.innerHTML || "";

            // Если нет HTML контента, используем текст
            if (!html || html.trim() === "") {
                html = textElement.textContent || "";
            }

            // Обрабатываем изображения в формате base64
            html = this.processImagesInHtml(html);

            return html;
        },

        processImagesInHtml(html) {
            if (!html) return "";

            // Регулярное выражение для поиска изображений в base64
            // Поддерживаем разные форматы: png, jpeg, jpg, gif
            const imgRegex =
                /<img[^>]+src="data:image\/(png|jpeg|jpg|gif);base64,([^"]+)"[^>]*>/gi;

            return html.replace(imgRegex, (match, format, base64) => {
                // Сохраняем изображение как есть в base64
                // Можно оптимизировать размер, но пока оставляем как есть
                return `<img src="data:image/${format.toLowerCase()};base64,${base64}" style="max-width: 100%; height: auto;" />`;
            });
        },

        generateId() {
            return Date.now() + Math.floor(Math.random() * 1000);
        },

        getUniqueQuestionTypes(test) {
            if (!test || !test.questions) return [];
            const typeMap = {
                single: "Один ответ",
                multiple: "Несколько ответов",
                matching: "Сопоставление",
                short: "Короткий ответ",
                essay: "Развернутый ответ",
            };

            const types = new Set(
                test.questions.map((q) => typeMap[q.type] || q.type)
            );
            return Array.from(types);
        },

        // Упрощенная валидация для XML
        validateXmlTestFile(testData) {
            this.importErrors = [];

            if (!testData.title) {
                this.importErrors.push("Отсутствует название теста");
            }

            if (!testData.questions || !Array.isArray(testData.questions)) {
                this.importErrors.push("Отсутствуют вопросы");
            } else if (testData.questions.length === 0) {
                this.importErrors.push(
                    "Не найдено ни одного валидного вопроса"
                );
            }

            // Проверяем каждый вопрос
            testData.questions.forEach((question, index) => {
                if (!question.text || question.text.trim() === "") {
                    this.importErrors.push(
                        `Вопрос ${index + 1}: отсутствует текст вопроса`
                    );
                }

                if (!question.type) {
                    this.importErrors.push(
                        `Вопрос ${index + 1}: отсутствует тип вопроса`
                    );
                }

                if (!question.points || question.points <= 0) {
                    this.importErrors.push(
                        `Вопрос ${index + 1}: неверные баллы (${
                            question.points
                        })`
                    );
                }

                // Проверка в зависимости от типа вопроса
                switch (question.type) {
                    case "single":
                    case "multiple":
                        if (
                            !question.answers ||
                            !Array.isArray(question.answers) ||
                            question.answers.length === 0
                        ) {
                            this.importErrors.push(
                                `Вопрос ${
                                    index + 1
                                }: отсутствуют варианты ответов`
                            );
                        } else {
                            const correctAnswers = question.answers.filter(
                                (a) => a.correct
                            );
                            if (correctAnswers.length === 0) {
                                this.importErrors.push(
                                    `Вопрос ${
                                        index + 1
                                    }: нет правильных ответов`
                                );
                            }
                        }
                        break;

                    case "matching":
                        if (
                            !question.pairs ||
                            !Array.isArray(question.pairs) ||
                            question.pairs.length === 0
                        ) {
                            this.importErrors.push(
                                `Вопрос ${
                                    index + 1
                                }: отсутствуют пары для сопоставления`
                            );
                        }
                        break;

                    case "short":
                        if (
                            !question.correctAnswer ||
                            question.correctAnswer.trim() === ""
                        ) {
                            this.importErrors.push(
                                `Вопрос ${
                                    index + 1
                                }: отсутствует правильный ответ`
                            );
                        }
                        break;
                }
            });

            // Информационное сообщение для XML
            // if (this.importErrors.length === 0) {
            //     this.importErrors.push(
            //         "Файл прошел базовую проверку. Для точной проверки импортируйте тест."
            //     );
            // }
        },

        validateTestFile(testData) {
            this.importErrors = [];

            const requiredFields = ["title", "questions"];
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

            this.error = "";

            try {
                let formData = new FormData();
                formData.append("file", this.selectedFile);
                formData.append("tree_id", this.$route.params.id);
                formData.append("format", this.importFormat);

                // Для XML добавляем распарсенные данные
                if (this.importFormat === "xml") {
                    formData.append(
                        "xml_data",
                        JSON.stringify(this.importPreview)
                    );
                    formData.append(
                        "grading",
                        JSON.stringify([
                            {
                                minScore: 0,
                                max_score: 59,
                                grade: "Неудовлетворительно",
                            },
                            {
                                minScore: 60,
                                max_score: 74,
                                grade: "Удовлетворительно",
                            },
                            { minScore: 75, max_score: 89, grade: "Хорошо" },
                            { minScore: 90, max_score: 100, grade: "Отлично" },
                        ])
                    );
                }

                // Используем существующий метод datasend для отправки
                this.datasend("tests/import", "POST", formData, true)
                    .then((response) => {
                        this.showToast(response.message, "success");
                        this.getTests();
                        const modalElement =
                            document.getElementById("importModal");
                        if (modalElement) {
                            const modal =
                                bootstrap.Modal.getInstance(modalElement);
                            if (modal) {
                                modal.hide();
                            }
                        }
                    })
                    .catch((error) => {
                        this.error = error.message;
                        this.showToast(
                            `Ошибка импорта: ${error.message}`,
                            "danger"
                        );
                    });
            } catch (error) {
                this.error = error.message;
                this.$emit("error", error.message);
            } finally {
            }
        },
        getTotalPointsPreview(test) {
            if (!test || !test.questions) return 0;
            return test.questions.reduce(
                (sum, q) => sum + (parseInt(q.points) || 0),
                0
            );
        },
        showImportModal() {
            this.importPreview = null;
            this.importErrors = [];
            this.selectedFile = null;
            this.importFormat = "json";
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

import-preview {
    max-height: 500px;
    overflow-y: auto;
}

.accordion-button {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}

.accordion-body {
    font-size: 0.85rem;
    padding: 0.75rem;
}

/* Стили для изображений в предпросмотре */
.import-preview img {
    max-width: 100px;
    max-height: 100px;
    margin: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

/* Стили для модального окна экспорта */
.list-group-item:hover {
    background-color: #f8f9fa;
    cursor: pointer;
}

.list-group-item:active {
    background-color: #e9ecef;
}

.form-check {
    padding-left: 2rem;
}

.form-check-input {
    margin-left: -2rem;
}
</style>
