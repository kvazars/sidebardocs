<template>
    <div class="test-runner">
        <!-- Выбор теста -->
        <div v-if="!selectedTest" class="test-selection">
            <h2 class="mb-4">
                <i class="bi bi-play-circle"></i> Выберите тест для прохождения
            </h2>

            <div v-if="tests.length === 0" class="alert alert-info text-center">
                <i class="bi bi-info-circle"></i> Нет доступных тестов. Создайте
                тест сначала.
            </div>

            <div v-else class="row">
                <div
                    v-for="test in tests"
                    :key="test.id"
                    class="col-md-6 col-lg-4 mb-3"
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
                    <div class="progress mb-2" style="height: 20px">
                        <div
                            class="progress-bar progress-bar-striped progress-bar-animated"
                            :style="{ width: progress + '%' }"
                        ></div>
                    </div>
                    <small class="text-muted">
                        Вопрос {{ currentQuestionIndex + 1 }} из
                        {{ selectedTest.questions.length }}
                    </small>
                </div>
                <div class="col-md-4">
                    <div class="timer alert" :class="timerClass">
                        <i class="bi bi-clock"></i> {{ formatTime(timeLeft) }}
                    </div>
                </div>
            </div>

            <!-- Вопрос -->
            <div class="card mb-4" @contextmenu="preventCopy">
                <div
                    class="card-header bg-light d-flex justify-content-between align-items-center"
                >
                    <h5 class="mb-0">
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
                    @click="nextQuestion"
                    class="btn btn-primary"
                >
                    Следующий <i class="bi bi-arrow-right"></i>
                </button>

                <button v-else @click="finishTest" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Завершить тест
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { saveResult } from "../utils/storage.js";

export default {
    name: "TestRunner",
    props: {
        tests: {
            type: Array,
            required: true,
        },
    },
    emits: ["test-completed"],
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
            // Для перемешивания - храним отдельно для каждого вопроса
            shuffledQuestions: [],
            shuffledOptionsMap: new Map(), // questionIndex -> shuffledOptions
            shuffledPairsMap: new Map(), // questionIndex -> { pairs, rightOptions }
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
                    this.finishTest();
                }
            }, 1000);
        },

        formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins}:${secs.toString().padStart(2, "0")}`;
        },

        nextQuestion() {
            if (
                this.currentQuestionIndex <
                this.selectedTest.questions.length - 1
            ) {
                this.currentQuestionIndex++;
                this.initializeQuestion();
            }
        },

        previousQuestion() {
            if (this.currentQuestionIndex > 0) {
                this.currentQuestionIndex--;
                this.initializeQuestion();
            }
        },

        finishTest() {
            clearInterval(this.timer);
            const result = this.calculateResult();
            saveResult(result);
            this.selectedTest = null;
            this.userName = "";
            this.tempUserName = "";
            this.shuffledOptionsMap.clear();
            this.shuffledPairsMap.clear();
            this.$emit("test-completed", result);
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
</style>
