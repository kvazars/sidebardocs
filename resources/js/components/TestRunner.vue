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
                                    v-if="test.settings.requireUserName"
                                    class="text-info"
                                >
                                    <i class="bi bi-person"></i> Требуется имя
                                </small>
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
                                <small class="text-danger ms-2">
                                    <i class="bi bi-exclamation-circle"></i> Все
                                    вопросы обязательны
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ввод имени (если требуется) -->
        <div
            v-else-if="
                selectedTest &&
                selectedTest.settings.requireUserName &&
                !userName
            "
            class="user-name-section"
        >
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

            <!-- Защита от копирования -->
            <div v-if="copyAttempted" class="copy-protection-warning">
                <div
                    class="alert alert-warning alert-dismissible fade show"
                    role="alert"
                >
                    <i class="bi bi-exclamation-triangle"></i> Копирование
                    текста запрещено!
                    <button
                        type="button"
                        class="btn-close"
                        @click="copyAttempted = false"
                    ></button>
                </div>
            </div>

            <!-- Таймер и прогресс -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="progress-container mb-3">
                        <div class="progress mb-2" style="height: 20px">
                            <div
                                class="progress-bar progress-bar-striped progress-bar-animated"
                                :style="{ width: progress + '%' }"
                            ></div>
                        </div>
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
                    <div class="timer alert" :class="timerClass">
                        <i class="bi bi-clock"></i> {{ formatTime(timeLeft) }}
                    </div>
                </div>
            </div>

            <!-- Вопрос -->
            <div
                class="card mb-4"
                @contextmenu="preventCopy"
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

                    <!-- Одиночный выбор -->
                    <div
                        v-if="currentQuestion.type === 'single'"
                        class="answers"
                    >
                        <div
                            v-for="(option, index) in currentShuffledOptions"
                            :key="index"
                            class="form-check mb-3 p-3 border rounded"
                        >
                            <input
                                :id="'option' + index"
                                type="radio"
                                :name="'question' + currentQuestionIndex"
                                :value="option.originalIndex"
                                v-model="userAnswers[currentQuestionIndex]"
                                class="form-check-input"
                            />
                            <label
                                :for="'option' + index"
                                class="form-check-label w-100"
                            >
                                <div class="d-flex align-items-center">
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

                    <!-- Множественный выбор -->
                    <div
                        v-else-if="currentQuestion.type === 'multiple'"
                        class="answers"
                    >
                        <div
                            v-for="(option, index) in currentShuffledOptions"
                            :key="index"
                            class="form-check mb-3 p-3 border rounded"
                        >
                            <input
                                :id="'option' + index"
                                type="checkbox"
                                :value="option.originalIndex"
                                v-model="userAnswers[currentQuestionIndex]"
                                class="form-check-input"
                            />
                            <label
                                :for="'option' + index"
                                class="form-check-label w-100"
                            >
                                <div class="d-flex align-items-center">
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
                                    class="form-check p-3 border rounded text-center h-100"
                                >
                                    <input
                                        :id="'true-' + currentQuestionIndex"
                                        type="radio"
                                        :name="
                                            'question' + currentQuestionIndex
                                        "
                                        value="true"
                                        v-model="
                                            userAnswers[currentQuestionIndex]
                                        "
                                        class="form-check-input"
                                    />
                                    <label
                                        :for="'true-' + currentQuestionIndex"
                                        class="form-check-label fs-5 fw-bold text-success w-100"
                                    >
                                        <i class="bi bi-check-circle"></i>
                                        Да
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div
                                    class="form-check p-3 border rounded text-center h-100"
                                >
                                    <input
                                        :id="'false-' + currentQuestionIndex"
                                        type="radio"
                                        :name="
                                            'question' + currentQuestionIndex
                                        "
                                        value="false"
                                        v-model="
                                            userAnswers[currentQuestionIndex]
                                        "
                                        class="form-check-input"
                                    />
                                    <label
                                        :for="'false-' + currentQuestionIndex"
                                        class="form-check-label fs-5 fw-bold text-danger w-100"
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
                        <textarea
                            v-model="userAnswers[currentQuestionIndex]"
                            class="form-control"
                            placeholder="Введите ваш ответ..."
                            rows="3"
                            @input="clearValidationError"
                        ></textarea>
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
                                    v-model="
                                        userAnswers[currentQuestionIndex][index]
                                    "
                                    class="form-select"
                                    @change="clearValidationError"
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
                    class="btn btn-success"
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
            copyAttempted: false,
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
            return this.shuffledOptionsMap.get(this.currentQuestionIndex) || [];
        },

        currentShuffledPairs() {
            const data = this.shuffledPairsMap.get(this.currentQuestionIndex);
            return data ? data.pairs : [];
        },

        currentShuffledRightOptions() {
            const data = this.shuffledPairsMap.get(this.currentQuestionIndex);
            return data ? data.rightOptions : [];
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
    },
    methods: {
        selectTest(test) {
            this.selectedTest = JSON.parse(JSON.stringify(test));

            // Сбрасываем имя пользователя
            this.userName = "";
            this.tempUserName = "";

            // Если имя не требуется, сразу инициализируем тест
            if (!this.selectedTest.settings.requireUserName) {
                this.initializeTest();
            }
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
                this.shuffledQuestions = [...this.selectedTest.questions];
            }

            // Инициализируем ответы пользователя
            this.userAnswers = this.selectedTest.questions.map((question) => {
                switch (question.type) {
                    case "multiple":
                        return []; // Для множественного выбора - массив
                    case "matching":
                        return []; // Для сопоставления - массив
                    default:
                        return ""; // Для остальных типов - строка
                }
            });

            this.timeLeft = this.selectedTest.timeLimit * 60;
            this.startTimer();
            this.initializeQuestion();
        },

        shuffleQuestions() {
            const questions = [...this.selectedTest.questions];
            this.shuffledQuestions = questions
                .map((question, index) => ({
                    ...question,
                    originalIndex: index,
                }))
                .sort(() => Math.random() - 0.5);
        },

        initializeQuestion() {
            if (!this.currentQuestion) return;

            // Перемешиваем варианты ответов если нужно
            if (this.shouldShuffleAnswers()) {
                this.shuffleQuestionOptions();
            }

            // Для вопросов на сопоставление
            if (this.currentQuestion.type === "matching") {
                this.shuffleMatchingOptions();
            }

            // Сбрасываем ошибку валидации при инициализации вопроса
            this.validationError = "";
        },

        shouldShuffleAnswers() {
            if (!this.selectedTest.settings.shuffleAnswers) return false;

            const questionType = this.currentQuestion.type;

            switch (questionType) {
                case "single":
                    return (
                        this.selectedTest.settings.shuffleSingleChoice !== false
                    );
                case "multiple":
                    return (
                        this.selectedTest.settings.shuffleMultipleChoice !==
                        false
                    );
                case "matching":
                    return this.selectedTest.settings.shuffleMatching !== false;
                default:
                    return false;
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
            const pairs = [...this.currentQuestion.pairs];
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

            if (
                !this.userAnswers[this.currentQuestionIndex] ||
                this.userAnswers[this.currentQuestionIndex].length === 0
            ) {
                this.userAnswers[this.currentQuestionIndex] = new Array(
                    pairs.length
                ).fill("");
            }
        },

        startTimer() {
            this.timer = setInterval(() => {
                this.timeLeft--;
                if (this.timeLeft <= 0) {
                    // При завершении времени автоматически завершаем тест с проверкой
                    this.validateAndFinishTest();
                }
            }, 1000);
        },

        formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins}:${secs.toString().padStart(2, "0")}`;
        },

        // ВАЛИДАЦИЯ ВОПРОСОВ
        validateCurrentQuestion() {
            this.validationError = "";
            const answer = this.userAnswers[this.currentQuestionIndex];
            const question = this.currentQuestion;

            // Проверяем наличие ответа
            let isValid = false;

            switch (question.type) {
                case "single":
                    // Для одиночного выбора answer должно быть числом или строкой, но не пустым
                    isValid =
                        answer !== "" &&
                        answer !== null &&
                        answer !== undefined;
                    if (!isValid) {
                        this.validationError =
                            "Пожалуйста, выберите один вариант ответа";
                    }
                    break;

                case "multiple":
                    // Для множественного выбора массив не должен быть пустым
                    isValid = Array.isArray(answer) && answer.length > 0;
                    if (!isValid) {
                        this.validationError =
                            "Пожалуйста, выберите хотя бы один вариант ответа";
                    }
                    break;

                case "true-false":
                    // Для да/нет должно быть выбрано значение
                    isValid = answer === "true" || answer === "false";
                    if (!isValid) {
                        this.validationError =
                            "Пожалуйста, выберите Да или Нет";
                    }
                    break;

                case "text":
                    // Для текстового ответа не должно быть пустой строкой
                    isValid = answer && answer.trim() !== "";
                    if (!isValid) {
                        this.validationError = "Пожалуйста, введите ваш ответ";
                    }
                    break;

                case "matching":
                    // Для сопоставления все пары должны быть заполнены
                    if (!Array.isArray(answer)) {
                        isValid = false;
                    } else {
                        const hasEmptySelections = answer.some(
                            (item) => !item || item.trim() === ""
                        );
                        isValid = !hasEmptySelections;
                    }
                    if (!isValid) {
                        this.validationError =
                            "Пожалуйста, выберите соответствие для каждого элемента";
                    }
                    break;

                default:
                    isValid = false;
                    this.validationError = "Неизвестный тип вопроса";
                    break;
            }

            // Если валидация пройдена, добавляем вопрос в список отвеченных
            if (isValid) {
                this.answeredQuestions.add(this.currentQuestionIndex);
            } else {
                this.answeredQuestions.delete(this.currentQuestionIndex);
            }

            return isValid;
        },

        validateAllQuestions() {
            // Проверяем все вопросы
            for (let i = 0; i < this.selectedTest.questions.length; i++) {
                const answer = this.userAnswers[i];
                const question = this.selectedTest.settings.shuffleQuestions
                    ? this.shuffledQuestions[i]
                    : this.selectedTest.questions[i];

                let isValid = false;

                // Проверяем ответ в зависимости от типа вопроса
                switch (question.type) {
                    case "single":
                        isValid =
                            answer !== "" &&
                            answer !== null &&
                            answer !== undefined;
                        break;
                    case "multiple":
                        isValid = Array.isArray(answer) && answer.length > 0;
                        break;
                    case "true-false":
                        isValid = answer === "true" || answer === "false";
                        break;
                    case "text":
                        isValid = answer && answer.trim() !== "";
                        break;
                    case "matching":
                        if (Array.isArray(answer)) {
                            const hasEmptySelections = answer.some(
                                (item) => !item || item.trim() === ""
                            );
                            isValid = !hasEmptySelections;
                        }
                        break;
                }

                if (!isValid) {
                    return {
                        isValid: false,
                        questionIndex: i,
                    };
                }
            }

            return { isValid: true };
        },

        validateAndNextQuestion() {
            if (this.validateCurrentQuestion()) {
                this.nextQuestion();
            } else {
                // Прокрутка к ошибке
                this.$nextTick(() => {
                    const errorElement =
                        document.querySelector(".alert-danger");
                    if (errorElement) {
                        errorElement.scrollIntoView({ behavior: "smooth" });
                    }
                });
            }
        },

        validateAndFinishTest() {
            // Проверяем текущий вопрос
            if (!this.validateCurrentQuestion()) {
                // Прокрутка к ошибке
                this.$nextTick(() => {
                    const errorElement =
                        document.querySelector(".alert-danger");
                    if (errorElement) {
                        errorElement.scrollIntoView({ behavior: "smooth" });
                    }
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
                    if (errorElement) {
                        errorElement.scrollIntoView({ behavior: "smooth" });
                    }
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
                    if (questionElement) {
                        questionElement.scrollIntoView({ behavior: "smooth" });
                    }
                });
            }
        },

        previousQuestion() {
            if (this.currentQuestionIndex > 0) {
                this.currentQuestionIndex--;
                this.validationError = "";
                this.initializeQuestion();

                // Прокрутка к началу вопроса
                this.$nextTick(() => {
                    const questionElement =
                        document.querySelector(".card.mb-4");
                    if (questionElement) {
                        questionElement.scrollIntoView({ behavior: "smooth" });
                    }
                });
            }
        },

        goToQuestion(index) {
            if (index >= 0 && index < this.selectedTest.questions.length) {
                this.currentQuestionIndex = index;
                this.validationError = "";
                this.initializeQuestion();

                // Прокрутка к началу вопроса
                this.$nextTick(() => {
                    const questionElement =
                        document.querySelector(".card.mb-4");
                    if (questionElement) {
                        questionElement.scrollIntoView({ behavior: "smooth" });
                    }
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
                this.showToast(response.message, "success");
            });
        },

        calculateResult() {
            let totalScore = 0;
            let max_score = 0;
            const questionResults = [];

            const questions = this.selectedTest.settings.shuffleQuestions
                ? this.shuffledQuestions
                : this.selectedTest.questions;

            questions.forEach((question, displayIndex) => {
                const originalIndex =
                    question.originalIndex !== undefined
                        ? question.originalIndex
                        : displayIndex;

                max_score += question.points;
                const userAnswer = this.userAnswers[displayIndex];
                let isCorrect = false;
                let score = 0;

                switch (question.type) {
                    case "single":
                        // Для одиночного выбора userAnswer - это originalIndex выбранного варианта
                        const selectedOption =
                            question.options.find(
                                (opt) => opt.originalIndex === userAnswer
                            ) || question.options[userAnswer];
                        isCorrect = selectedOption && selectedOption.correct;
                        score = isCorrect ? question.points : 0;
                        break;

                    case "multiple":
                        // Для множественного выбора userAnswer - массив originalIndex
                        const selectedOptions = userAnswer
                            .map(
                                (index) =>
                                    question.options.find(
                                        (opt) => opt.originalIndex === index
                                    ) || question.options[index]
                            )
                            .filter(Boolean);

                        const correctOptions = question.options.filter(
                            (opt) => opt.correct
                        );
                        const userCorrectCount = selectedOptions.filter(
                            (opt) => opt.correct
                        ).length;
                        const wrongCount = selectedOptions.filter(
                            (opt) => !opt.correct
                        ).length;

                        if (correctOptions.length > 0) {
                            score =
                                Math.max(
                                    0,
                                    (userCorrectCount - wrongCount) /
                                        correctOptions.length
                                ) * question.points;
                        } else {
                            score = 0;
                        }
                        isCorrect = score > 0;
                        break;

                    case "true-false":
                        isCorrect =
                            userAnswer === question.correct_answer?.toString();
                        score = isCorrect ? question.points : 0;
                        break;

                    case "text":
                        // Исправление: проверяем наличие correct_answers
                        const correct_answers = question.correct_answers || [];
                        const userAnswerText =
                            userAnswer?.toString().toLowerCase().trim() || "";

                        isCorrect = correct_answers.some(
                            (correct_answer) =>
                                userAnswerText ===
                                correct_answer.toLowerCase().trim()
                        );
                        score = isCorrect ? question.points : 0;
                        break;

                    case "matching":
                        const matchingData =
                            this.shuffledPairsMap.get(displayIndex);
                        if (
                            matchingData &&
                            question.pairs &&
                            question.pairs.length > 0
                        ) {
                            const correctPairs = userAnswer.filter(
                                (answer, idx) => {
                                    const originalPair =
                                        matchingData.pairs[idx];
                                    return answer === originalPair?.right;
                                }
                            ).length;
                            score =
                                (correctPairs / question.pairs.length) *
                                question.points;
                            isCorrect = correctPairs === question.pairs.length;
                        } else {
                            score = 0;
                            isCorrect = false;
                        }
                        break;

                    default:
                        score = 0;
                        isCorrect = false;
                        break;
                }

                totalScore += score;
                questionResults.push({
                    question: question.text,
                    questionImage: question.image,
                    userAnswer,
                    correct_answer: this.getcorrect_answer(question),
                    isCorrect,
                    score,
                    max_score: question.points,
                    originalIndex,
                    wasShuffled: this.selectedTest.settings.shuffleQuestions,
                });
            });

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
                settings: {
                    show_user_name_in_results:
                        this.selectedTest.settings.showUserNameInResults,
                },
            };

            if (
                this.selectedTest.settings.requireUserName &&
                this.selectedTest.settings.showUserNameInResults
            ) {
                result.user_name = this.userName;
            }

            result.question_results = questionResults;

            return result;
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
                    return question.correct_answer === "true" ? "Да" : "Нет";
                case "text":
                    return question.correct_answers || [];
                case "matching":
                    return (
                        question.pairs?.map((pair) => ({
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

        preventCopy(event) {
            event.preventDefault();
            this.copyAttempted = true;
            setTimeout(() => {
                this.copyAttempted = false;
            }, 2000);
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

.copy-protection-warning {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10000;
    min-width: 300px;
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
</style>
