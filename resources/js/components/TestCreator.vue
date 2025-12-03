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

                <button
                    v-if="isEditing"
                    @click="cancelEdit"
                    class="btn btn-outline-secondary"
                >
                    <i class="bi bi-arrow-left"></i> Назад
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
            <div class="card mb-5">
                <div
                    class="card-header bg-success text-white d-flex justify-content-between align-items-center"
                >
                    <h5 class="mb-0">Вопросы теста</h5>
                    <span class="badge bg-light text-dark"
                        >{{ test.questions.length }} вопросов</span
                    >
                    <span
                        v-if="errorQuestionsCount > 0"
                        class="badge bg-danger"
                    >
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        {{ errorQuestionsCount }} ошибок
                    </span>
                </div>
                <div
                    v-if="errorQuestionsCount > 0"
                    class="alert alert-danger m-3 mb-0 py-2"
                >
                    <div class="d-flex align-items-center">
                        <i
                            class="bi bi-exclamation-triangle-fill fs-5 me-2"
                        ></i>
                        <div>
                            <strong>Обнаружены ошибки в вопросах:</strong>
                            <div class="mt-1">
                                <template
                                    v-for="item in questionsWithErrors"
                                    :key="item.index"
                                >
                                    <span
                                        class="badge bg-danger bg-opacity-25 text-danger border border-danger me-2 mb-1"
                                    >
                                        Вопрос {{ item.index + 1 }}
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
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
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body py-2">
                                    <h6 class="card-title mb-0">
                                        {{ questionTypes.sorting }}
                                    </h6>
                                    <small class="text-muted">Сортировка</small>
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
                                        <option value="sorting">
                                            Сортировка (ранжирование)
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
                            <!-- В TestCreator.vue добавьте в шаблон -->

                            <!-- Сортировка (ранжирование) -->
                            <div
                                v-if="question.type === 'sorting'"
                                class="mb-3"
                            >
                                <label class="form-label"
                                    >Элементы для сортировки (перетащите для
                                    изменения порядка):</label
                                >

                                <div
                                    class="sorting-items-list"
                                    @dragover.prevent
                                    @drop="onSortingDrop($event, qIndex)"
                                >
                                    <div
                                        v-for="(item, iIndex) in question.items"
                                        :key="item.id"
                                        class="sorting-item card mb-2"
                                        draggable="true"
                                        @dragstart="
                                            onSortingDragStart($event, iIndex)
                                        "
                                        @dragover.prevent
                                        :data-index="iIndex"
                                    >
                                        <div class="card-body p-3">
                                            <div
                                                class="d-flex align-items-center"
                                            >
                                                <div
                                                    class="drag-handle me-3 text-muted"
                                                    style="cursor: grab"
                                                >
                                                    <i
                                                        class="bi bi-grip-vertical fs-5"
                                                    ></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="mb-2">
                                                        <label
                                                            class="form-label small mb-1"
                                                            >Текст элемента
                                                            {{
                                                                iIndex + 1
                                                            }}:</label
                                                        >
                                                        <input
                                                            v-model="item.text"
                                                            type="text"
                                                            class="form-control"
                                                            placeholder="Введите текст элемента"
                                                        />
                                                    </div>

                                                    <!-- Изображение элемента -->
                                                    <div class="mb-2">
                                                        <label
                                                            class="form-label small mb-1"
                                                            >Изображение
                                                            элемента:</label
                                                        >

                                                        <div
                                                            v-if="item.image"
                                                            class="mb-2"
                                                        >
                                                            <div
                                                                class="image-preview-container position-relative d-inline-block"
                                                            >
                                                                <img
                                                                    :src="
                                                                        item.image
                                                                    "
                                                                    class="img-thumbnail"
                                                                    style="
                                                                        max-height: 100px;
                                                                    "
                                                                />
                                                                <button
                                                                    @click="
                                                                        removeSortingItemImage(
                                                                            qIndex,
                                                                            iIndex
                                                                        )
                                                                    "
                                                                    class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                                                    type="button"
                                                                >
                                                                    <i
                                                                        class="bi bi-x"
                                                                    ></i>
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <div
                                                            class="input-group"
                                                        >
                                                            <input
                                                                type="file"
                                                                @change="
                                                                    handleSortingItemImageUpload(
                                                                        $event,
                                                                        qIndex,
                                                                        iIndex
                                                                    )
                                                                "
                                                                accept="image/*"
                                                                class="form-control form-control-sm"
                                                            />
                                                            <button
                                                                class="btn btn-outline-secondary btn-sm"
                                                                type="button"
                                                                @click="
                                                                    openImageManager(
                                                                        qIndex,
                                                                        iIndex,
                                                                        null,
                                                                        'sorting'
                                                                    )
                                                                "
                                                            >
                                                                <i
                                                                    class="bi bi-image"
                                                                ></i>
                                                                Выбрать
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="ms-3">
                                                    <button
                                                        @click="
                                                            removeSortingItem(
                                                                qIndex,
                                                                iIndex
                                                            )
                                                        "
                                                        class="btn btn-outline-danger btn-sm"
                                                        :disabled="
                                                            question.items
                                                                .length <= 2
                                                        "
                                                        title="Удалить элемент"
                                                    >
                                                        <i
                                                            class="bi bi-trash"
                                                        ></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="mt-2 text-muted small">
                                                <i
                                                    class="bi bi-info-circle"
                                                ></i>
                                                Правильная позиция:
                                                {{ iIndex + 1 }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    @click="addSortingItem(qIndex)"
                                    class="btn btn-outline-primary btn-sm mt-2"
                                >
                                    <i class="bi bi-plus-circle"></i> Добавить
                                    элемент
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
            <div
                class="fixed-bottom p-3 bg-white border-top shadow-lg d-flex justify-content-center gap-3"
            >
                <button
                    @click="saveTest"
                    :disabled="!isTestValid"
                    class="btn btn-success btn-lg text-white px-5"
                    :class="{ 'opacity-50': !isTestValid }"
                >
                    <i class="bi bi-check-circle"></i>
                    {{ isEditing ? "Обновить тест" : "Сохранить тест" }}
                </button>

                <!-- Опционально можно добавить кнопку отмены -->
                <button
                    v-if="isEditing"
                    @click="cancelEdit"
                    class="btn btn-outline-secondary btn-lg"
                >
                    <i class="bi bi-x-circle"></i> Отмена
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
import bootstrap from "bootstrap/dist/js/bootstrap.bundle.min.js";
export default {
    name: "TestCreator",
    emits: ["error"],
    props: ["editTestId", "datasend", "showToast", "changeCurrentView"],

    data() {
        return {
            test: {
                id: null,
                title: "",
                description: "",
                timeLimit: 30,
                settings: {
                    requireUserName: true,
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
            let valid =
                this.test.title.trim() &&
                this.test.questions.length > 0 &&
                this.test.questions.every((q) => {
                    let valid = this.validateQuestion(q) == null;
                    console.log(valid);

                    return valid;
                });

            return valid;
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
                sorting: 0, // Добавлено
                other: 0,
            };

            this.test.questions.forEach((q) => {
                if (q.type === "single") types.single++;
                else if (q.type === "multiple") types.multiple++;
                else if (q.type === "sorting") types.sorting++;
                else types.other++;
            });

            return types;
        },
        hasQuestionsWithImages() {
            return this.test.questions.some(
                (question) => question.options /*&&
                    question.options.some((option) => option.image)*/
            );
        },
        errorQuestionsCount() {
            return this.test.questions.filter((q) => this.hasQuestionError(q))
                .length;
        },

        // Список вопросов с ошибками
        questionsWithErrors() {
            return this.test.questions
                .map((q, index) => ({
                    index: index,
                    question: q,
                    error: this.validateQuestion(q),
                }))
                .filter((item) => item.error !== null);
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
        removeSortingItemImage(qIndex, iIndex) {
            this.test.questions[qIndex].items[iIndex].image = null;
        },
        onSortingItemMoved(qIndex, event) {
            const question = this.test.questions[qIndex];
            const items = [...question.items];
            const movedItem = items[event.oldIndex];

            // Удаляем элемент со старой позиции
            items.splice(event.oldIndex, 1);
            // Вставляем элемент на новую позицию
            items.splice(event.newIndex, 0, movedItem);

            // Обновляем массив элементов
            question.items = items;

            // Обновляем правильный порядок
            this.updateCorrectOrder(qIndex);

            // Принудительное обновление для реактивности
            this.$forceUpdate();
        },
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
                sorting: "Сортировка/Ранжирование",
            };
            return labels[type] || type;
        },

        async loadTestForEditing(testId) {
            this.loading = true;
            this.error = "";

            try {
                this.datasend("tests/" + testId, "GET").then((response) => {
                    const testToEdit = response.data;
                    this.test = JSON.parse(JSON.stringify(testToEdit));
                    this.isEditing = true;

                    this.normalizeTestData(this.test);

                    this.test.questions.forEach((question, index) => {
                        if (!question.id) {
                            question.id = Date.now() + index;
                        }
                    });
                });
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
            // Проверка текста вопроса
            if (!question.text || question.text.trim() === "") {
                return "Не заполнен текст вопроса";
            }

            // Проверка в зависимости от типа вопроса
            switch (question.type) {
                case "single":
                    if (!question.options || question.options.length < 2) {
                        return "Должно быть не менее 2 вариантов ответа";
                    }

                    // Проверяем заполненность всех вариантов
                    for (let i = 0; i < question.options.length; i++) {
                        if (
                            !question.options[i].text ||
                            question.options[i].text.trim() === ""
                        ) {
                            if (question.options[i].image === null) {
                                return `Вариант ответа ${i + 1} не заполнен`;
                            }
                        }
                    }

                    if (!question.options.some((opt) => opt.correct)) {
                        return "Не выбран правильный ответ";
                    }

                    // Проверяем что выбран только один ответ для single
                    const correctCount = question.options.filter(
                        (opt) => opt.correct
                    ).length;
                    if (correctCount > 1) {
                        return "Для одиночного выбора должен быть выбран только один вариант";
                    }
                    break;

                case "multiple":
                    if (!question.options || question.options.length < 2) {
                        return "Должно быть не менее 2 вариантов ответа";
                    }

                    // Проверяем заполненность всех вариантов
                    for (let i = 0; i < question.options.length; i++) {
                        if (
                            !question.options[i].text ||
                            question.options[i].text.trim() === ""
                        ) {
                            if (question.options[i].image === null) {
                                return `Вариант ответа ${i + 1} не заполнен`;
                            }
                        }
                    }

                    if (!question.options.some((opt) => opt.correct)) {
                        return "Не выбраны правильные ответы";
                    }
                    break;

                case "true-false":
                    if (
                        question.correct_answer === undefined ||
                        question.correct_answer === null
                    ) {
                        return "Не выбран правильный ответ";
                    }
                    break;
                case "sorting":
                    if (!question.items || question.items.length < 2) {
                        return "Должно быть не менее 2 элементов для сортировки";
                    }

                    // Проверяем заполненность всех элементов
                    for (let i = 0; i < question.items.length; i++) {
                        const item = question.items[i];
                        if (!item.text || item.text.trim() === "") {
                            if (!item.image) {
                                return `Элемент ${
                                    i + 1
                                } не заполнен (нужен текст или изображение)`;
                            }
                        }
                    }

                    // Проверяем правильный порядок
                    if (
                        !question.correctOrder ||
                        question.correctOrder.length !== question.items.length
                    ) {
                        return "Некорректный правильный порядок";
                    }
                    break;
                case "text":
                    if (
                        !question.correct_answers ||
                        question.correct_answers.length === 0
                    ) {
                        return "Не указаны правильные ответы";
                    }

                    // Проверяем каждый правильный ответ
                    for (let i = 0; i < question.correct_answers.length; i++) {
                        if (
                            !question.correct_answers[i] ||
                            question.correct_answers[i].trim() === ""
                        ) {
                            return `Правильный ответ ${i + 1} не заполнен`;
                        }
                    }
                    break;

                case "matching":
                    if (!question.pairs || question.pairs.length < 2) {
                        return "Должно быть не менее 2 пар для сопоставления";
                    }

                    // Проверяем каждую пару
                    for (let i = 0; i < question.pairs.length; i++) {
                        const pair = question.pairs[i];
                        const leftEmpty = !pair.left.trim() && !pair.leftImage;
                        const rightEmpty =
                            !pair.right.trim() && !pair.rightImage;

                        if (leftEmpty && rightEmpty) {
                            return `Пара ${i + 1} не заполнена`;
                        } else if (leftEmpty) {
                            return `Левая часть пары ${i + 1} не заполнена`;
                        } else if (rightEmpty) {
                            return `Правая часть пары ${i + 1} не заполнена`;
                        }
                    }
                    break;
            }

            return null; // Ошибок нет, валидация пройдена
        },

        // Новый метод для проверки, есть ли ошибка в вопросе
        hasQuestionError(question) {
            return this.validateQuestion(question) !== null;
        },

        // Получить текст ошибки для вопроса
        getQuestionErrorMessage(question) {
            return this.validateQuestion(question);
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
                        { text: "", correct: false },
                        { text: "", correct: false },
                    ];
                    break;
                case "true-false":
                    newQuestion.correct_answer = undefined;
                    break;
                case "text":
                    newQuestion.correct_answers = [""]; // Используем camelCase
                    break;
                case "sorting":
                    newQuestion.items = [
                        {
                            id: this.generateId(),
                            text: "Элемент 1",
                            image: null,
                            correctPosition: 0,
                        },
                        {
                            id: this.generateId(),
                            text: "Элемент 2",
                            image: null,
                            correctPosition: 1,
                        },
                    ];
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
        generateId() {
            return Date.now() + Math.floor(Math.random() * 1000);
        },
        addQuestion() {
            const newQuestion = {
                id: Date.now() + Math.random(),
                text: "",
                type: "single",
                points: 1,
                image: null,
                options: [
                    { text: "", correct: false },
                    { text: "", correct: false },
                ],
            };
            if (newQuestion.type === "sorting") {
                newQuestion.items = [
                    {
                        id: this.generateId(),
                        text: "Первый элемент",
                        image: null,
                        correctPosition: 0,
                    },
                    {
                        id: this.generateId(),
                        text: "Второй элемент",
                        image: null,
                        correctPosition: 1,
                    },
                ];
                newQuestion.correctOrder = [0, 1];
                // Удаляем options для типа sorting
                delete newQuestion.options;
            }
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
        onSortingDragStart(event, index) {
            event.dataTransfer.setData("text/plain", index.toString());
            event.currentTarget.classList.add("dragging");
        },

        onSortingDrop(event, qIndex) {
            event.preventDefault();
            const fromIndex = parseInt(
                event.dataTransfer.getData("text/plain")
            );
            const toElement = event.target.closest(".sorting-item");

            if (toElement) {
                const toIndex = parseInt(toElement.dataset.index);

                if (fromIndex !== toIndex && !isNaN(toIndex)) {
                    const question = this.test.questions[qIndex];
                    const items = [...question.items];
                    const [movedItem] = items.splice(fromIndex, 1);
                    items.splice(toIndex, 0, movedItem);

                    question.items = items;
                    this.updateCorrectOrder(qIndex);
                }
            }

            // Убираем класс dragging
            document
                .querySelectorAll(".sorting-item.dragging")
                .forEach((el) => {
                    el.classList.remove("dragging");
                });
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

        addSortingItem(qIndex) {
            const newId = this.generateId();
            const newItem = {
                id: newId,
                text: `Элемент ${this.test.questions[qIndex].items.length + 1}`,
                image: null,
                correctPosition: this.test.questions[qIndex].items.length,
            };

            this.test.questions[qIndex].items.push(newItem);
            this.updateCorrectOrder(qIndex);
        },

        removeSortingItem(qIndex, iIndex) {
            if (this.test.questions[qIndex].items.length > 2) {
                this.test.questions[qIndex].items.splice(iIndex, 1);
                this.updateCorrectOrder(qIndex);
            }
        },

        async handleSortingItemImageUpload(event, qIndex, iIndex) {
            const file = event.target.files[0];
            if (file && this.validateImageFile(file)) {
                const base64 = await this.fileToBase64(file);
                this.test.questions[qIndex].items[iIndex].image = base64;
                this.$forceUpdate(); // Принудительное обновление для отображения изображения
            }
            event.target.value = "";
        },

        removeSortingItemImageremoveSortingItemImage(qIndex, iIndex) {
            this.test.questions[qIndex].items[iIndex].image = null;
        },

        updateCorrectOrder(qIndex) {
            const question = this.test.questions[qIndex];
            // Правильный порядок - это текущий порядок элементов в массиве
            question.correctOrder = question.items.map((_, index) => index);

            // Обновляем correctPosition для каждого элемента
            question.items.forEach((item, index) => {
                item.correctPosition = index;
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
                // if (question.image) images.add(question.image);

                // if (question.options) {
                //     question.options.forEach((option) => {
                //         if (option.image) images.add(option.image);
                //     });
                // }

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

        saveTest() {
            this.loading = true;
            this.error = "";

            try {
                if (this.isEditing) {
                    this.test.id = this.editTestId;
                    console.log(this.test);
                    
                    this.datasend(`tests/${this.test.id}`, "PUT", this.test)
                        .then((response) => {
                            this.resetTest();
                            this.changeCurrentView();
                            this.showToast(response.message, "success");
                        })
                        .catch((err) => {
                            console.log(err);
                        });
                } else {
                    const testToSend = { ...this.test };
                    testToSend.tree_id = this.$route.params.id;
                    delete testToSend.id;

                    this.datasend(`tests`, "POST", testToSend)
                        .then((response) => {
                            this.resetTest();
                            this.changeCurrentView();
                            this.showToast(response.message, "success");
                        })
                        .catch((err) => {
                            console.error("Ошибка создания теста:", err);
                            this.error = "Ошибка при создании теста";
                            this.showToast(
                                "Ошибка при создании теста",
                                "danger"
                            );
                        });
                }
            } catch (error) {
                this.error = error.message;
                this.$emit("error", error.message);
            } finally {
                this.loading = false;
            }
        },

        cancelEdit() {
            this.resetTest();
            this.changeCurrentView();
        },

        deleteTest() {
            if (
                confirm(
                    "Вы уверены, что хотите удалить этот тест? Все результаты этого теста также будут удалены."
                )
            ) {
                this.loading = true;
                this.error = "";

                try {
                    this.datasend(
                        `tests/${this.test.id}`,
                        "DELETE",
                        this.test
                    ).then((response) => {
                        this.resetTest();
                        this.showToast(response.message, "success");
                        this.changeCurrentView();
                    });
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
                    requireUserName: true,
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
                    requireUserName: true,
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

                if (question.type === "sorting") {
                    if (!question.items || !Array.isArray(question.items)) {
                        question.items = [
                            {
                                id: this.generateId(),
                                text: "Первый элемент",
                                image: null,
                                correctPosition: 0,
                            },
                            {
                                id: this.generateId(),
                                text: "Второй элемент",
                                image: null,
                                correctPosition: 1,
                            },
                        ];
                    }

                    if (
                        !question.correctOrder ||
                        !Array.isArray(question.correctOrder)
                    ) {
                        question.correctOrder = question.items.map(
                            (_, index) => index
                        );
                    }

                    // Убедимся, что у каждого элемента есть ID
                    question.items.forEach((item, index) => {
                        if (!item.id) {
                            item.id = this.generateId();
                        }
                        if (item.correctPosition === undefined) {
                            item.correctPosition = index;
                        }
                    });
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
                        // if (option.image === undefined) {
                        //     option.image = null;
                        // }
                    });
                }
            });
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
.sortable-ghost {
    opacity: 0.5;
    background: #c8ebfb;
}

.sortable-chosen {
    background-color: #e9ecef;
}

.sortable-drag {
    opacity: 0.8;
    transform: rotate(5deg);
}

.sorting-items-list {
    min-height: 100px;
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.sorting-item {
    transition: all 0.3s ease;
    user-select: none;
}

.sorting-item:hover {
    border-color: #0d6efd;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.drag-handle {
    cursor: grab;
}

.drag-handle:active {
    cursor: grabbing;
}
</style>
