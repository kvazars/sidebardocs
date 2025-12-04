<template>
    <div class="test-runner">
        <!-- Выбор теста -->
        <div v-if="!selectedTest" class="test-selection">
            <div v-if="tests.length != 0" class="row">
                <h2 class="mb-4">
                    <i class="bi bi-play-circle"></i> Выберите тест для
                    прохождения
                </h2>
                <div
                    v-for="test in tests"
                    :key="test.id"
                    class="col-md-12 col-lg-12 mb-3"
                    @click="selectTest(test)"
                >
                    <div class="test-card card h-100">
                        <div class="card-body">
                            <h5 class="card-title">{{ test.title }}</h5>
                            <p class="card-text text-muted">
                                {{ test.description }}
                            </p>
                            <div class="d-flex justify-content-between text-sm">
                                <small
                                    ><i class="bi bi-question-circle"></i>
                                    {{ test.questions.length }} вопросов</small
                                >
                                <small
                                    ><i class="bi bi-clock"></i>
                                    {{ test.timeLimit }} мин</small
                                >
                            </div>
                            <div class="mt-2">
                                <small
                                    v-if="test.settings.shuffleQuestions"
                                    class="text-primary"
                                >
                                    <i class="bi bi-shuffle"></i> Перемеш.
                                    вопросы
                                </small>
                                <small
                                    v-if="test.settings.shuffleAnswers"
                                    class="text-success"
                                >
                                    <i class="bi bi-shuffle"></i> Перемеш.
                                    ответы
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ввод имени (если требуется) -->
        <div v-else-if="selectedTest && !userName" class="user-name-section">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-person"></i> Введите ваше имя
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Ваше имя:</label>
                        <input
                            v-model="tempUserName"
                            type="text"
                            class="form-control"
                            placeholder="Введите ваше имя"
                            maxlength="50"
                        />
                        <div class="form-text">
                            Имя будет сохранено вместе с результатами теста.
                        </div>
                    </div>
                    <button
                        @click="startTestWithName"
                        :disabled="!tempUserName.trim()"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-play-circle"></i> Начать тест
                    </button>
                </div>
            </div>
        </div>

        <!-- Прохождение теста -->
        <div v-else class="test-execution">
            <!-- Информация о пользователе -->
            <div v-if="userName" class="alert alert-info mb-3">
                <i class="bi bi-person"></i> Тестируемый:
                <strong>{{ userName }}</strong>
            </div>

            <!-- Информация о настройках теста -->
            <div
                v-if="
                    selectedTest.settings.shuffleQuestions ||
                    selectedTest.settings.shuffleAnswers
                "
                class="alert alert-info mb-3"
            >
                <i class="bi bi-shuffle"></i>
                <strong>Настройки теста:</strong>
                <span
                    v-if="selectedTest.settings.shuffleQuestions"
                    class="ms-2"
                >
                    <i class="bi bi-check-circle"></i> Вопросы перемешаны
                </span>
                <span v-if="selectedTest.settings.shuffleAnswers" class="ms-2">
                    <i class="bi bi-check-circle"></i> Ответы перемешаны
                </span>
            </div>

            <!-- Предупреждение о незаполненном вопросе -->
            <div
                v-if="validationError"
                class="alert alert-danger alert-dismissible fade show mb-3"
            >
                <i class="bi bi-exclamation-circle"></i>
                {{ validationError }}
                <button
                    type="button"
                    class="btn-close"
                    @click="validationError = ''"
                ></button>
            </div>

            <div
                v-if="showTimeWarning"
                class="alert alert-warning alert-dismissible fade show mb-3"
            >
                <i class="bi bi-clock-history"></i>
                Осталось меньше 30 секунд! Неотвеченные вопросы не будут
                засчитаны.
            </div>

            <!-- Таймер и прогресс -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="progress-container mb-3">
                        <div class="progress mb-2" style="height: 20px">
                            <div
                                class="progress-bar progress-bar-striped progress-bar-animated"
                                :style="{ width: progress + '%' }"
                                title="`Вопрос ${currentQuestionIndex + 1} из ${selectedTest.questions.length}`"
                            ></div>
                        </div>
                        <div
                            v-if="timeLeft <= 60"
                            class="progress-bar bg-warning"
                            :style="{
                                width:
                                    (answeredQuestions.size /
                                        selectedTest.questions.length) *
                                        100 +
                                    '%',
                                position: 'absolute',
                                left: 0,
                                opacity: 0.3,
                            }"
                            :title="`Отвечено: ${answeredQuestions.size} из ${selectedTest.questions.length}`"
                        ></div>
                        <div class="progress-markers">
                            <div
                                v-for="i in selectedTest.questions.length"
                                :key="i"
                                class="question-marker"
                                :style="{
                                    left:
                                        ((i - 0.5) /
                                            selectedTest.questions.length) *
                                            100 +
                                        '%',
                                }"
                                :class="{
                                    answered: answeredQuestions.has(i - 1),
                                    current: currentQuestionIndex === i - 1,
                                    unanswered:
                                        !answeredQuestions.has(i - 1) &&
                                        currentQuestionIndex !== i - 1,
                                }"
                                @click="goToQuestion(i - 1)"
                                :title="`Вопрос ${i}${
                                    answeredQuestions.has(i - 1)
                                        ? ' (отвечен)'
                                        : ' (не отвечен)'
                                }`"
                            ></div>
                        </div>
                    </div>
                    <small class="text-muted">
                        Вопрос {{ currentQuestionIndex + 1 }} из
                        {{ selectedTest.questions.length }}
                        (отвечено: {{ answeredQuestions.size }}/{{
                            selectedTest.questions.length
                        }})
                    </small>
                </div>
                <div class="col-md-4">
                    <div
                        class="timer alert"
                        :class="[timerClass, { expired: timeLeft <= 0 }]"
                    >
                        <i class="bi bi-clock"></i>
                        {{
                            timeLeft > 0
                                ? formatTime(timeLeft)
                                : "Время истекло!"
                        }}
                    </div>
                </div>
            </div>

            <!-- Вопрос -->
            <div
                class="card mb-4"
                :class="{
                    'required-question':
                        !answeredQuestions.has(currentQuestionIndex),
                    'answered-question':
                        answeredQuestions.has(currentQuestionIndex),
                }"
            >
                <div
                    class="card-header bg-light d-flex justify-content-between align-items-center"
                >
                    <h5 class="mb-0">
                        <span
                            v-if="!answeredQuestions.has(currentQuestionIndex)"
                            class="text-danger me-2"
                        >
                            <i class="bi bi-exclamation-circle"></i>
                        </span>
                        <span v-else class="text-success me-2">
                            <i class="bi bi-check-circle"></i>
                        </span>
                        Вопрос {{ currentQuestionIndex + 1 }}
                        <small
                            v-if="selectedTest.settings.shuffleQuestions"
                            class="text-muted"
                        >
                            (порядок перемешан)
                        </small>
                    </h5>
                    <span class="badge bg-secondary"
                        >{{ currentQuestion.points }} баллов</span
                    >
                </div>
                <div class="card-body">
                    <!-- Текст вопроса и изображение -->
                    <div class="mb-3">
                        <h6 class="card-title">{{ currentQuestion.text }}</h6>
                        <!-- Изображение вопроса -->
                        <div
                            v-if="currentQuestion.image"
                            class="mt-3 text-center"
                        >
                            <img
                                :src="currentQuestion.image"
                                class="img-fluid rounded"
                                style="max-height: 300px"
                            />
                        </div>
                    </div>

                    <p class="text-muted">
                        <i class="bi bi-star"></i> Баллы:
                        {{ currentQuestion.points }}
                    </p>

                    <div
                        v-if="currentQuestion.type === 'single'"
                        class="answers"
                    >
                        <div
                            v-for="(option, index) in currentShuffledOptions"
                            :key="index"
                            class="form-check mb-3 border rounded d-flex align-items-center px-3"
                        >
                            <div class="">
                                <input
                                    :id="
                                        'option' +
                                        index +
                                        'q' +
                                        currentQuestion.id
                                    "
                                    type="radio"
                                    :name="'question' + currentQuestionIndex"
                                    :value="String(option.originalIndex)"
                                    v-model.number="currentUserAnswer"
                                    class="form-check-input px-2"
                                />
                            </div>
                            <label
                                :for="
                                    'option' + index + 'q' + currentQuestion.id
                                "
                                class="form-check-label w-100 h-100 py-3"
                            >
                                <div class="px-1">
                                    <span class="flex-grow-1">{{
                                        option.text
                                    }}</span>
                                    <!-- Изображение варианта -->
                                    <div v-if="option.image" class="ms-3">
                                        <img
                                            :src="option.image"
                                            class="img-thumbnail"
                                            style="max-height: 80px"
                                        />
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Множественный выбор - ПРОСТОЙ ПОДХОД -->
                    <div
                        v-else-if="currentQuestion.type === 'multiple'"
                        class="answers"
                    >
                        <div
                            v-for="(option, index) in currentShuffledOptions"
                            :key="index"
                            class="form-check mb-3 border rounded d-flex align-items-center px-3"
                        >
                            <div>
                                <input
                                    :id="
                                        'option' +
                                        index +
                                        'q' +
                                        currentQuestion.id
                                    "
                                    type="checkbox"
                                    :value="option.originalIndex"
                                    v-model="currentMultipleChoiceAnswer"
                                    class="form-check-input"
                                />
                            </div>
                            <label
                                :for="
                                    'option' + index + 'q' + currentQuestion.id
                                "
                                class="form-check-label w-100 h-100 py-3"
                            >
                                <div>
                                    <span class="flex-grow-1">{{
                                        option.text
                                    }}</span>
                                    <!-- Изображение варианта -->
                                    <div v-if="option.image" class="ms-3">
                                        <img
                                            :src="option.image"
                                            class="img-thumbnail"
                                            style="max-height: 80px"
                                        />
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Да/Нет -->
                    <div
                        v-else-if="currentQuestion.type === 'true-false'"
                        class="answers"
                    >
                        <div class="row">
                            <div class="col-md-6">
                                <div
                                    class="form-check border rounded d-flex align-items-center px-3"
                                >
                                    <div>
                                        <input
                                            :id="
                                                'true-' +
                                                currentQuestionIndex +
                                                'q' +
                                                currentQuestion.id
                                            "
                                            type="radio"
                                            :name="
                                                'question' +
                                                currentQuestionIndex
                                            "
                                            value="true"
                                            v-model="currentUserAnswer"
                                            class="form-check-input"
                                        />
                                    </div>
                                    <label
                                        :for="
                                            'true-' +
                                            currentQuestionIndex +
                                            'q' +
                                            currentQuestion.id
                                        "
                                        class="form-check-label fs-5 fw-bold text-success form-check-label w-100 h-100 py-3"
                                    >
                                        <i class="bi bi-check-circle"></i>
                                        Да
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div
                                    class="form-check border rounded d-flex align-items-center px-3"
                                >
                                    <div>
                                        <input
                                            :id="
                                                'false-' +
                                                currentQuestionIndex +
                                                'q' +
                                                currentQuestion.id
                                            "
                                            type="radio"
                                            :name="
                                                'question' +
                                                currentQuestionIndex
                                            "
                                            value="false"
                                            v-model="currentUserAnswer"
                                            class="form-check-input"
                                        />
                                    </div>
                                    <label
                                        :for="
                                            'false-' +
                                            currentQuestionIndex +
                                            'q' +
                                            currentQuestion.id
                                        "
                                        class="form-check-label fs-5 fw-bold text-danger form-check-label w-100 h-100 py-3"
                                    >
                                        <i class="bi bi-x-circle"></i> Нет
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Свободный ввод -->
                    <div
                        v-else-if="currentQuestion.type === 'text'"
                        class="answers"
                    >
                        <input
                            v-model="currentUserAnswer"
                            class="form-control"
                            placeholder="Введите ваш ответ..."
                        />
                    </div>

                    <!-- Сопоставление -->
                    <div
                        v-else-if="currentQuestion.type === 'matching'"
                        class="answers"
                    >
                        <div
                            v-for="(pair, index) in currentShuffledPairs"
                            :key="index"
                            class="row mb-3 align-items-center border rounded p-3"
                        >
                            <div class="col-md-5">
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold me-2">{{
                                        pair.left
                                    }}</span>
                                    <!-- Изображение левой части -->
                                    <div v-if="pair.leftImage">
                                        <img
                                            :src="pair.leftImage"
                                            class="img-thumbnail"
                                            style="max-height: 60px"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <i class="bi bi-arrow-right fs-4"></i>
                            </div>
                            <div class="col-md-5">
                                <select
                                    v-model="currentMatchingAnswer[index]"
                                    class="form-select"
                                    @change="onMatchingChange(index, $event)"
                                >
                                    <option value="">
                                        Выберите соответствие
                                    </option>
                                    <option
                                        v-for="rightOption in currentShuffledRightOptions"
                                        :key="rightOption"
                                        :value="rightOption"
                                    >
                                        {{ rightOption }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else-if="currentQuestion.type === 'sorting'"
                        class="answers"
                    >
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-arrow-down-up"></i>
                            <strong>Инструкция:</strong> Расположите элементы в
                            правильном порядке, перетаскивая их с помощью мыши
                        </div>

                        <!-- Область сортировки -->
                        <div
                            class="sorting-area"
                            @dragover.prevent
                            @drop="handleDrop"
                        >
                            <div
                                v-for="(itemId, index) in currentSortingAnswer"
                                :key="itemId"
                                class="sorting-item mb-2 border rounded p-3"
                                draggable="true"
                                @dragstart="handleDragStart($event, index)"
                                @dragover.prevent
                                :data-index="index"
                            >
                                <div class="d-flex align-items-center">
                                    <div
                                        class="drag-handle me-3 text-muted"
                                        style="cursor: grab"
                                    >
                                        <i class="bi bi-grip-vertical fs-5"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div>
                                            {{ getSortingItemText(itemId) }}
                                        </div>
                                        <!-- Изображение элемента -->
                                        <div
                                            v-if="getSortingItemImage(itemId)"
                                            class="mt-2 text-center"
                                        >
                                            <img
                                                :src="
                                                    getSortingItemImage(itemId)
                                                "
                                                class="img-thumbnail"
                                                style="max-height: 100px"
                                            />
                                        </div>
                                    </div>
                                    <div class="position-badge ms-3">
                                        <span class="badge bg-secondary">{{
                                            index + 1
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Навигация -->
            <div class="d-flex justify-content-between">
                <button
                    @click="previousQuestion"
                    :disabled="currentQuestionIndex === 0"
                    class="btn btn-outline-primary"
                >
                    <i class="bi bi-arrow-left"></i> Назад
                </button>

                <button
                    v-if="
                        currentQuestionIndex < selectedTest.questions.length - 1
                    "
                    @click="validateAndNextQuestion"
                    class="btn btn-primary"
                >
                    Следующий <i class="bi bi-arrow-right"></i>
                </button>

                <button
                    v-else
                    @click="validateAndFinishTest"
                    class="btn btn-success text-white"
                >
                    <i class="bi bi-check-circle"></i> Завершить тест
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "TestRunner",
    props: ["datasend", "loadData", "tests", "showToast"],
    data() {
        return {
            selectedTest: null,
            currentQuestionIndex: 0,
            userAnswers: [],
            timeLeft: 0,
            timer: null,
            userName: "",
            tempUserName: "",
            // Для перемешивания
            shuffledQuestions: [],
            shuffledOptionsMap: new Map(),
            shuffledPairsMap: new Map(),
            // Для валидации
            validationError: "",
            // Для отслеживания отвеченных вопросов
            answeredQuestions: new Set(),
            // Временные массивы для сложных типов вопросов
            multipleChoiceAnswers: [], // Для множественного выбора
            matchingAnswers: [], // Для сопоставления
            userAnswersByOriginalIndex: new Map(),
            // Для навигации по отображенным вопросам
            displayedQuestionToOriginal: new Map(),
            originalToDisplayedQuestion: new Map(),
        };
    },
    computed: {
        currentQuestion() {
            if (!this.selectedTest) return null;

            if (
                this.selectedTest.settings.shuffleQuestions &&
                this.shuffledQuestions.length > 0
            ) {
                return this.shuffledQuestions[this.currentQuestionIndex];
            }

            return this.selectedTest.questions[this.currentQuestionIndex];
        },

        currentShuffledOptions() {
            // Если есть перемешанные варианты - используем их
            const shuffled = this.shuffledOptionsMap.get(
                this.currentQuestionIndex
            );
            if (shuffled && shuffled.length > 0) {
                return shuffled;
            }

            // Иначе используем оригинальные варианты
            if (this.currentQuestion && this.currentQuestion.options) {
                return this.currentQuestion.options.map((option, index) => ({
                    ...option,
                    originalIndex: index,
                }));
            }

            return [];
        },

        currentShuffledPairs() {
            const data = this.shuffledPairsMap.get(this.currentQuestionIndex);
            if (data && data.options) {
                return data.options;
            }

            // Иначе используем оригинальные пары
            if (this.currentQuestion && this.currentQuestion.options) {
                return this.currentQuestion.options.map((pair, index) => ({
                    ...pair,
                    originalIndex: index,
                }));
            }

            return [];
        },

        currentShuffledRightOptions() {
            const data = this.shuffledPairsMap.get(this.currentQuestionIndex);
            if (data && data.rightOptions) {
                return data.rightOptions;
            }

            // Иначе используем оригинальные правые части
            if (this.currentQuestion && this.currentQuestion.options) {
                return this.currentQuestion.options.map((pair) => pair.right);
            }

            return [];
        },

        progress() {
            if (!this.selectedTest) return 0;
            return (
                ((this.currentQuestionIndex + 1) /
                    this.selectedTest.questions.length) *
                100
            );
        },

        timerClass() {
            if (this.timeLeft < 300) {
                return "timer-warning alert-danger";
            } else if (this.timeLeft < 600) {
                return "alert-warning";
            }
            return "alert-info";
        },
        currentOriginalIndex() {
            if (!this.currentQuestion) return -1;
            return this.currentQuestion.originalIndex !== undefined
                ? this.currentQuestion.originalIndex
                : this.currentQuestionIndex;
        },

        showTimeWarning() {
            return this.timeLeft <= 30 && this.timeLeft > 0;
        },

        currentUserAnswer: {
            get() {
                if (this.currentOriginalIndex === -1) {
                    return this.getDefaultAnswerValue();
                }

                const answer = this.userAnswersByOriginalIndex.get(
                    this.currentOriginalIndex
                );

                // Если это вопрос с одиночным выбором и ответ - строка, преобразуем в число
                if (
                    this.currentQuestion?.type === "single" &&
                    typeof answer === "string"
                ) {
                    return answer !== "" ? Number(answer) : "";
                }

                return answer || this.getDefaultAnswerValue();
            },
            set(value) {
                if (this.currentOriginalIndex !== -1) {
                    // Сохраняем значение как есть
                    this.userAnswersByOriginalIndex.set(
                        this.currentOriginalIndex,
                        value
                    );
                    // Обновляем статус отвеченности
                    this.updateAnsweredStatus();
                }
            },
        },

        currentMultipleChoiceAnswer: {
            get() {
                // Получаем ответ напрямую из хранилища
                if (this.currentOriginalIndex === -1) return [];

                const answer = this.userAnswersByOriginalIndex.get(
                    this.currentOriginalIndex
                );

                if (Array.isArray(answer)) {
                    // Фильтруем пустые значения и преобразуем в числа
                    return answer
                        .filter(
                            (item) =>
                                item !== null &&
                                item !== undefined &&
                                item !== ""
                        )
                        .map((item) => Number(item));
                }

                // Если ответ не массив, возвращаем пустой массив
                return [];
            },
            set(value) {
                if (this.currentOriginalIndex !== -1) {
                    // Фильтруем пустые значения и сохраняем как числа
                    const filteredValue = Array.isArray(value)
                        ? value
                              .filter(
                                  (item) =>
                                      item !== null &&
                                      item !== undefined &&
                                      item !== ""
                              )
                              .map((item) => Number(item))
                        : [];

                    this.userAnswersByOriginalIndex.set(
                        this.currentOriginalIndex,
                        filteredValue
                    );
                    this.updateAnsweredStatus();
                }
            },
        },

        currentMatchingAnswer: {
            get() {
                const originalIndex = this.currentOriginalIndex;
                const answer =
                    this.userAnswersByOriginalIndex.get(originalIndex);

                if (Array.isArray(answer)) {
                    return [...answer];
                }

                // Создаем массив нужной длины для сопоставления
                const length = this.currentQuestion?.options?.length || 0;
                const newArray = new Array(length).fill("");

                // Сохраняем инициализированный массив
                if (originalIndex !== -1) {
                    this.userAnswersByOriginalIndex.set(originalIndex, [
                        ...newArray,
                    ]);
                }

                return newArray;
            },
            set(value) {
                const originalIndex = this.currentOriginalIndex;
                if (originalIndex !== -1) {
                    this.userAnswersByOriginalIndex.set(
                        originalIndex,
                        Array.isArray(value) ? [...value] : []
                    );
                }
            },
        },

        currentSortingAnswer: {
            get() {
                const originalIndex = this.currentOriginalIndex;
                const answer =
                    this.userAnswersByOriginalIndex.get(originalIndex);

                if (Array.isArray(answer) && answer.length > 0) {
                    return [...answer];
                }

                // Инициализируем массив с исходным порядком
                const defaultOrder = this.currentQuestion?.options
                    ? this.currentQuestion.options.map((_, index) => index)
                    : [];

                if (originalIndex !== undefined && defaultOrder.length > 0) {
                    this.userAnswersByOriginalIndex.set(originalIndex, [
                        ...defaultOrder,
                    ]);
                    return [...defaultOrder];
                }

                return defaultOrder;
            },
            set(value) {
                const originalIndex = this.currentOriginalIndex;
                if (originalIndex !== undefined) {
                    this.userAnswersByOriginalIndex.set(originalIndex, [
                        ...value,
                    ]);
                    this.updateAnsweredStatus();
                }
            },
        },
    },
    userAnswers: {
        handler(newVal) {
            if (this.currentQuestion) {
                this.$nextTick(() => {
                    this.validateCurrentQuestion();
                });
            }
        },
        deep: true,
    },

    currentQuestionIndex() {
        // При смене вопроса проверяем валидацию
        this.$nextTick(() => {
            this.validateCurrentQuestion();
        });
    },
    methods: {
        getSortingItemText(itemId) {
            if (!this.currentQuestion?.options?.[itemId]) {
                return `Элемент ${itemId + 1}`;
            }

            const item = this.currentQuestion.options[itemId];
            return item.text || item.title || item.name || `${itemId + 1}`;
        },

        getSortingItemImage(itemId) {
            return this.currentQuestion?.options?.[itemId]?.image;
        },

        handleDragStart(event, index) {
            event.dataTransfer.setData("text/plain", index.toString());
        },

        handleDrop(event) {
            event.preventDefault();
            const fromIndex = parseInt(
                event.dataTransfer.getData("text/plain")
            );
            const toElement = event.target.closest(".sorting-item");

            if (toElement) {
                // Получаем индекс элемента-цели
                const sortingItems = document.querySelectorAll(".sorting-item");
                const toIndex = Array.from(sortingItems).indexOf(toElement);

                if (fromIndex !== toIndex && !isNaN(toIndex) && toIndex >= 0) {
                    const newOrder = [...this.currentSortingAnswer];
                    const [movedItem] = newOrder.splice(fromIndex, 1);
                    newOrder.splice(toIndex, 0, movedItem);
                    this.currentSortingAnswer = newOrder;
                }
            }
        },

        resetSortingOrder() {
            const defaultOrder = this.currentQuestion?.options
                ? this.currentQuestion.options.map((_, index) => index)
                : [];
            this.currentSortingAnswer = defaultOrder;
        },

        getDefaultAnswerValue() {
            if (!this.currentQuestion) return "";

            switch (this.currentQuestion.type) {
                case "multiple":
                case "matching":
                    return [];
                case "single":
                    return null;
                default:
                    return "";
            }
        },
        onAnswerChange() {
            // Для radio и true/false
            this.updateAnsweredStatus();
            this.clearValidationError();
        },

        onMultipleChoiceChange() {
            // Для множественного выбора
            // Копируем массив в userAnswers
            this.userAnswers[this.currentQuestionIndex] = [
                ...this.multipleChoiceAnswers,
            ];
            this.updateAnsweredStatus();
            this.clearValidationError();
        },

        onTextInput() {
            // Для текстового ввода
            this.updateAnsweredStatus();
            this.clearValidationError();
        },

        onMatchingChange(index) {
            // Для сопоставления
            // Создаем новый массив с обновленным значением
            const newAnswers = [...this.matchingAnswers];
            newAnswers[index] = event.target.value; // или value, если это не событие

            // Обновляем основной ответ
            const originalIndex = this.currentOriginalIndex;
            if (originalIndex !== -1) {
                this.userAnswersByOriginalIndex.set(originalIndex, [
                    ...newAnswers,
                ]);
            }

            // Также обновляем временный массив
            this.matchingAnswers = [...newAnswers];

            this.updateAnsweredStatus();
            this.clearValidationError();
        },

        updateAnsweredStatus() {
            const answer = this.currentUserAnswer;
            const question = this.currentQuestion;

            if (!question) return;

            let hasAnswer = false;

            switch (question.type) {
                case "single":
                case "true-false":
                case "text":
                    hasAnswer =
                        answer !== null &&
                        answer !== undefined &&
                        answer !== "" &&
                        answer.toString().trim() !== "";
                    break;
                case "multiple":
                    hasAnswer =
                        Array.isArray(answer) &&
                        answer.length > 0 &&
                        !answer.some(
                            (item) =>
                                item === null ||
                                item === undefined ||
                                item === ""
                        );
                    break;
                case "sorting":
                    hasAnswer =
                        Array.isArray(answer) &&
                        answer.length === (question.options?.length || 0);
                    break;
                case "matching":
                    if (
                        Array.isArray(answer) &&
                        question.options &&
                        answer.length === question.options.length
                    ) {
                        const matchingData = this.shuffledPairsMap.get(
                            this.currentQuestionIndex
                        );
                        if (matchingData) {
                            // Для перемешанных вариантов
                            hasAnswer = answer.every((item, index) => {
                                const availableOptions =
                                    matchingData.rightOptions || [];
                                return (
                                    availableOptions.includes(item) &&
                                    item.trim() !== ""
                                );
                            });
                        } else {
                            // Для неперемешанных вариантов
                            hasAnswer = answer.every(
                                (item) => item && item.trim() !== ""
                            );
                        }
                    }
                    break;
            }

            const displayedIndex = this.currentQuestionIndex;
            if (hasAnswer) {
                this.answeredQuestions.add(displayedIndex);
            } else {
                this.answeredQuestions.delete(displayedIndex);
            }
        },

        selectTest(test) {
            this.selectedTest = JSON.parse(JSON.stringify(test));

            // Сбрасываем имя пользователя
            this.userName = "";
            this.tempUserName = "";
        },

        startTestWithName() {
            if (this.tempUserName.trim()) {
                this.userName = this.tempUserName.trim();
                this.initializeTest();
            }
        },

        initializeTest() {
            // Перемешиваем вопросы если нужно
            if (this.selectedTest.settings.shuffleQuestions) {
                this.shuffleQuestions();
            } else {
                this.shuffledQuestions = this.selectedTest.questions.map(
                    (q, idx) => ({
                        ...q,
                        originalIndex: idx,
                    })
                );
            }

            // Инициализируем маппинги для перемешанных вопросов
            this.displayedQuestionToOriginal.clear();
            this.originalToDisplayedQuestion.clear();

            this.shuffledQuestions.forEach((question, displayedIndex) => {
                const originalIndex =
                    question.originalIndex !== undefined
                        ? question.originalIndex
                        : displayedIndex;
                this.displayedQuestionToOriginal.set(
                    displayedIndex,
                    originalIndex
                );
                this.originalToDisplayedQuestion.set(
                    originalIndex,
                    displayedIndex
                );
            });

            // Инициализируем userAnswersByOriginalIndex
            this.userAnswersByOriginalIndex.clear();
            this.selectedTest.questions.forEach((question, originalIndex) => {
                switch (question.type) {
                    case "multiple":
                    case "matching":
                        this.userAnswersByOriginalIndex.set(originalIndex, []);
                        break;
                    case "single":
                        this.userAnswersByOriginalIndex.set(
                            originalIndex,
                            null
                        ); // null вместо ""
                        break;
                    default:
                        this.userAnswersByOriginalIndex.set(originalIndex, "");
                }
            });

            this.timeLeft = this.selectedTest.timeLimit * 60;
            this.startTimer();
            this.initializeQuestion();
        },

        createIndexMapping() {
            if (this.selectedTest.settings.shuffleQuestions) {
                this.shuffleQuestions();
                // Создаем маппинг: отображаемый индекс -> оригинальный индекс
                this.displayToOriginalIndex = new Map();
                this.originalToDisplayIndex = new Map();

                this.shuffledQuestions.forEach((question, displayIndex) => {
                    const originalIndex = question.originalIndex;
                    this.displayToOriginalIndex.set(
                        displayIndex,
                        originalIndex
                    );
                    this.originalToDisplayIndex.set(
                        originalIndex,
                        displayIndex
                    );
                });
            } else {
                this.shuffledQuestions = [...this.selectedTest.questions];
                // Без перемешивания индексы совпадают
                this.displayToOriginalIndex = new Map(
                    this.selectedTest.questions.map((_, index) => [
                        index,
                        index,
                    ])
                );
                this.originalToDisplayIndex = new Map(
                    this.selectedTest.questions.map((_, index) => [
                        index,
                        index,
                    ])
                );
            }
        },

        getCurrentAnswer() {
            const originalIndex = this.getOriginalIndex(
                this.currentQuestionIndex
            );
            return this.userAnswers[originalIndex];
        },

        // Установка ответа для текущего отображаемого вопроса
        setCurrentAnswer(value) {
            const originalIndex = this.getOriginalIndex(
                this.currentQuestionIndex
            );
            // В Vue 3 простое присваивание работает для реактивных массивов
            this.userAnswers[originalIndex] = value;

            // Если нужно форсировать обновление (редко требуется)
            // this.userAnswers = [...this.userAnswers];
        },

        // Получение оригинального индекса по отображаемому
        getOriginalIndex(displayIndex) {
            return (
                this.displayToOriginalIndex.get(displayIndex) || displayIndex
            );
        },

        // Получение отображаемого индекса по оригинальному
        getDisplayIndex(originalIndex) {
            return (
                this.originalToDisplayIndex.get(originalIndex) || originalIndex
            );
        },

        shuffleQuestions() {
            const questions = [...this.selectedTest.questions];
            this.shuffledQuestions = questions
                .map((question, originalIndex) => ({
                    ...question,
                    originalIndex: originalIndex, // Сохраняем оригинальный индекс
                }))
                .sort(() => Math.random() - 0.5);
        },

        shuffleSortingItems() {
            if (!this.currentQuestion.options) return;

            const items = [...this.currentQuestion.options];
            const shuffledOrder = items
                .map((_, index) => index)
                .sort(() => Math.random() - 0.5);

            const originalIndex = this.currentOriginalIndex;
            if (originalIndex !== undefined) {
                this.userAnswersByOriginalIndex.set(
                    originalIndex,
                    shuffledOrder
                );
            }
        },

        initializeQuestion() {
            if (!this.currentQuestion) return;

            // Получаем оригинальный индекс текущего вопроса
            const originalIndex = this.currentOriginalIndex;
            if (this.currentQuestion.type === "sorting") {
                this.shuffleSortingItems();
            }
            // Перемешиваем варианты ответов если нужно
            if (this.shouldShuffleAnswers()) {
                this.shuffleQuestionOptions();
                this.shuffleMatchingOptions();
            }

            // Инициализируем временные массивы для сложных типов вопросов
            const currentAnswer = this.userAnswers[this.currentQuestionIndex];

            if (this.currentQuestion.type === "multiple") {
                // Для множественного выбора
                this.multipleChoiceAnswers = Array.isArray(currentAnswer)
                    ? [...currentAnswer]
                    : [];
            } else {
                this.multipleChoiceAnswers = [];
            }

            if (this.currentQuestion.type === "matching") {
                // Для сопоставления
                if (Array.isArray(currentAnswer)) {
                    this.matchingAnswers = [...currentAnswer];
                } else {
                    // Создаем массив нужной длины
                    const length = this.currentQuestion.options
                        ? this.currentQuestion.options.length
                        : 0;
                    this.matchingAnswers = new Array(length).fill("");

                    // Если массив не инициализирован, инициализируем его
                    if (
                        !Array.isArray(currentAnswer) &&
                        originalIndex !== undefined
                    ) {
                        this.userAnswersByOriginalIndex.set(originalIndex, [
                            ...this.matchingAnswers,
                        ]);
                    }
                }
            } else {
                this.matchingAnswers = [];
            }

            if (this.currentQuestion.type === "sorting") {
                // Проверяем, есть ли у вопроса элементы для сортировки
                if (
                    !this.currentQuestion.options ||
                    this.currentQuestion.options.length === 0
                ) {
                    console.warn(
                        "Вопрос типа sorting не имеет элементов items"
                    );
                    return;
                }

                // Инициализируем ответ для сортировки, если его еще нет
                if (originalIndex !== undefined) {
                    const existingAnswer =
                        this.userAnswersByOriginalIndex.get(originalIndex);

                    if (
                        !Array.isArray(existingAnswer) ||
                        existingAnswer.length === 0
                    ) {
                        // Создаем начальный порядок (0, 1, 2, ...)
                        const defaultOrder = this.currentQuestion.options.map(
                            (_, index) => index
                        );

                        // Если нужно перемешивать, перемешиваем начальный порядок
                        if (
                            this.selectedTest.settings.shuffleAnswers &&
                            this.selectedTest.settings.shuffleSorting !== false
                        ) {
                            // Создаем копию и перемешиваем
                            const shuffledOrder = [...defaultOrder];
                            for (let i = shuffledOrder.length - 1; i > 0; i--) {
                                const j = Math.floor(Math.random() * (i + 1));
                                [shuffledOrder[i], shuffledOrder[j]] = [
                                    shuffledOrder[j],
                                    shuffledOrder[i],
                                ];
                            }
                            this.userAnswersByOriginalIndex.set(
                                originalIndex,
                                shuffledOrder
                            );
                        } else {
                            this.userAnswersByOriginalIndex.set(
                                originalIndex,
                                defaultOrder
                            );
                        }
                    }
                }
            }

            // Сбрасываем ошибку валидации при инициализации вопроса
            this.validationError = "";

            // Обновляем статус отвеченности
            this.updateAnsweredStatus();
        },
        shouldShuffleAnswers() {
            // Основная проверка - если shuffleAnswers = false, не перемешиваем
            if (!this.selectedTest.settings.shuffleAnswers) {
                return false;
            }
            return true;
            // const questionType = this.currentQuestion.type;

            // Если shuffleAnswers = true, проверяем настройки для конкретных типов вопросов
            // (если они заданы, иначе считаем что перемешивать можно)
            // switch (questionType) {
            //     case "single":
            //         return (
            //             this.selectedTest.settings.shuffleSingleChoice !== false
            //         );
            //     case "multiple":
            //         return (
            //             this.selectedTest.settings.shuffleMultipleChoice !==
            //             false
            //         );
            //     case "matching":
            //         return this.selectedTest.settings.shuffleMatching !== false;
            //     case "sorting":
            //         return this.selectedTest.settings.shuffleSorting !== false;
            //     default:
            //         return false; // Для других типов не перемешиваем
            // }
        },
        shuffleSortingItems() {
            if (!this.currentQuestion.options) return;

            const items = [...this.currentQuestion.options];
            const shuffledOrder = items
                .map((_, index) => index)
                .sort(() => Math.random() - 0.5);

            const originalIndex = this.currentOriginalIndex;
            if (originalIndex !== undefined) {
                this.userAnswersByOriginalIndex.set(
                    originalIndex,
                    shuffledOrder
                );
            }
        },

        shuffleQuestionOptions() {
            if (!this.currentQuestion.options) return;

            const options = [...this.currentQuestion.options];
            const shuffledOptions = options
                .map((option, index) => ({ ...option, originalIndex: index }))
                .sort(() => Math.random() - 0.5);

            this.shuffledOptionsMap.set(
                this.currentQuestionIndex,
                shuffledOptions
            );
        },

        shuffleMatchingOptions() {
            if (!this.currentQuestion.options) return;

            const pairs = [...this.currentQuestion.options];
            const shuffledPairs = pairs
                .map((pair, index) => ({ ...pair, originalIndex: index }))
                .sort(() => Math.random() - 0.5);

            const shuffledRightOptions = shuffledPairs
                .map((p) => p.right)
                .sort(() => Math.random() - 0.5);

            this.shuffledPairsMap.set(this.currentQuestionIndex, {
                pairs: shuffledPairs,
                rightOptions: shuffledRightOptions,
            });

            // Получаем оригинальный индекс текущего вопроса
            const originalIndex = this.currentOriginalIndex;
            const currentAnswer =
                this.userAnswersByOriginalIndex.get(originalIndex);

            // Инициализируем ответы для matching, если их еще нет
            if (
                !Array.isArray(currentAnswer) ||
                currentAnswer.length !== pairs.length
            ) {
                this.userAnswersByOriginalIndex.set(
                    originalIndex,
                    new Array(pairs.length).fill("")
                );
                this.matchingAnswers = new Array(pairs.length).fill("");
            }
        },

        startTimer() {
            this.timer = setInterval(() => {
                this.timeLeft--;
                if (this.timeLeft <= 0) {
                    clearInterval(this.timer);
                    // При истечении времени сразу завершаем без валидации
                    this.forceFinishTest();
                }
            }, 1000);
        },

        calculatePartialResult() {
            let totalScore = 0;
            let maxPossibleScore = 0; // Максимум за отвеченные вопросы
            let totalMaxScore = 0; // Общий максимум всех вопросов
            const questionResults = [];

            // Проходим по всем вопросам
            this.selectedTest.questions.forEach(
                (originalQuestion, originalIndex) => {
                    // Всегда добавляем в общий максимум
                    totalMaxScore += originalQuestion.points;

                    const userAnswer =
                        this.userAnswersByOriginalIndex.get(originalIndex);

                    // Проверяем, отвечен ли вопрос
                    let isAnswered = false;

                    switch (originalQuestion.type) {
                        case "single":
                        case "true-false":
                        case "text":
                            isAnswered =
                                userAnswer !== "" &&
                                userAnswer !== null &&
                                userAnswer !== undefined &&
                                userAnswer.toString().trim() !== "";
                            break;
                        case "multiple":
                            isAnswered =
                                Array.isArray(userAnswer) &&
                                userAnswer.length > 0;
                            break;
                        case "matching":
                            isAnswered =
                                Array.isArray(userAnswer) &&
                                userAnswer.length > 0 &&
                                userAnswer.every(
                                    (item) =>
                                        item !== null &&
                                        item !== undefined &&
                                        item.toString().trim() !== ""
                                );
                            break;
                    }

                    // Если вопрос не отвечен, добавляем как неотвеченный
                    if (!isAnswered) {
                        questionResults.push({
                            question: originalQuestion.text,
                            userAnswer: "Не отвечено (время истекло)",
                            correct_answer:
                                this.getCorrectAnswer(originalQuestion),
                            isCorrect: false,
                            score: 0,
                            max_score: originalQuestion.points,
                            originalIndex,
                            answered: false,
                            completed_under_timeout: true,
                        });
                        return; // Не учитываем в набранных баллах
                    }

                    // Добавляем максимальный балл вопроса
                    maxPossibleScore += originalQuestion.points;

                    // Рассчитываем результат для отвеченного вопроса
                    let isCorrect = false;
                    let score = 0;

                    switch (originalQuestion.type) {
                        case "single":
                            if (userAnswer === undefined || userAnswer === "") {
                                score = 0;
                                isCorrect = false;
                                break;
                            }

                            let correctOriginalIndex = -1;
                            if (originalQuestion.options) {
                                correctOriginalIndex =
                                    originalQuestion.options.findIndex(
                                        (opt) => opt.correct === true
                                    );
                            }

                            const selectedOriginalIndex = Number(userAnswer);
                            isCorrect =
                                selectedOriginalIndex === correctOriginalIndex;
                            score = isCorrect ? originalQuestion.points : 0;
                            break;

                        case "multiple":
                            if (
                                !Array.isArray(userAnswer) ||
                                userAnswer.length === 0
                            ) {
                                score = 0;
                                isCorrect = false;
                                break;
                            }

                            const correctOriginalIndices = [];
                            if (originalQuestion.options) {
                                originalQuestion.options.forEach((opt, idx) => {
                                    if (opt.correct === true) {
                                        correctOriginalIndices.push(idx);
                                    }
                                });
                            }

                            const selectedOriginalIndices = userAnswer.map(
                                (idx) => Number(idx)
                            );

                            // Проверяем, что выбраны ВСЕ правильные ответы
                            const hasAllCorrect = correctOriginalIndices.every(
                                (idx) => selectedOriginalIndices.includes(idx)
                            );

                            // Проверяем, что НЕ выбрано ни одного неправильного ответа
                            const hasNoWrong = selectedOriginalIndices.every(
                                (idx) => correctOriginalIndices.includes(idx)
                            );

                            // Ответ правильный только если выбраны все правильные и ни одного неправильного
                            isCorrect = hasAllCorrect && hasNoWrong;
                            score = isCorrect ? originalQuestion.points : 0;
                            break;

                        case "true-false":
                            // Обработка разных форматов
                            let correctPartialAnswer;

                            if (typeof originalQuestion.options === "string") {
                                correctPartialAnswer = originalQuestion.options;
                            } else if (
                                Array.isArray(originalQuestion.options)
                            ) {
                                correctPartialAnswer =
                                    originalQuestion.options[0] || "false";
                            } else {
                                correctPartialAnswer =
                                    originalQuestion.options?.toString() ||
                                    "false";
                            }

                            const userPartialAnswerStr = userAnswer?.toString();
                            isCorrect =
                                userPartialAnswerStr === correctPartialAnswer;
                            score = isCorrect ? originalQuestion.points : 0;
                            break;

                        case "text":
                            const options = originalQuestion.options || [];
                            const userAnswerText =
                                userAnswer?.toString().toLowerCase().trim() ||
                                "";
                            isCorrect = options.some(
                                (correct_answer) =>
                                    userAnswerText ===
                                    correct_answer.toLowerCase().trim()
                            );
                            score = isCorrect ? originalQuestion.points : 0;
                            break;

                        case "matching":
                            if (
                                !originalQuestion.options ||
                                originalQuestion.options.length === 0
                            ) {
                                score = 0;
                                isCorrect = false;
                                break;
                            }

                            if (
                                !Array.isArray(userAnswer) ||
                                userAnswer.length === 0
                            ) {
                                score = 0;
                                isCorrect = false;
                                break;
                            }

                            let correctPairs = 0;
                            userAnswer.forEach((userRightAnswer, pairIndex) => {
                                const originalPair =
                                    originalQuestion.options[pairIndex];
                                if (
                                    originalPair &&
                                    originalPair.right === userRightAnswer
                                ) {
                                    correctPairs++;
                                }
                            });

                            score =
                                (correctPairs /
                                    originalQuestion.options.length) *
                                originalQuestion.points;
                            isCorrect =
                                correctPairs ===
                                originalQuestion.options.length;
                            break;
                        case "sorting":
                            if (
                                !originalQuestion.options ||
                                originalQuestion.options.length === 0
                            ) {
                                score = 0;
                                isCorrect = false;
                                break;
                            }

                            if (
                                !Array.isArray(userAnswer) ||
                                userAnswer.length === 0
                            ) {
                                score = 0;
                                isCorrect = false;
                                break;
                            }

                            // Получаем правильный порядок из correctPosition
                            const correctOrder = [];
                            const positionMap = new Map();

                            // Собираем правильный порядок
                            originalQuestion.options.forEach((opt, index) => {
                                positionMap.set(
                                    index,
                                    opt.correctPosition || index
                                );
                            });

                            // Сортируем по correctPosition
                            const sortedByPosition = [
                                ...originalQuestion.options.keys(),
                            ].sort((a, b) => {
                                const posA = positionMap.get(a);
                                const posB = positionMap.get(b);
                                return posA - posB;
                            });

                            // Создаем маппинг: originalIndex -> его правильная позиция
                            const correctPositionForIndex = new Map();
                            sortedByPosition.forEach(
                                (originalIndex, correctPosition) => {
                                    correctPositionForIndex.set(
                                        originalIndex,
                                        correctPosition
                                    );
                                }
                            );

                            // Проверяем, полностью ли совпадает порядок
                            let isFullyCorrect = true;
                            userAnswer.forEach((userItemId, userPosition) => {
                                const shouldBeAtPosition =
                                    correctPositionForIndex.get(userItemId);
                                if (shouldBeAtPosition !== userPosition) {
                                    isFullyCorrect = false;
                                }
                            });

                            // Только полностью правильный ответ дает баллы
                            isCorrect = isFullyCorrect;
                            score = isCorrect ? originalQuestion.points : 0;
                            break;

                        default:
                            score = 0;
                            isCorrect = false;
                            break;
                    }

                    totalScore += score;

                    questionResults.push({
                        question: originalQuestion.text,
                        userAnswer: this.formatUserAnswerForDisplay(
                            originalQuestion,
                            originalIndex,
                            userAnswer
                        ),
                        correct_answer: this.getCorrectAnswer(originalQuestion),
                        isCorrect,
                        score,
                        max_score: originalQuestion.points,
                        originalIndex,
                        answered: true,
                        completed_under_timeout: true,
                    });
                }
            );

            // Теперь считаем процент от общего количества баллов
            const percentage =
                totalMaxScore > 0 ? (totalScore / totalMaxScore) * 100 : 0;

            const grade = this.calculateGrade(percentage);

            const result = {
                test_id: this.selectedTest.id,
                test_title: this.selectedTest.title,
                timestamp: new Date().toISOString(),
                total_score: totalScore,
                max_score: totalMaxScore, // Общий максимум всех вопросов
                answered_max_score: maxPossibleScore, // Максимум только отвеченных
                percentage: Math.round(percentage),
                grade,
                time_spent: this.selectedTest.timeLimit * 60,
                completed_with_timeout: true,
                answered_questions_count: this.answeredQuestions.size,
                total_questions_count: this.selectedTest.questions.length,
            };

            result.user_name = this.userName;

            result.question_results = questionResults;

            return result;
        },

        forceFinishTest() {
            // Останавливаем таймер
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }

            // Собираем результат
            const result = this.calculatePartialResult();

            // Отправляем результат
            this.datasend("results", "POST", result).then((response) => {
                // Сброс состояния
                this.selectedTest = null;
                this.userName = "";
                this.tempUserName = "";
                this.shuffledOptionsMap.clear();
                this.shuffledPairsMap.clear();
                this.answeredQuestions.clear();
                this.validationError = "";

                // Расчет дополнительной информации для сообщения
                const answeredPercentage =
                    result.total_questions_count > 0
                        ? Math.round(
                              (result.answered_questions_count /
                                  result.total_questions_count) *
                                  100
                          )
                        : 0;

                // Показываем сообщение
                this.showToast(
                    "⏰ Время истекло! Тест завершен автоматически.<br>" +
                        "Отвечено вопросов: " +
                        result.answered_questions_count +
                        " из " +
                        result.total_questions_count +
                        " (" +
                        answeredPercentage +
                        "%)<br>" +
                        "Набрано баллов: " +
                        result.total_score +
                        " из " +
                        result.max_score +
                        " (" +
                        result.percentage +
                        "%)",
                    "warning"
                );
                this.resetTestState();
            });
        },
        resetTestState() {
            // Сброс всех основных данных
            this.selectedTest = null;
            this.currentQuestionIndex = 0;
            this.userAnswers = []; // ОЧИСТКА ОТВЕТОВ
            this.timeLeft = 0;
            this.timer = null;
            this.userName = "";
            this.tempUserName = "";

            // Очистка всех перемешанных данных
            this.shuffledQuestions = [];
            this.shuffledOptionsMap.clear();
            this.shuffledPairsMap.clear();

            // Очистка валидации
            this.validationError = "";

            // Очистка отслеживания отвеченных вопросов
            this.answeredQuestions.clear();

            // Очистка временных массивов
            this.multipleChoiceAnswers = [];
            this.matchingAnswers = [];

            // Очистка маппингов
            this.userAnswersByOriginalIndex.clear();
            this.displayedQuestionToOriginal.clear();
            this.originalToDisplayedQuestion.clear();
            this.displayToOriginalIndex?.clear();
            this.originalToDisplayIndex?.clear();
        },

        formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins}:${secs.toString().padStart(2, "0")}`;
        },

        // ВАЛИДАЦИЯ ВОПРОСОВ
        validateCurrentQuestion() {
            this.validationError = "";
            const answer = this.currentUserAnswer;
            const question = this.currentQuestion;

            if (!question) {
                this.validationError = "Вопрос не найден";
                return false;
            }

            let isValid = false;

            switch (question.type) {
                case "single":
                    // Проверяем, что ответ не null, не undefined и не пустая строка
                    const isNumber = typeof answer === "number";
                    const isStringWithValue =
                        typeof answer === "string" && answer.trim() !== "";

                    isValid =
                        answer !== null &&
                        answer !== undefined &&
                        answer !== "" &&
                        (isNumber || isStringWithValue);

                    if (!isValid) {
                        this.validationError =
                            "Пожалуйста, выберите один вариант ответа";
                    }
                    break;

                case "multiple":
                    // Для multiple используем currentMultipleChoiceAnswer напрямую
                    const multipleAnswer = this.currentMultipleChoiceAnswer;

                    // Проверка 1: Это массив
                    if (Array.isArray(multipleAnswer)) {
                        // Проверка 2: Массив не пустой
                        if (multipleAnswer.length > 0) {
                            // Проверка 3: Все элементы валидны (не пустые строки)
                            isValid = multipleAnswer.every(
                                (item) =>
                                    item !== null &&
                                    item !== undefined &&
                                    item.toString().trim() !== ""
                            );

                            // Проверка 4: Все элементы - числа (индексы)
                            if (isValid) {
                                isValid = multipleAnswer.every(
                                    (item) =>
                                        typeof Number(item) === "number" &&
                                        !isNaN(Number(item))
                                );
                            }
                        } else {
                            isValid = false; // Пустой массив
                        }
                    } else {
                        isValid = false; // Не массив
                    }

                    if (!isValid) {
                        this.validationError =
                            "Пожалуйста, выберите хотя бы один вариант ответа";
                    }
                    break;

                case "true-false":
                    isValid = answer == "true" || answer == "false";
                    if (!isValid) {
                        this.validationError =
                            "Пожалуйста, выберите Да или Нет";
                    }
                    break;
                case "sorting":
                    isValid =
                        Array.isArray(answer) &&
                        answer.length === (question.options?.length || 0);

                    // ДОПОЛНИТЕЛЬНАЯ ПРОВЕРКА: все элементы должны быть уникальны
                    if (isValid) {
                        const uniqueSet = new Set(answer);
                        isValid = uniqueSet.size === answer.length;

                        // Проверяем, что все индексы в допустимом диапазоне
                        if (isValid) {
                            const maxIndex = question.options.length - 1;
                            isValid = answer.every(
                                (itemId) => itemId >= 0 && itemId <= maxIndex
                            );
                        }
                    }

                    if (!isValid) {
                        this.validationError =
                            "Пожалуйста, расположите все элементы в правильном порядке";
                    }
                    break;

                case "text":
                    isValid =
                        answer !== null &&
                        answer !== undefined &&
                        answer.toString().trim() !== "";

                    if (!isValid) {
                        this.validationError = "Пожалуйста, введите ваш ответ";
                    }
                    break;

                case "matching":
                    if (question.options) {
                        if (Array.isArray(answer)) {
                            // Основная проверка - что массив имеет правильную длину
                            // и все элементы не пустые
                            isValid = answer.length === question.options.length;

                            if (isValid) {
                                // Проверяем, что все элементы заполнены
                                isValid = answer.every((item) => {
                                    return (
                                        item !== null &&
                                        item !== undefined &&
                                        item.toString().trim() !== ""
                                    );
                                });
                            }

                            // Дополнительная проверка для перемешанных вариантов
                            if (isValid) {
                                const matchingData = this.shuffledPairsMap.get(
                                    this.currentQuestionIndex
                                );
                                if (matchingData && matchingData.rightOptions) {
                                    // Для перемешанных вариантов проверяем,
                                    // что выбранные значения есть в доступных вариантах
                                    isValid = answer.every((item) => {
                                        return matchingData.rightOptions.includes(
                                            item
                                        );
                                    });
                                }
                            }
                        }
                    }
                    if (!isValid) {
                        this.validationError =
                            "Пожалуйста, выберите соответствие для каждого элемента";
                    }
                    break;

                default:
                    this.validationError = "Неизвестный тип вопроса";
                    break;
            }

            // Обновляем статус отвеченности
            const displayedIndex = this.currentQuestionIndex;
            if (isValid) {
                this.answeredQuestions.add(displayedIndex);
            } else {
                this.answeredQuestions.delete(displayedIndex);
            }

            return isValid;
        },

        validateAndNextQuestion() {
            if (this.validateCurrentQuestion()) {
                this.nextQuestion();
            } else {
                // Прокрутка к ошибке
                // this.$nextTick(() => {
                //     const errorElement =
                //         document.querySelector(".alert-danger");
                //     if (errorElement) {
                //         errorElement.scrollIntoView({ behavior: "smooth" });
                //     }
                // });
            }
        },
        validateAllQuestions() {
            // Проверяем все вопросы по их отображаемым индексам
            for (
                let displayedIndex = 0;
                displayedIndex < this.shuffledQuestions.length;
                displayedIndex++
            ) {
                const question = this.shuffledQuestions[displayedIndex];
                const originalIndex =
                    question.originalIndex !== undefined
                        ? question.originalIndex
                        : displayedIndex;

                const answer =
                    this.userAnswersByOriginalIndex.get(originalIndex);

                let isValid = false;

                switch (question.type) {
                    case "single":
                    case "true-false":
                        isValid =
                            answer !== "" &&
                            answer !== null &&
                            answer !== undefined;
                        break;
                    case "multiple":
                        // Проверка, что выбрано хотя бы один вариант
                        isValid = Array.isArray(answer) && answer.length > 0;

                        // Дополнительная проверка валидности выбранных значений
                        if (
                            isValid &&
                            answer.some(
                                (item) =>
                                    item === null ||
                                    item === undefined ||
                                    item === ""
                            )
                        ) {
                            isValid = false;
                        }
                        break;
                    case "sorting":
                        // ОБНОВЛЕННАЯ ПРОВЕРКА ДЛЯ СОРТИРОВКИ
                        if (Array.isArray(answer) && question.options) {
                            isValid = answer.length === question.options.length;

                            // Проверяем уникальность
                            if (isValid) {
                                const uniqueSet = new Set(answer);
                                isValid = uniqueSet.size === answer.length;

                                // Проверяем допустимость индексов
                                if (isValid) {
                                    const maxIndex =
                                        question.options.length - 1;
                                    isValid = answer.every(
                                        (itemId) =>
                                            itemId >= 0 && itemId <= maxIndex
                                    );
                                }
                            }
                        }
                        break;
                    case "text":
                        // Проверка заполненности текстового поля
                        isValid =
                            answer !== null &&
                            answer !== undefined &&
                            answer.toString().trim() !== "";
                        break;
                    case "matching":
                        if (Array.isArray(answer) && question.options) {
                            // Упрощенная проверка - просто проверяем, что все элементы не пустые
                            isValid =
                                answer.length === question.options.length &&
                                answer.every(
                                    (item) =>
                                        item !== null &&
                                        item !== undefined &&
                                        item.toString().trim() !== ""
                                );
                        }
                        break;
                }

                if (!isValid) {
                    return {
                        isValid: false,
                        questionIndex: displayedIndex, // Возвращаем отображаемый индекс
                    };
                }
            }

            return { isValid: true };
        },

        validateAndFinishTest() {
            // Проверяем текущий вопрос
            if (this.timeLeft <= 0) {
                this.forceFinishTest();
                return;
            }
            if (!this.validateCurrentQuestion()) {
                // Прокрутка к ошибке
                this.$nextTick(() => {
                    const errorElement =
                        document.querySelector(".alert-danger");
                    // if (errorElement) {
                    //     errorElement.scrollIntoView({ behavior: "smooth" });
                    // }
                });
                return;
            }

            // Проверяем все вопросы
            const validationResult = this.validateAllQuestions();
            if (!validationResult.isValid) {
                // Переключаемся на первый неотвеченный вопрос
                this.currentQuestionIndex = validationResult.questionIndex;
                this.validationError = `Вопрос ${
                    validationResult.questionIndex + 1
                } не отвечен. Пожалуйста, ответьте на него`;

                // Прокрутка к ошибке
                this.$nextTick(() => {
                    const errorElement =
                        document.querySelector(".alert-danger");
                    // if (errorElement) {
                    //     errorElement.scrollIntoView({ behavior: "smooth" });
                    // }
                });
                return;
            }

            // Если все вопросы отвечены, завершаем тест
            this.finishTest();
        },

        clearValidationError() {
            this.validationError = "";
        },

        nextQuestion() {
            if (this.timeLeft <= 0) {
                return;
            }
            if (
                this.currentQuestionIndex <
                this.selectedTest.questions.length - 1
            ) {
                this.currentQuestionIndex++;
                this.validationError = "";
                this.initializeQuestion();

                // Прокрутка к началу вопроса
                this.$nextTick(() => {
                    const questionElement =
                        document.querySelector(".card.mb-4");
                    // if (questionElement) {
                    //     questionElement.scrollIntoView({ behavior: "smooth" });
                    // }
                });
            }
        },

        previousQuestion() {
            if (this.timeLeft <= 0) {
                return;
            }
            if (this.currentQuestionIndex > 0) {
                this.currentQuestionIndex--;
                this.validationError = "";
                this.initializeQuestion();

                // Прокрутка к началу вопроса
                this.$nextTick(() => {
                    const questionElement =
                        document.querySelector(".card.mb-4");
                    // if (questionElement) {
                    //     questionElement.scrollIntoView({ behavior: "smooth" });
                    // }
                });
            }
        },

        goToQuestion(displayIndex) {
            if (
                displayIndex >= 0 &&
                displayIndex < this.selectedTest.questions.length
            ) {
                this.currentQuestionIndex = displayIndex;
                this.validationError = "";
                this.initializeQuestion();

                this.$nextTick(() => {
                    const questionElement =
                        document.querySelector(".card.mb-4");
                });
            }
        },

        finishTest() {
            clearInterval(this.timer);
            const result = this.calculateResult();

            this.datasend("results", "POST", result).then((response) => {
                this.selectedTest = null;
                this.userName = "";
                this.tempUserName = "";
                this.shuffledOptionsMap.clear();
                this.shuffledPairsMap.clear();
                this.answeredQuestions.clear();
                this.validationError = "";

                this.showToast(
                    "Тест завершен!<br>" +
                        "Набрано баллов: " +
                        result.total_score +
                        " из " +
                        result.max_score +
                        " (" +
                        result.percentage +
                        "%)",
                    "success"
                );

                this.resetTestState();
            });
        },
        isOptionSelected(optionIndex) {
            const answer = this.getCurrentAnswer();
            if (!Array.isArray(answer)) return false;
            return answer.includes(optionIndex);
        },

        toggleMultipleChoiceOption(optionIndex, event) {
            // Создаем копию массива или инициализируем новый
            let currentAnswers = this.userAnswers[this.currentQuestionIndex];

            // Если массив не инициализирован или не является массивом
            if (!Array.isArray(currentAnswers)) {
                currentAnswers = [];
            }

            // Создаем новый массив для реактивности
            let newAnswers = [...currentAnswers];

            if (event.target.checked) {
                // Добавляем индекс, если его еще нет
                if (!newAnswers.includes(optionIndex)) {
                    newAnswers.push(optionIndex);
                }
            } else {
                // Удаляем индекс
                newAnswers = newAnswers.filter((idx) => idx !== optionIndex);
            }

            // Обновляем реактивно
            this.userAnswers[this.currentQuestionIndex] = newAnswers;

            // Обновляем answeredQuestions
            if (newAnswers.length > 0) {
                this.answeredQuestions.add(this.currentQuestionIndex);
            } else {
                this.answeredQuestions.delete(this.currentQuestionIndex);
            }

            // Принудительно вызываем обновление валидации
            this.$nextTick(() => {
                this.validateCurrentQuestion();
            });
        },

        updateMatchingAnswer(pairIndex, value) {
            const answer = this.getCurrentAnswer();
            let currentAnswers = Array.isArray(answer)
                ? [...answer]
                : new Array(this.currentQuestion.options.length).fill("");

            if (pairIndex < currentAnswers.length) {
                currentAnswers[pairIndex] = value;
            } else {
                currentAnswers[pairIndex] = value;
            }

            this.setCurrentAnswer(currentAnswers);
        },
        handleMatchingChange(pairIndex, event) {
            this.updateMatchingAnswer(pairIndex, event.target.value);
            this.clearValidationError();
        },

        calculateResult() {
            let totalScore = 0;
            let max_score = 0;
            const questionResults = [];

            // Проходим по всем вопросам в их оригинальном порядке
            this.selectedTest.questions.forEach(
                (originalQuestion, originalIndex) => {
                    max_score += originalQuestion.points;

                    // Находим отображаемый индекс для этого вопроса
                    const displayedIndex =
                        this.originalToDisplayedQuestion.get(originalIndex);
                    const question =
                        displayedIndex !== undefined
                            ? this.shuffledQuestions[displayedIndex]
                            : originalQuestion;

                    const userAnswer =
                        this.userAnswersByOriginalIndex.get(originalIndex);
                    let isCorrect = false;
                    let score = 0;

                    switch (question.type) {
                        case "single":
                            if (userAnswer === undefined || userAnswer === "") {
                                score = 0;
                                isCorrect = false;
                                break;
                            }

                            let correctOriginalIndex = -1;

                            // Находим оригинальный индекс правильного ответа
                            if (originalQuestion.options) {
                                correctOriginalIndex =
                                    originalQuestion.options.findIndex(
                                        (opt) => opt.correct === true
                                    );
                            }

                            // Получаем выбранный пользователем оригинальный индекс
                            const selectedOriginalIndex = Number(userAnswer);

                            // Сравниваем оригинальные индексы
                            isCorrect =
                                selectedOriginalIndex === correctOriginalIndex;
                            score = isCorrect ? question.points : 0;
                            break;

                        case "multiple":
                            if (
                                !Array.isArray(userAnswer) ||
                                userAnswer.length === 0
                            ) {
                                score = 0;
                                isCorrect = false;
                                break;
                            }

                            // Находим оригинальные индексы правильных ответов
                            const correctOriginalIndices = [];
                            if (originalQuestion.options) {
                                originalQuestion.options.forEach((opt, idx) => {
                                    if (opt.correct === true) {
                                        correctOriginalIndices.push(idx);
                                    }
                                });
                            }

                            // Получаем выбранные пользователем оригинальные индексы
                            const selectedOriginalIndices = userAnswer.map(
                                (idx) => Number(idx)
                            );

                            // Проверяем, что выбраны ВСЕ правильные ответы
                            const hasAllCorrect = correctOriginalIndices.every(
                                (idx) => selectedOriginalIndices.includes(idx)
                            );

                            // Проверяем, что НЕ выбрано ни одного неправильного ответа
                            const hasNoWrong = selectedOriginalIndices.every(
                                (idx) => correctOriginalIndices.includes(idx)
                            );

                            // Ответ правильный только если выбраны все правильные и ни одного неправильного
                            isCorrect = hasAllCorrect && hasNoWrong;
                            score = isCorrect ? originalQuestion.points : 0;
                            break;

                        case "sorting":
                            if (
                                !originalQuestion.options ||
                                originalQuestion.options.length === 0
                            ) {
                                score = 0;
                                isCorrect = false;
                                break;
                            }

                            if (
                                !Array.isArray(userAnswer) ||
                                userAnswer.length === 0
                            ) {
                                score = 0;
                                isCorrect = false;
                                break;
                            }

                            // Получаем правильный порядок из correctPosition
                            const correctOrder = [];
                            const positionMap = new Map();

                            // Собираем правильный порядок
                            originalQuestion.options.forEach((opt, index) => {
                                positionMap.set(
                                    index,
                                    opt.correctPosition || index
                                );
                            });

                            // Сортируем по correctPosition
                            const sortedByPosition = [
                                ...originalQuestion.options.keys(),
                            ].sort((a, b) => {
                                const posA = positionMap.get(a);
                                const posB = positionMap.get(b);
                                return posA - posB;
                            });

                            // Создаем маппинг: originalIndex -> его правильная позиция
                            const correctPositionForIndex = new Map();
                            sortedByPosition.forEach(
                                (originalIndex, correctPosition) => {
                                    correctPositionForIndex.set(
                                        originalIndex,
                                        correctPosition
                                    );
                                }
                            );

                            // Проверяем, полностью ли совпадает порядок
                            let isFullyCorrect = true;
                            userAnswer.forEach((userItemId, userPosition) => {
                                const shouldBeAtPosition =
                                    correctPositionForIndex.get(userItemId);
                                if (shouldBeAtPosition !== userPosition) {
                                    isFullyCorrect = false;
                                }
                            });

                            // Только полностью правильный ответ дает баллы
                            isCorrect = isFullyCorrect;
                            score = isCorrect ? originalQuestion.points : 0;
                            break;

                        case "true-false":
                            // Обработка разных форматов хранения правильного ответа
                            let correctAnswer;

                            if (typeof originalQuestion.options === "string") {
                                correctAnswer = originalQuestion.options; // "true" или "false"
                            } else if (
                                Array.isArray(originalQuestion.options)
                            ) {
                                correctAnswer =
                                    originalQuestion.options[0] || "false";
                            } else {
                                correctAnswer =
                                    originalQuestion.options?.toString() ||
                                    "false";
                            }

                            // Приводим userAnswer к строке для сравнения
                            const userAnswerStr = userAnswer?.toString();
                            isCorrect = userAnswerStr === correctAnswer;
                            score = isCorrect ? originalQuestion.points : 0;
                            break;

                        case "text":
                            const options = originalQuestion.options || [];
                            const userAnswerText =
                                userAnswer?.toString().toLowerCase().trim() ||
                                "";

                            isCorrect = options.some(
                                (correct_answer) =>
                                    userAnswerText ===
                                    correct_answer.toLowerCase().trim()
                            );
                            score = isCorrect ? originalQuestion.points : 0;
                            break;

                        case "matching":
                            const matchingData =
                                this.shuffledPairsMap.get(displayedIndex);

                            if (
                                !originalQuestion.options ||
                                originalQuestion.options.length === 0
                            ) {
                                score = 0;
                                isCorrect = false;
                                break;
                            }

                            if (
                                !Array.isArray(userAnswer) ||
                                userAnswer.length === 0
                            ) {
                                score = 0;
                                isCorrect = false;
                                break;
                            }

                            let correctPairs = 0;

                            if (matchingData) {
                                // Если пары были перемешаны
                                const shuffledPairs = matchingData.pairs;
                                // console.log(matchingData);

                                userAnswer.forEach(
                                    (userRightAnswer, displayedPairIndex) => {
                                        // Находим перемешанную пару по отображаемому индексу
                                        const shuffledPair =
                                            shuffledPairs[displayedPairIndex];
                                        if (
                                            shuffledPair &&
                                            shuffledPair.right ===
                                                userRightAnswer
                                        ) {
                                            correctPairs++;
                                        }
                                    }
                                );
                            } else {
                                // Если пары не перемешаны
                                userAnswer.forEach(
                                    (userRightAnswer, pairIndex) => {
                                        const originalPair =
                                            originalQuestion.options[pairIndex];
                                        if (
                                            originalPair &&
                                            originalPair.right ===
                                                userRightAnswer
                                        ) {
                                            correctPairs++;
                                        }
                                    }
                                );
                            }

                            score =
                                (correctPairs /
                                    originalQuestion.options.length) *
                                originalQuestion.points;
                            isCorrect =
                                correctPairs ===
                                originalQuestion.options.length;
                            break;

                        default:
                            score = 0;
                            isCorrect = false;
                            break;
                    }

                    totalScore += score;
                    questionResults.push({
                        question: originalQuestion.text,
                        userAnswer: this.formatUserAnswerForDisplay(
                            originalQuestion,
                            originalIndex,
                            userAnswer
                        ),
                        correct_answer: this.getCorrectAnswer(originalQuestion),
                        isCorrect,
                        score,
                        max_score: originalQuestion.points,
                        originalIndex,
                        wasShuffled:
                            this.selectedTest.settings.shuffleQuestions,
                        displayedIndex:
                            displayedIndex !== undefined
                                ? displayedIndex
                                : originalIndex,
                    });
                }
            );

            const percentage =
                max_score > 0 ? (totalScore / max_score) * 100 : 0;
            const grade = this.calculateGrade(percentage);

            const result = {
                test_id: this.selectedTest.id,
                test_title: this.selectedTest.title,
                timestamp: new Date().toISOString(),
                total_score: totalScore,
                max_score: max_score,
                percentage: Math.round(percentage),
                grade,
                time_spent: this.selectedTest.timeLimit * 60 - this.timeLeft,
                completed_with_timeout: false,
                answered_questions_count: this.selectedTest.questions.length,
                total_questions_count: this.selectedTest.questions.length,
            };

            result.user_name = this.userName;

            result.question_results = questionResults;

            return result;
        },
        formatUserAnswerForDisplay(
            originalQuestion,
            originalIndex,
            userAnswer
        ) {
            if (
                userAnswer === undefined ||
                userAnswer === null ||
                userAnswer === ""
            ) {
                return "";
            }

            const displayedIndex =
                this.originalToDisplayedQuestion.get(originalIndex);

            switch (originalQuestion.type) {
                case "single":
                    if (userAnswer === "") return "";

                    const displayedAnswerIndex = Number(userAnswer);

                    // Если варианты были перемешаны, нужно найти текст ответа
                    if (this.shuffledOptionsMap.has(displayedIndex)) {
                        const shuffledOptions =
                            this.shuffledOptionsMap.get(displayedIndex);
                        const selectedOption = shuffledOptions.find(
                            (opt) => opt.originalIndex === displayedAnswerIndex
                        );
                        return selectedOption
                            ? selectedOption.text
                            : `Вариант ${displayedAnswerIndex + 1}`;
                    } else {
                        // Если не перемешаны
                        return (
                            originalQuestion.options[displayedAnswerIndex]
                                ?.text || `Вариант ${displayedAnswerIndex + 1}`
                        );
                    }

                case "multiple":
                    if (!Array.isArray(userAnswer) || userAnswer.length === 0)
                        return "";

                    const answerTexts = userAnswer.map((answerIndex) => {
                        const index = Number(answerIndex);

                        if (this.shuffledOptionsMap.has(displayedIndex)) {
                            const shuffledOptions =
                                this.shuffledOptionsMap.get(displayedIndex);
                            const selectedOption = shuffledOptions.find(
                                (opt) => opt.originalIndex === index
                            );
                            return selectedOption
                                ? selectedOption.text
                                : `Вариант ${index + 1}`;
                        } else {
                            return (
                                originalQuestion.options[index]?.text ||
                                `Вариант ${index + 1}`
                            );
                        }
                    });

                    return answerTexts.join(", ");

                case "sorting":
                    if (!Array.isArray(userAnswer) || userAnswer.length === 0) {
                        return "";
                    }

                    // Получаем текст элементов
                    const sortedItemsText = userAnswer.map((itemId, index) => {
                        const item = originalQuestion.options?.[itemId];
                        const itemText = item?.text || `Элемент ${itemId + 1}`;
                        return `${index + 1}. ${itemText}`;
                    });

                    // Единый формат: перечисление позиций и текстов
                    return sortedItemsText.join("; ");

                case "true-false":
                    return userAnswer == "true" ? "Да" : "Нет";

                case "text":
                    return userAnswer.toString();

                case "matching":
                    if (!Array.isArray(userAnswer) || userAnswer.length === 0)
                        return "";

                    const matchingData =
                        this.shuffledPairsMap.get(displayedIndex);
                    const pairs =
                        matchingData?.options || originalQuestion.options || [];

                    const formattedPairs = userAnswer.map(
                        (rightAnswer, idx) => {
                            const leftText =
                                pairs[idx]?.left || `Элемент ${idx + 1}`;
                            const rightText = rightAnswer || "Не выбрано";
                            return `${leftText} → ${rightText}`;
                        }
                    );

                    // Единый формат: перечисление сопоставлений через точку с запятой
                    return formattedPairs.join("; ");

                default:
                    return userAnswer.toString();
            }
        },
        getCorrectAnswer(originalQuestion) {
            if (!originalQuestion) return "";

            switch (originalQuestion.type) {
                case "single":
                    const correctOption = originalQuestion.options?.find(
                        (opt) => opt.correct === true
                    );
                    return correctOption ? correctOption.text : "";
                case "multiple":
                    return (
                        originalQuestion.options
                            ?.filter((opt) => opt.correct === true)
                            ?.map((opt) => opt.text) || []
                    );
                case "true-false":
                    if (typeof originalQuestion.options === "string") {
                        return originalQuestion.options == "true"
                            ? "Да"
                            : "Нет";
                    }
                    if (Array.isArray(originalQuestion.options)) {
                        return originalQuestion.options[0] == "true"
                            ? "Да"
                            : "Нет";
                    }
                    return originalQuestion.options == "true" ? "Да" : "Нет";
                case "text":
                    return originalQuestion.options || [];
                case "matching":
                    if (!originalQuestion.options) return [];

                    // Единый формат для сопоставления: список пар через точку с запятой
                    const matchingPairs = originalQuestion.options.map(
                        (pair) => {
                            return `${pair.left} → ${pair.right}`;
                        }
                    );

                    return matchingPairs.join("; ");
                case "sorting":
                    if (!originalQuestion.options) return [];

                    // Получаем правильный порядок из correctPosition
                    const positionMap = new Map();
                    originalQuestion.options.forEach((opt, index) => {
                        positionMap.set(index, opt.correctPosition || index);
                    });

                    // Сортируем по correctPosition
                    const sortedByPosition = [
                        ...originalQuestion.options.keys(),
                    ].sort((a, b) => {
                        const posA = positionMap.get(a);
                        const posB = positionMap.get(b);
                        return posA - posB;
                    });

                    // Формируем текст правильного порядка
                    const correctOrderText = sortedByPosition.map(
                        (originalIndex, position) => {
                            const item =
                                originalQuestion.options[originalIndex];
                            const itemText =
                                item?.text || `Элемент ${originalIndex + 1}`;
                            return `${position + 1}. ${itemText}`;
                        }
                    );

                    return correctOrderText.join("; ");
                default:
                    return "";
            }
        },

        debugShuffledOptions(displayedIndex) {
            console.log(`Вопрос ${displayedIndex}:`);
            if (this.shuffledOptionsMap.has(displayedIndex)) {
                const shuffled = this.shuffledOptionsMap.get(displayedIndex);
                console.log(
                    "Перемешанные варианты:",
                    shuffled.map((opt) => ({
                        text: opt.text,
                        originalIndex: opt.originalIndex,
                        correct:
                            this.selectedTest.questions[
                                this.displayedQuestionToOriginal.get(
                                    displayedIndex
                                )
                            ]?.options?.[opt.originalIndex]?.correct,
                    }))
                );
            } else {
                console.log("Варианты не перемешаны");
            }
        },

        calculateGrade(percentage) {
            if (!this.selectedTest.grading) return "Не оценено";

            const gradeSystem = this.selectedTest.grading;
            const grade = gradeSystem.find(
                (g) => percentage >= g.minScore && percentage <= g.max_score // Используйте то название, которое есть в ваших данных
            );
            return grade ? grade.grade : "Не оценено";
        },
        handleTextInput(event) {
            this.setCurrentAnswer(event.target.value);
            this.clearValidationError();
        },

        getcorrect_answer(question) {
            if (!question) return "";

            switch (question.type) {
                case "single":
                    const correctOption = question.options?.find(
                        (opt) => opt.correct
                    );
                    return correctOption ? correctOption.text : "";
                case "multiple":
                    return (
                        question.options
                            ?.filter((opt) => opt.correct)
                            ?.map((opt) => opt.text) || []
                    );
                case "true-false":
                    return question.options === "true" ? "Да" : "Нет";
                case "text":
                    return question.options || [];
                case "matching":
                    return (
                        question.options?.map((pair) => ({
                            left: pair.left,
                            right: pair.right,
                        })) || []
                    );
                default:
                    return "";
            }
        },

        calculateGrade(percentage) {
            if (!this.selectedTest.grading) return "Не оценено";

            const gradeSystem = this.selectedTest.grading;
            const grade = gradeSystem.find(
                (g) => percentage >= g.minScore && percentage <= g.max_score
            );
            return grade ? grade.grade : "Не оценено";
        },

        isOptionSelected(optionIndex) {
            const answer = this.userAnswers[this.currentQuestionIndex];
            if (!Array.isArray(answer)) return false;
            return answer.includes(optionIndex);
        },

        toggleMultipleChoiceOption(optionIndex, event) {
            // Создаем копию массива или инициализируем новый
            let currentAnswers = this.userAnswers[this.currentQuestionIndex];

            // Если массив не инициализирован или не является массивом
            if (!Array.isArray(currentAnswers)) {
                currentAnswers = [];
            }

            // Создаем новый массив для реактивности
            let newAnswers = [...currentAnswers];

            if (event.target.checked) {
                // Добавляем индекс, если его еще нет
                if (!newAnswers.includes(optionIndex)) {
                    newAnswers.push(optionIndex);
                }
            } else {
                // Удаляем индекс
                newAnswers = newAnswers.filter((idx) => idx !== optionIndex);
            }

            // Обновляем реактивно (просто присваиваем новый массив)
            this.userAnswers[this.currentQuestionIndex] = newAnswers;

            // Обновляем answeredQuestions
            if (newAnswers.length > 0) {
                this.answeredQuestions.add(this.currentQuestionIndex);
            } else {
                this.answeredQuestions.delete(this.currentQuestionIndex);
            }
        },
    },
    beforeUnmount() {
        if (this.timer) {
            clearInterval(this.timer);
        }
    },
};
</script>
<style scoped>
.test-card {
    cursor: pointer;
}

.test-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.timer-warning {
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
    100% {
        opacity: 1;
    }
}

.user-name-section {
    max-width: 500px;
    margin: 0 auto;
}

.form-check {
    transition: all 0.3s ease;
}

.form-check:hover {
    background-color: #f8f9fa;
    border-color: #0d6efd;
}

/* Стили для валидации */
.required-question {
    border-left: 4px solid #dc3545 !important;
}

.answered-question {
    border-left: 4px solid #28a745 !important;
}

/* Стили для маркеров вопросов */
.progress-container {
    position: relative;
}

.progress-markers {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 20px;
    pointer-events: none;
}

.question-marker {
    position: absolute;
    top: 0;
    width: 8px;
    height: 20px;
    cursor: pointer;
    z-index: 10;
    pointer-events: auto;
    transition: background-color 0.3s ease;
}

.question-marker.answered {
    background-color: #28a745;
}

.question-marker.current {
    background-color: #ffc107;
    width: 10px;
    height: 24px;
    top: -2px;
}

.question-marker.unanswered {
    background-color: #dc3545;
}

.question-marker:hover {
    transform: scale(1.2);
}

/* Стили для навигации кнопок */
.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-primary {
    min-width: 120px;
}

.btn-success {
    min-width: 150px;
}
.timer.expired {
    background-color: #dc3545 !important;
    color: white !important;
    animation: pulse 0.5s infinite;
}
.sorting-area {
    min-height: 200px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
    border: 2px dashed #dee2e6;
}

.sorting-item {
    background-color: white;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: move;
}

.sorting-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.sorting-item.dragging {
    opacity: 0.5;
    border: 2px dashed #0d6efd;
}

.drag-handle {
    cursor: grab;
}

.drag-handle:active {
    cursor: grabbing;
}

.position-badge {
    min-width: 40px;
    text-align: center;
}
</style>
