<template>
    <div class="test-creator">
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
            <h2 class="mb-0">
                <i
                    class="bi"
                    :class="isEditing ? 'bi-pencil' : 'bi-pencil-square'"
                ></i>
                {{
                    isEditing ? "Редактирование теста" : "Создание нового теста"
                }}
            </h2>
            <div>
                <button
                    v-if="!isEditing"
                    @click="showImportModal"
                    class="btn btn-success me-2"
                >
                    <i class="bi bi-upload"></i> Импорт теста
                </button>
                <button @click="exportTest" class="btn btn-success me-2">
                    <i class="bi bi-download"></i> Экспорт теста
                </button>
                <button
                    v-if="isEditing"
                    @click="cancelEdit"
                    class="btn btn-outline-secondary"
                >
                    <i class="bi bi-arrow-left"></i> Назад к списку
                </button>
            </div>
        </div>
        <div
            v-if="loading"
            class="d-flex justify-content-center align-items-center py-5"
        >
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
        </div>
        <div v-else>
            <!-- Основная информация о тесте -->
            <div class="card mb-4">
                <div
                    class="card-header bg-primary text-white d-flex justify-content-between align-items-center"
                >
                    <h5 class="mb-0">Основная информация</h5>
                    <div v-if="isEditing" class="text-warning">
                        <i class="bi bi-info-circle"></i> Режим редактирования
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Название теста:</label>
                            <input
                                v-model="test.title"
                                type="text"
                                class="form-control"
                                placeholder="Введите название теста"
                            />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"
                                >Время на выполнение (минут):</label
                            >
                            <input
                                v-model="test.timeLimit"
                                type="number"
                                class="form-control"
                                min="1"
                            />
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Описание теста:</label>
                        <textarea
                            v-model="test.description"
                            class="form-control"
                            rows="3"
                            placeholder="Описание теста"
                        ></textarea>
                    </div>

                    <!-- Настройки теста -->
                    <div class="row">
                        
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input
                                    v-model="test.settings.requireUserName"
                                    class="form-check-input"
                                    type="checkbox"
                                />
                                <label class="form-check-label"
                                    >Требовать ввод имени при прохождении</label
                                >
                            </div>
                        </div>
                    </div>

                    <div class="row" v-if="test.settings.requireUserName">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input
                                    v-model="
                                        test.settings.showUserNameInResults
                                    "
                                    class="form-check-input"
                                    type="checkbox"
                                />
                                <label class="form-check-label"
                                    >Показывать имя в результатах</label
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Настройки перемешивания -->
                    <div class="card mt-3">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0">
                                <i class="bi bi-shuffle"></i> Настройки
                                перемешивания
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input
                                            v-model="
                                                test.settings.shuffleQuestions
                                            "
                                            class="form-check-input"
                                            type="checkbox"
                                        />
                                        <label class="form-check-label">
                                            <strong
                                                >Перемешивать вопросы</strong
                                            >
                                            <div class="form-text">
                                                Вопросы будут показываться в
                                                случайном порядке для каждого
                                                пользователя
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input
                                            v-model="
                                                test.settings.shuffleAnswers
                                            "
                                            class="form-check-input"
                                            type="checkbox"
                                        />
                                        <label class="form-check-label">
                                            <strong>Перемешивать ответы</strong>
                                            <div class="form-text">
                                                Варианты ответов будут
                                                показываться в случайном порядке
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Дополнительные настройки для типов вопросов -->
                            <div
                                v-if="test.settings.shuffleAnswers"
                                class="mt-3 p-3 bg-light rounded"
                            >
                                <h6 class="mb-2">
                                    Настройки перемешивания ответов:
                                </h6>

                                <div class="form-check form-check-inline">
                                    <input
                                        v-model="
                                            test.settings.shuffleSingleChoice
                                        "
                                        class="form-check-input"
                                        type="checkbox"
                                        :disabled="
                                            !test.settings.shuffleAnswers
                                        "
                                    />
                                    <label class="form-check-label"
                                        >Одиночный выбор</label
                                    >
                                </div>

                                <div class="form-check form-check-inline">
                                    <input
                                        v-model="
                                            test.settings.shuffleMultipleChoice
                                        "
                                        class="form-check-input"
                                        type="checkbox"
                                        :disabled="
                                            !test.settings.shuffleAnswers
                                        "
                                    />
                                    <label class="form-check-label"
                                        >Множественный выбор</label
                                    >
                                </div>

                                <div class="form-check form-check-inline">
                                    <input
                                        v-model="test.settings.shuffleMatching"
                                        class="form-check-input"
                                        type="checkbox"
                                        :disabled="
                                            !test.settings.shuffleAnswers
                                        "
                                    />
                                    <label class="form-check-label"
                                        >Сопоставление</label
                                    >
                                </div>

                                <div class="form-text small">
                                    Выберите типы вопросов, для которых нужно
                                    перемешивать ответы
                                </div>
                            </div>

                            <!-- Предупреждение для вопросов с изображениями -->
                            <div
                                v-if="
                                    test.settings.shuffleAnswers &&
                                    hasQuestionsWithImages
                                "
                                class="alert alert-warning mt-3"
                            >
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Внимание:</strong> В тесте есть вопросы
                                с изображениями в вариантах ответов. При
                                перемешивании изображения останутся привязанными
                                к своим вариантам.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Система оценивания -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Система оценивания</h5>
                </div>
                <div class="card-body">
                    <div
                        v-for="(grade, index) in test.grading"
                        :key="index"
                        class="row mb-2 align-items-center"
                    >
                        <div class="col-md-3">
                            <input
                                v-model="grade.minScore"
                                type="number"
                                class="form-control"
                                placeholder="Мин. баллы"
                                min="0"
                            />
                        </div>
                        <div class="col-md-3">
                            <input
                                v-model="grade.max_score"
                                type="number"
                                class="form-control"
                                placeholder="Макс. баллы"
                                min="0"
                            />
                        </div>
                        <div class="col-md-4">
                            <input
                                v-model="grade.grade"
                                type="text"
                                class="form-control"
                                placeholder="Оценка"
                            />
                        </div>
                        <div class="col-md-2">
                            <button
                                @click="removeGradeLevel(index)"
                                class="btn btn-outline-danger btn-sm"
                                :disabled="test.grading.length <= 1"
                            >
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <button
                        @click="addGradeLevel"
                        class="btn btn-outline-primary btn-sm"
                    >
                        <i class="bi bi-plus-circle"></i> Добавить уровень
                        оценки
                    </button>
                </div>
            </div>

            <!-- Вопросы -->
            <div class="card">
                <div
                    class="card-header bg-success text-white d-flex justify-content-between align-items-center"
                >
                    <h5 class="mb-0">Вопросы теста</h5>
                    <span class="badge bg-light text-dark"
                        >{{ test.questions.length }} вопросов</span
                    >
                </div>
                <div class="card-body">
                    <!-- Статистика вопросов -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body py-2">
                                    <h6 class="card-title mb-0">
                                        {{ totalPoints }}
                                    </h6>
                                    <small class="text-muted"
                                        >Всего баллов</small
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body py-2">
                                    <h6 class="card-title mb-0">
                                        {{ questionTypes.single }}
                                    </h6>
                                    <small class="text-muted"
                                        >Одиночный выбор</small
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body py-2">
                                    <h6 class="card-title mb-0">
                                        {{ questionTypes.multiple }}
                                    </h6>
                                    <small class="text-muted"
                                        >Множественный выбор</small
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body py-2">
                                    <h6 class="card-title mb-0">
                                        {{ questionTypes.other }}
                                    </h6>
                                    <small class="text-muted"
                                        >Другие типы</small
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-for="(question, qIndex) in test.questions"
                        :key="question.id"
                        class="question-card card mb-3"
                    >
                        <div
                            class="card-header d-flex justify-content-between align-items-center"
                        >
                            <div>
                                <h6 class="mb-0">Вопрос {{ qIndex + 1 }}</h6>
                                <small class="text-muted"
                                    >{{ getQuestionTypeLabel(question.type) }} •
                                    {{ question.points }} баллов</small
                                >
                            </div>
                            <div>
                                <button
                                    v-if="qIndex > 0"
                                    @click="moveQuestionUp(qIndex)"
                                    class="btn btn-outline-secondary btn-sm me-1"
                                    title="Переместить вверх"
                                >
                                    <i class="bi bi-arrow-up"></i>
                                </button>
                                <button
                                    v-if="qIndex < test.questions.length - 1"
                                    @click="moveQuestionDown(qIndex)"
                                    class="btn btn-outline-secondary btn-sm me-1"
                                    title="Переместить вниз"
                                >
                                    <i class="bi bi-arrow-down"></i>
                                </button>
                                <button
                                    @click="duplicateQuestion(qIndex)"
                                    class="btn btn-outline-info btn-sm me-1"
                                    title="Дублировать вопрос"
                                >
                                    <i class="bi bi-copy"></i>
                                </button>
                                <button
                                    @click="removeQuestion(qIndex)"
                                    class="btn btn-outline-danger btn-sm"
                                    title="Удалить вопрос"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Текст вопроса -->
                            <div class="mb-3">
                                <label class="form-label">Текст вопроса:</label>
                                <textarea
                                    v-model="question.text"
                                    class="form-control"
                                    rows="2"
                                    placeholder="Введите текст вопроса"
                                ></textarea>
                            </div>

                            <!-- Изображение вопроса -->
                            <div class="mb-3">
                                <label class="form-label"
                                    >Изображение к вопросу (опционально):</label
                                >

                                <!-- Предпросмотр изображения -->
                                <div v-if="question.image" class="mb-3">
                                    <div
                                        class="image-preview-container position-relative d-inline-block"
                                    >
                                        <img
                                            :src="question.image"
                                            class="img-thumbnail"
                                            style="max-height: 200px"
                                        />
                                        <button
                                            @click="removeQuestionImage(qIndex)"
                                            class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                            type="button"
                                        >
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Загрузка изображения -->
                                <div class="input-group">
                                    <input
                                        type="file"
                                        @change="
                                            handleImageUpload($event, qIndex)
                                        "
                                        accept="image/*"
                                        class="form-control"
                                        :id="'imageUpload' + qIndex"
                                    />
                                    <button
                                        class="btn btn-outline-secondary"
                                        type="button"
                                        @click="openImageManager(qIndex)"
                                    >
                                        <i class="bi bi-image"></i> Менеджер
                                        изображений
                                    </button>
                                </div>
                                <div class="form-text">
                                    Поддерживаемые форматы: JPG, PNG, GIF.
                                    Максимальный размер: 2MB
                                </div>
                            </div>

                            <!-- Тип вопроса и баллы -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label"
                                        >Тип вопроса:</label
                                    >
                                    <select
                                        v-model="question.type"
                                        @change="resetQuestionAnswers(question)"
                                        class="form-select"
                                    >
                                        <option value="single">
                                            Одиночный выбор
                                        </option>
                                        <option value="multiple">
                                            Множественный выбор
                                        </option>
                                        <option value="true-false">
                                            Да/Нет
                                        </option>
                                        <option value="text">
                                            Свободный ввод
                                        </option>
                                        <option value="matching">
                                            Сопоставление
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"
                                        >Баллы за вопрос:</label
                                    >
                                    <input
                                        v-model="question.points"
                                        type="number"
                                        class="form-control"
                                        min="1"
                                    />
                                </div>
                            </div>

                            <!-- Одиночный и множественный выбор -->
                            <div
                                v-if="
                                    ['single', 'multiple'].includes(
                                        question.type
                                    )
                                "
                                class="mb-3"
                            >
                                <label class="form-label"
                                    >Варианты ответов:</label
                                >
                                <div
                                    v-for="(option, oIndex) in question.options"
                                    :key="oIndex"
                                    class="option-item mb-2"
                                >
                                    <div class="input-group">
                                        <input
                                            v-model="option.text"
                                            type="text"
                                            class="form-control"
                                            placeholder="Текст варианта"
                                        />
                                        <span class="input-group-text">
                                            <input
                                                v-model="option.correct"
                                                :type="
                                                    question.type === 'single'
                                                        ? 'radio'
                                                        : 'checkbox'
                                                "
                                                :name="'q' + qIndex"
                                                :value="true"
                                                class="form-check-input"
                                            />
                                        </span>
                                        <button
                                            @click="
                                                removeOption(qIndex, oIndex)
                                            "
                                            class="btn btn-outline-danger"
                                            :disabled="
                                                question.options.length <= 2
                                            "
                                        >
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>

                                    <!-- Изображение для варианта ответа -->
                                    <div class="mt-2 ps-4">
                                        <label class="form-label small"
                                            >Изображение для варианта
                                            (опционально):</label
                                        >

                                        <div v-if="option.image" class="mb-2">
                                            <div
                                                class="image-preview-container position-relative d-inline-block"
                                            >
                                                <img
                                                    :src="option.image"
                                                    class="img-thumbnail"
                                                    style="max-height: 100px"
                                                />
                                                <button
                                                    @click="
                                                        removeOptionImage(
                                                            qIndex,
                                                            oIndex
                                                        )
                                                    "
                                                    class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                                    type="button"
                                                >
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="input-group input-group-sm">
                                            <input
                                                type="file"
                                                @change="
                                                    handleOptionImageUpload(
                                                        $event,
                                                        qIndex,
                                                        oIndex
                                                    )
                                                "
                                                accept="image/*"
                                                class="form-control form-control-sm"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <button
                                    @click="addOption(qIndex)"
                                    class="btn btn-outline-primary btn-sm"
                                >
                                    <i class="bi bi-plus-circle"></i> Добавить
                                    вариант
                                </button>
                            </div>

                            <!-- Да/Нет -->
                            <div
                                v-if="question.type === 'true-false'"
                                class="mb-3"
                            >
                                <label class="form-label"
                                    >Правильный ответ:</label
                                >
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input
                                            v-model="question.correct_answer"
                                            type="radio"
                                            id="trues"
                                            value="true"
                                            class="form-check-input"
                                        />
                                        <label
                                            class="form-check-label"
                                            for="trues"
                                            >Да</label
                                        >
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input
                                            v-model="question.correct_answer"
                                            type="radio"
                                            value="false"
                                            id="falses"
                                            class="form-check-input"
                                        />
                                        <label
                                            class="form-check-label"
                                            for="falses"
                                            >Нет</label
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Свободный ввод -->
                            <div v-if="question.type === 'text'" class="mb-3">
                                <label class="form-label"
                                    >Правильные ответы
                                    (регистронезависимые):</label
                                >
                                <div
                                    v-for="(
                                        answer, aIndex
                                    ) in question.correct_answers"
                                    :key="aIndex"
                                    class="input-group mb-2"
                                >
                                    <input
                                        v-model="
                                            question.correct_answers[aIndex]
                                        "
                                        type="text"
                                        class="form-control"
                                        placeholder="Правильный ответ"
                                    />
                                    <button
                                        @click="
                                            removecorrect_answer(qIndex, aIndex)
                                        "
                                        class="btn btn-outline-danger"
                                        :disabled="
                                            question.correct_answers.length <= 1
                                        "
                                    >
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                                <button
                                    @click="addcorrect_answer(qIndex)"
                                    class="btn btn-outline-primary btn-sm"
                                >
                                    <i class="bi bi-plus-circle"></i> Добавить
                                    ответ
                                </button>
                            </div>

                            <!-- Сопоставление -->
                            <div
                                v-if="question.type === 'matching'"
                                class="mb-3"
                            >
                                <label class="form-label"
                                    >Пары для сопоставления:</label
                                >
                                <div
                                    v-for="(pair, pIndex) in question.pairs"
                                    :key="pIndex"
                                    class="matching-pair row mb-2 align-items-center"
                                >
                                    <div class="col-md-5">
                                        <input
                                            v-model="pair.left"
                                            type="text"
                                            class="form-control"
                                            placeholder="Левая часть"
                                        />

                                        <!-- Изображение для левой части -->
                                        <div class="mt-1">
                                            <div
                                                v-if="pair.leftImage"
                                                class="image-preview-container position-relative d-inline-block"
                                            >
                                                <img
                                                    :src="pair.leftImage"
                                                    class="img-thumbnail"
                                                    style="max-height: 80px"
                                                />
                                                <button
                                                    @click="
                                                        removeLeftImage(
                                                            qIndex,
                                                            pIndex
                                                        )
                                                    "
                                                    class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                                    type="button"
                                                >
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                            <input
                                                type="file"
                                                @change="
                                                    handleLeftImageUpload(
                                                        $event,
                                                        qIndex,
                                                        pIndex
                                                    )
                                                "
                                                accept="image/*"
                                                class="form-control form-control-sm mt-1"
                                            />
                                        </div>
                                    </div>

                                    <div class="col-md-1 text-center">
                                        <i class="bi bi-arrow-right"></i>
                                    </div>

                                    <div class="col-md-5">
                                        <input
                                            v-model="pair.right"
                                            type="text"
                                            class="form-control"
                                            placeholder="Правая часть"
                                        />

                                        <!-- Изображение для правой части -->
                                        <div class="mt-1">
                                            <div
                                                v-if="pair.rightImage"
                                                class="image-preview-container position-relative d-inline-block"
                                            >
                                                <img
                                                    :src="pair.rightImage"
                                                    class="img-thumbnail"
                                                    style="max-height: 80px"
                                                />
                                                <button
                                                    @click="
                                                        removeRightImage(
                                                            qIndex,
                                                            pIndex
                                                        )
                                                    "
                                                    class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                                    type="button"
                                                >
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                            <input
                                                type="file"
                                                @change="
                                                    handleRightImageUpload(
                                                        $event,
                                                        qIndex,
                                                        pIndex
                                                    )
                                                "
                                                accept="image/*"
                                                class="form-control form-control-sm mt-1"
                                            />
                                        </div>
                                    </div>

                                    <div class="col-md-1">
                                        <button
                                            @click="
                                                removeMatchingPair(
                                                    qIndex,
                                                    pIndex
                                                )
                                            "
                                            class="btn btn-outline-danger btn-sm"
                                            :disabled="
                                                question.pairs.length <= 2
                                            "
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <button
                                    @click="addMatchingPair(qIndex)"
                                    class="btn btn-outline-primary btn-sm"
                                >
                                    <i class="bi bi-plus-circle"></i> Добавить
                                    пару
                                </button>
                            </div>
                        </div>
                    </div>

                    <button @click="addQuestion" class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle"></i> Добавить вопрос
                    </button>
                </div>
            </div>

            <!-- Действия -->
            <div class="mt-4 d-flex gap-2 justify-content-center">
                <button
                    @click="saveTest"
                    :disabled="!isTestValid"
                    class="btn btn-success btn-lg"
                >
                    <i class="bi bi-check-circle"></i>
                    {{ isEditing ? "Обновить тест" : "Сохранить тест" }}
                </button>
                <button
                    @click="resetTest"
                    class="btn btn-outline-secondary btn-lg"
                >
                    <i class="bi bi-arrow-clockwise"></i> Сбросить
                </button>
                <button
                    v-if="isEditing"
                    @click="deleteTest"
                    class="btn btn-outline-danger btn-lg"
                >
                    <i class="bi bi-trash"></i> Удалить тест
                </button>
            </div>
        </div>
        <!-- Модальное окно менеджера изображений -->
        <div class="modal fade" id="imageManagerModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Менеджер изображений</h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <input
                                type="file"
                                @change="handleManagerImageUpload"
                                accept="image/*"
                                class="form-control"
                                multiple
                            />
                        </div>

                        <div class="row" v-if="imageManager.images.length > 0">
                            <div
                                v-for="(image, index) in imageManager.images"
                                :key="index"
                                class="col-md-4 mb-3"
                            >
                                <div
                                    class="image-item card"
                                    :class="{
                                        'border-primary':
                                            imageManager.selectedImage ===
                                            image,
                                    }"
                                    @click="selectImageInManager(image)"
                                >
                                    <img
                                        :src="image"
                                        class="card-img-top"
                                        style="height: 100px; object-fit: cover"
                                    />
                                    <div class="card-body p-2 text-center">
                                        <small class="text-muted"
                                            >Изображение {{ index + 1 }}</small
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center text-muted py-4">
                            <i class="bi bi-image display-4"></i>
                            <p class="mt-2">Нет загруженных изображений</p>
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
                            @click="applySelectedImage"
                            :disabled="!imageManager.selectedImage"
                        >
                            Выбрать изображение
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно импорта -->
        <div class="modal fade" id="importModalCreator" tabindex="-1">
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
                                ref="fileInputCreator"
                                @change="handleFileSelect"
                                accept=".json,application/json"
                                class="form-control"
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
                            @click="importTestInCreator"
                            :disabled="
                                !importPreview || importErrors.length > 0
                            "
                        >
                            <i class="bi bi-check-circle"></i> Импортировать
                            тест
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {
    saveTest,
    deleteTest,
    loadTests,
    importTest,
    exportTest,
} from "../utils/storage.js";

import bootstrap from "bootstrap/dist/js/bootstrap.bundle.min.js";
export default {
    name: "TestCreator",
    emits: [
        "test-created",
        "test-updated",
        "test-deleted",
        "edit-cancelled",
        "error",
    ],
    props: {
        editTestId: {
            type: Number,
            default: null,
        },
    },
    data() {
        return {
            test: {
                id: null,
                title: "",
                description: "",
                timeLimit: 30,
                settings: {
                    requireUserName: false,
                    showUserNameInResults: true,
                    shuffleQuestions: false,
                    shuffleAnswers: false,
                    shuffleSingleChoice: true,
                    shuffleMultipleChoice: true,
                    shuffleMatching: true,
                },
                grading: [
                    {
                        minScore: 0,
                        max_score: 59,
                        grade: "Неудовлетворительно",
                    },
                    { minScore: 60, max_score: 74, grade: "Удовлетворительно" },
                    { minScore: 75, max_score: 89, grade: "Хорошо" },
                    { minScore: 90, max_score: 100, grade: "Отлично" },
                ],
                questions: [],
            },
            imageManager: {
                images: [],
                selectedImage: null,
                currentQuestionIndex: null,
                currentOptionIndex: null,
                currentPairIndex: null,
                imageType: null,
            },
            isEditing: false,
            importPreview: null,
            importErrors: [],
            selectedFile: null,
            loading: false,
            error: "",
        };
    },
    computed: {
        isTestValid() {
            return (
                this.test.title.trim() &&
                this.test.questions.length > 0 &&
                this.test.questions.every((q) => this.validateQuestion(q))
            );
        },
        totalPoints() {
            return this.test.questions.reduce(
                (sum, q) => sum + (parseInt(q.points) || 0),
                0
            );
        },
        questionTypes() {
            const types = {
                single: 0,
                multiple: 0,
                other: 0,
            };

            this.test.questions.forEach((q) => {
                if (q.type === "single") types.single++;
                else if (q.type === "multiple") types.multiple++;
                else types.other++;
            });

            return types;
        },
        hasQuestionsWithImages() {
            return this.test.questions.some(
                (question) =>
                    question.options &&
                    question.options.some((option) => option.image)
            );
        },
    },
    watch: {
        editTestId: {
            immediate: true,
            handler(newVal) {
                if (newVal) {
                    this.loadTestForEditing(newVal);
                } else {
                    this.isEditing = false;
                    this.resetTest();
                }
            },
        },
    },
    methods: {
        getcorrect_answers(question) {
            if (
                !question.correct_answers ||
                !Array.isArray(question.correct_answers)
            ) {
                question.correct_answers = [""];
            }
            return question.correct_answers;
        },
        getQuestionTypeLabel(type) {
            const labels = {
                single: "Одиночный выбор",
                multiple: "Множественный выбор",
                "true-false": "Да/Нет",
                text: "Свободный ввод",
                matching: "Сопоставление",
            };
            return labels[type] || type;
        },

        async loadTestForEditing(testId) {
            this.loading = true;
            this.error = "";

            try {
                const tests = await loadTests();
                const testToEdit = tests.find((t) => t.id === testId);

                if (testToEdit) {
                    this.test = JSON.parse(JSON.stringify(testToEdit));
                    this.isEditing = true;

                    // Нормализуем данные теста (переименовываем поля и добавляем отсутствующие)
                    this.normalizeTestData(this.test);

                    // Убедимся, что у всех вопросов есть ID
                    this.test.questions.forEach((question, index) => {
                        if (!question.id) {
                            question.id = Date.now() + index;
                        }
                    });
                } else {
                    this.error = "Тест не найден";
                }
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        moveQuestionUp(index) {
            if (index > 0) {
                const questions = [...this.test.questions];
                const temp = questions[index];
                questions[index] = questions[index - 1];
                questions[index - 1] = temp;
                this.test.questions = questions;
            }
        },

        moveQuestionDown(index) {
            if (index < this.test.questions.length - 1) {
                const questions = [...this.test.questions];
                const temp = questions[index];
                questions[index] = questions[index + 1];
                questions[index + 1] = temp;
                this.test.questions = questions;
            }
        },

        duplicateQuestion(index) {
            const originalQuestion = this.test.questions[index];
            const duplicatedQuestion = JSON.parse(
                JSON.stringify(originalQuestion)
            );

            // Генерируем новый ID и обновляем текст
            duplicatedQuestion.id = Date.now() + Math.random();
            duplicatedQuestion.text = `${duplicatedQuestion.text} (копия)`;

            // Вставляем после оригинального вопроса
            this.test.questions.splice(index + 1, 0, duplicatedQuestion);
        },

        validateQuestion(question) {
            if (!question.text.trim()) return false;

            switch (question.type) {
                case "single":
                    return (
                        question.options.length >= 2 &&
                        question.options.some((opt) => opt.correct)
                    );
                case "multiple":
                    return (
                        question.options.length >= 2 &&
                        question.options.some((opt) => opt.correct)
                    );
                case "true-false":
                    return question.correct_answer !== undefined;
                case "text":
                    return (
                        question.correct_answers &&
                        question.correct_answers.length > 0 &&
                        question.correct_answers.every((a) => a && a.trim())
                    );
                case "matching":
                    return (
                        question.pairs.length >= 2 &&
                        question.pairs.every(
                            (p) => p.left.trim() && p.right.trim()
                        )
                    );
                default:
                    return false;
            }
        },

        resetQuestionAnswers(question) {
            // Сохраняем ID и основные поля
            const id = question.id;
            const text = question.text;
            const points = question.points;
            const image = question.image;

            // Полностью пересоздаем объект вопроса в зависимости от типа
            const newQuestion = {
                id: id,
                text: text || "",
                type: question.type,
                points: points || 1,
                image: image || null,
            };

            switch (question.type) {
                case "single":
                case "multiple":
                    newQuestion.options = [
                        { text: "", correct: false, image: null },
                        { text: "", correct: false, image: null },
                    ];
                    break;
                case "true-false":
                    newQuestion.correct_answer = undefined;
                    break;
                case "text":
                    newQuestion.correct_answers = [""]; // Используем camelCase
                    break;
                case "matching":
                    newQuestion.pairs = [
                        {
                            left: "",
                            right: "",
                            leftImage: null,
                            rightImage: null,
                        },
                        {
                            left: "",
                            right: "",
                            leftImage: null,
                            rightImage: null,
                        },
                    ];
                    break;
            }

            // Заменяем вопрос в массиве
            const index = this.test.questions.findIndex(
                (q) => q.id === question.id
            );
            if (index !== -1) {
                this.test.questions.splice(index, 1, newQuestion);
            }
        },

        addQuestion() {
            const newQuestion = {
                id: Date.now() + Math.random(),
                text: "",
                type: "single",
                points: 1,
                image: null,
                options: [
                    { text: "", correct: false, image: null },
                    { text: "", correct: false, image: null },
                ],
            };

            this.test.questions.push(newQuestion);
        },

        removeQuestion(index) {
            if (confirm("Вы уверены, что хотите удалить этот вопрос?")) {
                this.test.questions.splice(index, 1);
            }
        },

        addOption(qIndex) {
            this.test.questions[qIndex].options.push({
                text: "",
                correct: false,
                image: null,
            });
        },

        removeOption(qIndex, oIndex) {
            this.test.questions[qIndex].options.splice(oIndex, 1);
        },

        addcorrect_answer(qIndex) {
            const question = this.test.questions[qIndex];
            if (!question.correct_answers) {
                question.correct_answers = [""];
            }
            question.correct_answers.push("");
        },

        removecorrect_answer(qIndex, aIndex) {
            const question = this.test.questions[qIndex];
            if (
                question.correct_answers &&
                question.correct_answers.length > 1
            ) {
                question.correct_answers.splice(aIndex, 1);
            }
        },

        addMatchingPair(qIndex) {
            this.test.questions[qIndex].pairs.push({
                left: "",
                right: "",
                leftImage: null,
                rightImage: null,
            });
        },

        removeMatchingPair(qIndex, pIndex) {
            this.test.questions[qIndex].pairs.splice(pIndex, 1);
        },

        addGradeLevel() {
            this.test.grading.push({ minScore: 0, max_score: 0, grade: "" });
        },

        removeGradeLevel(index) {
            this.test.grading.splice(index, 1);
        },

        async handleImageUpload(event, qIndex) {
            const file = event.target.files[0];
            if (file && this.validateImageFile(file)) {
                const base64 = await this.fileToBase64(file);
                this.test.questions[qIndex].image = base64;
            }
            event.target.value = "";
        },

        async handleOptionImageUpload(event, qIndex, oIndex) {
            const file = event.target.files[0];
            if (file && this.validateImageFile(file)) {
                const base64 = await this.fileToBase64(file);
                this.test.questions[qIndex].options[oIndex].image = base64;
            }
            event.target.value = "";
        },

        async handleLeftImageUpload(event, qIndex, pIndex) {
            const file = event.target.files[0];
            if (file && this.validateImageFile(file)) {
                const base64 = await this.fileToBase64(file);
                this.test.questions[qIndex].pairs[pIndex].leftImage = base64;
            }
            event.target.value = "";
        },

        async handleRightImageUpload(event, qIndex, pIndex) {
            const file = event.target.files[0];
            if (file && this.validateImageFile(file)) {
                const base64 = await this.fileToBase64(file);
                this.test.questions[qIndex].pairs[pIndex].rightImage = base64;
            }
            event.target.value = "";
        },

        removeQuestionImage(qIndex) {
            this.test.questions[qIndex].image = null;
        },

        removeOptionImage(qIndex, oIndex) {
            this.test.questions[qIndex].options[oIndex].image = null;
        },

        removeLeftImage(qIndex, pIndex) {
            this.test.questions[qIndex].pairs[pIndex].leftImage = null;
        },

        removeRightImage(qIndex, pIndex) {
            this.test.questions[qIndex].pairs[pIndex].rightImage = null;
        },

        validateImageFile(file) {
            const validTypes = [
                "image/jpeg",
                "image/jpg",
                "image/png",
                "image/gif",
            ];
            const maxSize = 2 * 1024 * 1024; // 2MB

            if (!validTypes.includes(file.type)) {
                alert("Пожалуйста, выберите файл в формате JPG, PNG или GIF");
                return false;
            }

            if (file.size > maxSize) {
                alert("Размер файла не должен превышать 2MB");
                return false;
            }

            return true;
        },

        fileToBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = () => resolve(reader.result);
                reader.onerror = (error) => reject(error);
            });
        },

        openImageManager(
            qIndex,
            oIndex = null,
            pIndex = null,
            imageType = "question"
        ) {
            this.imageManager.currentQuestionIndex = qIndex;
            this.imageManager.currentOptionIndex = oIndex;
            this.imageManager.currentPairIndex = pIndex;
            this.imageManager.imageType = imageType;
            this.imageManager.selectedImage = null;

            this.collectExistingImages();

            this.$nextTick(() => {
                const modalElement =
                    document.getElementById("imageManagerModal");
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                }
            });
        },

        collectExistingImages() {
            const images = new Set();

            this.test.questions.forEach((question) => {
                if (question.image) images.add(question.image);

                if (question.options) {
                    question.options.forEach((option) => {
                        if (option.image) images.add(option.image);
                    });
                }

                if (question.pairs) {
                    question.pairs.forEach((pair) => {
                        if (pair.leftImage) images.add(pair.leftImage);
                        if (pair.rightImage) images.add(pair.rightImage);
                    });
                }
            });

            this.imageManager.images = Array.from(images);
        },

        async handleManagerImageUpload(event) {
            const files = Array.from(event.target.files);
            for (const file of files) {
                if (this.validateImageFile(file)) {
                    const base64 = await this.fileToBase64(file);
                    if (!this.imageManager.images.includes(base64)) {
                        this.imageManager.images.push(base64);
                    }
                }
            }
            event.target.value = "";
        },

        selectImageInManager(image) {
            this.imageManager.selectedImage = image;
        },

        applySelectedImage() {
            if (!this.imageManager.selectedImage) return;

            const {
                currentQuestionIndex,
                currentOptionIndex,
                currentPairIndex,
                imageType,
            } = this.imageManager;

            switch (imageType) {
                case "question":
                    this.test.questions[currentQuestionIndex].image =
                        this.imageManager.selectedImage;
                    break;
                case "option":
                    this.test.questions[currentQuestionIndex].options[
                        currentOptionIndex
                    ].image = this.imageManager.selectedImage;
                    break;
                case "left":
                    this.test.questions[currentQuestionIndex].pairs[
                        currentPairIndex
                    ].leftImage = this.imageManager.selectedImage;
                    break;
                case "right":
                    this.test.questions[currentQuestionIndex].pairs[
                        currentPairIndex
                    ].rightImage = this.imageManager.selectedImage;
                    break;
            }

            const modalElement = document.getElementById("imageManagerModal");
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
            }
        },

        async saveTest() {
            this.loading = true;
            this.error = "";

            try {
                // Отладочный вывод для проверки структуры данных
                console.log(
                    "Данные перед сохранением:",
                    JSON.stringify(this.test, null, 2)
                );

                // Проверяем вопросы типа "text"
                this.test.questions.forEach((q, index) => {
                    if (q.type === "text") {
                        console.log(`Вопрос ${index + 1} (text):`, {
                            correct_answers: q.correct_answers,
                            hascorrect_answers: !!q.correct_answers,
                            length: q.correct_answers
                                ? q.correct_answers.length
                                : 0,
                        });
                    }
                });

                if (this.isEditing) {
                    this.test.id = this.editTestId;
                    await saveTest(this.test);
                    this.$emit("test-updated");
                    this.showToast("Тест успешно обновлен!", "success");
                } else {
                    this.test.id = Date.now();
                    await saveTest(this.test);
                    this.$emit("test-created");
                    this.resetTest();
                    this.showToast("Тест успешно создан!", "success");
                }
            } catch (error) {
                this.error = error.message;
                this.$emit("error", error.message);
            } finally {
                this.loading = false;
            }
        },

        cancelEdit() {
            this.$emit("edit-cancelled");
        },

        async deleteTest() {
            if (
                confirm(
                    "Вы уверены, что хотите удалить этот тест? Все результаты этого теста также будут удалены."
                )
            ) {
                this.loading = true;
                this.error = "";

                try {
                    await deleteTest(this.test.id);
                    this.$emit("test-deleted");
                    this.showToast("Тест успешно удален!", "success");
                } catch (error) {
                    this.error = error.message;
                    this.$emit("error", error.message);
                } finally {
                    this.loading = false;
                }
            }
        },

        resetTest() {
            this.test = {
                id: null,
                title: "",
                description: "",
                timeLimit: 30,
                settings: {
                    requireUserName: false,
                    showUserNameInResults: true,
                    shuffleQuestions: false,
                    shuffleAnswers: false,
                    shuffleSingleChoice: true,
                    shuffleMultipleChoice: true,
                    shuffleMatching: true,
                },
                grading: [
                    {
                        minScore: 0,
                        max_score: 59,
                        grade: "Неудовлетворительно",
                    },
                    { minScore: 60, max_score: 74, grade: "Удовлетворительно" },
                    { minScore: 75, max_score: 89, grade: "Хорошо" },
                    { minScore: 90, max_score: 100, grade: "Отлично" },
                ],
                questions: [],
            };
            this.isEditing = false;
        },

        async exportTest() {
            this.loading = true;
            this.error = "";

            try {
                const blob = await exportTest(this.test);
                const url = URL.createObjectURL(blob);
                const link = document.createElement("a");
                link.href = url;
                link.download = `test-${this.test.title || "export"}.json`;
                link.click();
                URL.revokeObjectURL(url);
            } catch (error) {
                this.error = error.message;
                this.$emit("error", error.message);
            } finally {
                this.loading = false;
            }
        },

        showImportModal() {
            this.importPreview = null;
            this.importErrors = [];
            this.selectedFile = null;
            if (this.$refs.fileInputCreator) {
                this.$refs.fileInputCreator.value = "";
            }

            this.$nextTick(() => {
                const modalElement =
                    document.getElementById("importModalCreator");
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

        importTestInCreator() {
            if (!this.importPreview || this.importErrors.length > 0) return;

            try {
                this.test = {
                    ...this.importPreview,
                    id: null,
                    title: this.importPreview.title + " (импорт)",
                };

                this.normalizeTestData(this.test);

                const modalElement =
                    document.getElementById("importModalCreator");
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                }

                this.showToast("Тест загружен для редактирования!", "success");
            } catch (error) {
                console.error("Ошибка импорта:", error);
                this.showToast("Ошибка при импорте теста", "danger");
            }
        },

        normalizeTestData(test) {
            // Убедимся, что все необходимые поля существуют
            if (!test.settings) {
                test.settings = {
                    requireUserName: false,
                    showUserNameInResults: true,
                    shuffleQuestions: false,
                    shuffleAnswers: false,
                    shuffleSingleChoice: true,
                    shuffleMultipleChoice: true,
                    shuffleMatching: true,
                };
            }

            if (!test.grading || test.grading.length === 0) {
                test.grading = [
                    {
                        minScore: 0,
                        max_score: 59,
                        grade: "Неудовлетворительно",
                    },
                    { minScore: 60, max_score: 74, grade: "Удовлетворительно" },
                    { minScore: 75, max_score: 89, grade: "Хорошо" },
                    { minScore: 90, max_score: 100, grade: "Отлично" },
                ];
            }

            if (!test.questions) {
                test.questions = [];
            }

            // Нормализуем каждый вопрос
            test.questions.forEach((question) => {
                // Для типа "text" обрабатываем оба варианта названия поля
                if (question.type === "text") {
                    // Если поле называется correct_answers (из базы данных), переименовываем в correct_answers
                    if (question.correct_answers && !question.correct_answers) {
                        question.correct_answers = question.correct_answers;
                        delete question.correct_answers;
                    }

                    // Убедимся, что correct_answers существует и является массивом
                    if (
                        !question.correct_answers ||
                        !Array.isArray(question.correct_answers)
                    ) {
                        question.correct_answers = [""];
                    }
                }

                // Для типов с options
                if (["single", "multiple"].includes(question.type)) {
                    if (!question.options || !Array.isArray(question.options)) {
                        question.options = [
                            { text: "", correct: false, image: null },
                            { text: "", correct: false, image: null },
                        ];
                    }
                }

                // Для типа matching
                if (question.type === "matching") {
                    if (!question.pairs || !Array.isArray(question.pairs)) {
                        question.pairs = [
                            {
                                left: "",
                                right: "",
                                leftImage: null,
                                rightImage: null,
                            },
                            {
                                left: "",
                                right: "",
                                leftImage: null,
                                rightImage: null,
                            },
                        ];
                    }
                }

                // Убедимся, что у опций есть все необходимые поля
                if (question.options) {
                    question.options.forEach((option) => {
                        if (option.correct === undefined) {
                            option.correct = false;
                        }
                        if (option.image === undefined) {
                            option.image = null;
                        }
                    });
                }
            });
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
.question-card:hover {
    border-color: #0d6efd;
}

.image-preview-container {
    max-width: 100%;
}

.image-item {
    cursor: pointer;
    transition: all 0.3s ease;
}

.image-item:hover {
    transform: scale(1.05);
}

.option-item {
    border-left: 3px solid #e9ecef;
    padding-left: 15px;
    margin-bottom: 15px;
}

.matching-pair {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    border: 1px solid #e9ecef;
}

.statistics-card {
    transition: all 0.3s ease;
}

.statistics-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}
</style>
