<template>
    <div class="test-results">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="bi bi-graph-up"></i> Результаты тестов
            </h2>
            <div class="d-flex gap-2">
                <!-- Переключение режима просмотра -->
                <div class="btn-group" role="group">
                    <button
                        @click="viewMode = 'cards'"
                        class="btn btn-outline-primary"
                        :class="{ active: viewMode === 'cards' }"
                    >
                        <i class="bi bi-grid"></i> Карточки
                    </button>
                    <button
                        @click="viewMode = 'table'"
                        class="btn btn-outline-primary"
                        :class="{ active: viewMode === 'table' }"
                    >
                        <i class="bi bi-table"></i> Таблица
                    </button>
                </div>
            </div>
        </div>

        <div v-if="results.length === 0" class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i> Нет результатов тестов.
        </div>

        <div v-else>
            <!-- Фильтры -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <label class="form-label"
                                >Поиск по названию теста/пользователю:</label
                            >
                            <input
                                v-model="searchQuery"
                                type="text"
                                class="form-control"
                                placeholder="Введите название теста..."
                            />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Сортировка:</label>
                            <select v-model="sortBy" class="form-select">
                                <option value="date">
                                    По дате (новые сначала)
                                </option>
                                <option value="date_asc">
                                    По дате (старые сначала)
                                </option>
                                <option value="score">
                                    По баллам (высокие сначала)
                                </option>
                                <option value="score_asc">
                                    По баллам (низкие сначала)
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Фильтр по оценке:</label>
                            <select v-model="gradeFilter" class="form-select">
                                <option value="">Все оценки</option>
                                <option value="Отлично">Отлично</option>
                                <option value="Хорошо">Хорошо</option>
                                <option value="Удовлетворительно">
                                    Удовлетворительно
                                </option>
                                <option value="Неудовлетворительно">
                                    Неудовлетворительно
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Режим карточек -->
            <div v-if="viewMode === 'cards'" class="row">
                <div
                    v-for="result in filteredResults"
                    :key="getResultKey(result)"
                    class="col-lg-3 mb-4"
                >
                    <div
                        class="card h-100"
                        :class="getResultClass(result.percentage)"
                    >
                        <div
                            class="card-header d-flex justify-content-between align-items-center"
                        >
                            <div>
                                <h5 class="mb-0">{{ getTestTitle(result) }}</h5>
                                <small class="text-muted">{{
                                    formatDate(result.created_at)
                                }}</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span
                                    class="badge"
                                    :class="getBadgeClass(result.percentage)"
                                >
                                    {{ result.percentage }}%
                                </span>
                                <button
                                    @click="deleteResult(result)"
                                    class="btn btn-outline-danger btn-sm"
                                    title="Удалить результат"
                                >
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>

                        <div class="card-body">
                            <!-- Информация о пользователе -->
                            <div
                                v-if="result.user_name"
                                class="alert alert-info py-2 mb-3"
                            >
                                <i class="bi bi-person"></i>
                                <strong
                                    >{{ result.user_name }} ({{
                                        result.user.name
                                    }})</strong
                                >
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">Баллы:</small>
                                    <div class="fw-bold">
                                        {{ result.total_score || 0 }} /
                                        {{ result.max_score || 0 }}
                                    </div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Оценка:</small>
                                    <div class="fw-bold">
                                        {{ result.grade || "Не оценено" }}
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">Время:</small>
                                    <div>
                                        {{ formatTime(result.time_spent) }}
                                    </div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Дата:</small>
                                    <div>
                                        {{ formatDate(result.created_at) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Кнопка детального просмотра (если разрешено) -->
                            <button
                                @click="toggleDetails(result)"
                                class="btn btn-outline-primary btn-sm w-100 mb-3"
                            >
                                <i
                                    class="bi"
                                    :class="
                                        isResultExpanded(result)
                                            ? 'bi-chevron-up'
                                            : 'bi-chevron-down'
                                    "
                                ></i>
                                {{
                                    isResultExpanded(result)
                                        ? "Скрыть детали"
                                        : "Показать детали"
                                }}
                            </button>

                            <!-- Детальные результаты -->
                            <div
                                v-if="isResultExpanded(result)"
                                class="question-details"
                            >
                                <div
                                    v-for="(
                                        qResult, qIndex
                                    ) in getQuestionResults(result)"
                                    :key="qIndex"
                                    class="question-result mb-3 p-3 rounded"
                                    :class="
                                        qResult.isCorrect
                                            ? 'bg-success bg-opacity-10'
                                            : 'bg-danger bg-opacity-10'
                                    "
                                >
                                    <div
                                        v-if="
                                            qResult.questionType === 'sorting'
                                        "
                                        class="sorting-details mt-2"
                                    >
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong class="d-block mb-1"
                                                    >Ваш порядок:</strong
                                                >
                                                <ol class="mb-0">
                                                    <li
                                                        v-for="(
                                                            item, idx
                                                        ) in qResult.userAnswer"
                                                        :key="idx"
                                                    >
                                                        {{ item }}
                                                    </li>
                                                </ol>
                                            </div>
                                            <div class="col-md-6">
                                                <strong class="d-block mb-1"
                                                    >Правильный порядок:</strong
                                                >
                                                <ol class="mb-0">
                                                    <li
                                                        v-for="(
                                                            item, idx
                                                        ) in qResult.correct_answer"
                                                        :key="idx"
                                                    >
                                                        {{ item }}
                                                    </li>
                                                </ol>
                                            </div>
                                        </div>
                                    </div>
                                    <h6 class="mb-1">
                                        Вопрос {{ qIndex + 1 }}:
                                        {{ qResult.question || "Без текста" }}
                                    </h6>

                                    <div
                                        v-if="qResult.questionImage"
                                        class="mt-2 text-center"
                                    >
                                        <img
                                            :src="qResult.questionImage"
                                            class="img-thumbnail"
                                            style="max-height: 150px"
                                            @error="handleImageError"
                                        />
                                    </div>

                                    <div class="mb-2">
                                        <strong class="d-block mb-1"
                                            >Ваш ответ:</strong
                                        >
                                        <div
                                            :class="
                                                qResult.isCorrect
                                                    ? 'text-success'
                                                    : 'text-danger'
                                            "
                                        >
                                            {{
                                                formatUserAnswer(
                                                    qResult.userAnswer
                                                )
                                            }}
                                        </div>
                                    </div>

                                    <div v-if="!qResult.isCorrect" class="mb-2">
                                        <strong class="d-block mb-1"
                                            >Правильный ответ:</strong
                                        >
                                        <div class="text-success">
                                            {{
                                                formatCorrectAnswer(
                                                    qResult.correct_answer
                                                )
                                            }}
                                        </div>
                                    </div>

                                    <div
                                        class="d-flex justify-content-between align-items-center"
                                    >
                                        <span
                                            class="badge"
                                            :class="
                                                qResult.isCorrect
                                                    ? 'bg-success'
                                                    : 'bg-danger'
                                            "
                                        >
                                            {{
                                                (qResult.score || 0).toFixed(1)
                                            }}
                                            /
                                            {{ qResult.max_score || 0 }} баллов
                                        </span>
                                        <i
                                            class="bi"
                                            :class="
                                                qResult.isCorrect
                                                    ? 'bi-check-circle text-success'
                                                    : 'bi-x-circle text-danger'
                                            "
                                        ></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Режим таблицы -->
            <div v-else-if="viewMode === 'table'" class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Тест</th>
                            <th>Пользователь</th>
                            <th>Баллы</th>
                            <th>Процент</th>
                            <th>Оценка</th>
                            <th>Время</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="result in filteredResults"
                            :key="getResultKey(result)"
                        >
                            <td>
                                <strong>{{ getTestTitle(result) }}</strong>
                            </td>
                            <td>
                                <span
                                    v-if="result.user_name"
                                    class="badge bg-info"
                                >
                                    <i class="bi bi-person"></i>
                                    {{ result.user_name }}
                                    ({{ result.user.name }})
                                </span>

                                <span v-else class="text-muted">—</span>
                            </td>
                            <td>
                                <span class="fw-bold">
                                    {{ result.total_score || 0 }}/{{
                                        result.max_score || 0
                                    }}
                                </span>
                            </td>
                            <td>
                                <span
                                    class="badge"
                                    :class="getBadgeClass(result.percentage)"
                                >
                                    {{ result.percentage }}%
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold">{{
                                    result.grade || "Не оценено"
                                }}</span>
                            </td>
                            <td>
                                <small>{{
                                    formatTime(result.time_spent)
                                }}</small>
                            </td>
                            <td>
                                <small>{{
                                    formatDate(result.created_at)
                                }}</small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button
                                        @click="toggleDetails(result)"
                                        class="btn btn-outline-primary"
                                        :title="
                                            isResultExpanded(result)
                                                ? 'Скрыть детали'
                                                : 'Показать детали'
                                        "
                                    >
                                        <i
                                            class="bi"
                                            :class="
                                                isResultExpanded(result)
                                                    ? 'bi-chevron-up'
                                                    : 'bi-chevron-down'
                                            "
                                        ></i>
                                    </button>
                                    <button
                                        @click="deleteResult(result)"
                                        class="btn btn-outline-danger"
                                        title="Удалить результат"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Детали результатов в табличном режиме -->
                <div
                    v-for="result in filteredResults.filter((r) =>
                        isResultExpanded(r)
                    )"
                    :key="'details-' + getResultKey(result)"
                    class="card mt-3"
                >
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            Детали результатов: {{ getTestTitle(result) }}
                            <span v-if="result.user_name" class="text-muted">
                                ({{ result.user_name }})
                            </span>
                        </h6>
                    </div>

                    <div class="card-body">
                        <div
                            v-for="(qResult, qIndex) in getQuestionResults(
                                result
                            )"
                            :key="qIndex"
                            class="question-result mb-3 p-3 rounded"
                            :class="
                                qResult.isCorrect
                                    ? 'bg-success bg-opacity-10'
                                    : 'bg-danger bg-opacity-10'
                            "
                        >
                            <h6 class="mb-1">
                                Вопрос {{ qIndex + 1 }}:
                                {{ qResult.question || "Без текста" }}
                            </h6>
                            <div class="mb-2">
                                <strong>Ваш ответ:</strong>
                                <div
                                    :class="
                                        qResult.isCorrect
                                            ? 'text-success'
                                            : 'text-danger'
                                    "
                                >
                                    {{ formatUserAnswer(qResult.userAnswer) }}
                                </div>
                            </div>
                            <div v-if="!qResult.isCorrect" class="mb-2">
                                <strong>Правильный ответ:</strong>
                                <div class="text-success">
                                    {{
                                        formatCorrectAnswer(
                                            qResult.correct_answer
                                        )
                                    }}
                                </div>
                            </div>
                            <div
                                class="d-flex justify-content-between align-items-center"
                            >
                                <span
                                    class="badge"
                                    :class="
                                        qResult.isCorrect
                                            ? 'bg-success'
                                            : 'bg-danger'
                                    "
                                >
                                    {{ (qResult.score || 0).toFixed(1) }}/{{
                                        qResult.max_score || 0
                                    }}
                                    баллов
                                </span>
                                <i
                                    class="bi"
                                    :class="
                                        qResult.isCorrect
                                            ? 'bi-check-circle text-success'
                                            : 'bi-x-circle text-danger'
                                    "
                                ></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Пагинация -->
            <div v-if="filteredResults.length === 0" class="text-center py-4">
                <i class="bi bi-search display-4 text-muted"></i>
                <p class="mt-2 text-muted">Результаты не найдены</p>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "TestResults",
    props: ["results", "datasend", "showToast", "getResult"],
    data() {
        return {
            sortBy: "date",
            gradeFilter: "",
            searchQuery: "",
            viewMode: "table", // 'cards' или 'table'
            expandedResults: new Set(),
        };
    },
    computed: {
        filteredResults() {
            let filtered = this.results;

            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase().trim();
                filtered = filtered.filter((result) => {
                    const testTitle = this.getTestTitle(result).toLowerCase();
                    const userName = (result.user_name || "").toLowerCase();
                    return (
                        testTitle.includes(query) || userName.includes(query)
                    );
                });
            }

            // Фильтр по оценке
            if (this.gradeFilter) {
                filtered = filtered.filter(
                    (result) => (result.grade || "") === this.gradeFilter
                );
            }

            // Сортировка
            switch (this.sortBy) {
                case "date":
                    filtered.sort(
                        (a, b) =>
                            new Date(b.created_at || 0) -
                            new Date(a.created_at || 0)
                    );
                    break;
                case "date_asc":
                    filtered.sort(
                        (a, b) =>
                            new Date(a.created_at || 0) -
                            new Date(b.created_at || 0)
                    );
                    break;
                case "score":
                    filtered.sort(
                        (a, b) => (b.percentage || 0) - (a.percentage || 0)
                    );
                    break;
                case "score_asc":
                    filtered.sort(
                        (a, b) => (a.percentage || 0) - (b.percentage || 0)
                    );
                    break;
                case "name":
                    filtered.sort((a, b) =>
                        this.getTestTitle(a).localeCompare(this.getTestTitle(b))
                    );
                    break;
            }

            return filtered;
        },
    },
    methods: {
        getResultKey(result) {
            return (
                (result.created_at || "") +
                (result.test_id || "") +
                (result.user_name || "")
            );
        },

        getTestTitle(result) {
            return result.test.title || "Без названия";
        },

        getQuestionResults(result) {
            return result.question_results || [];
        },

        isResultExpanded(result) {
            const resultKey = this.getResultKey(result);
            return this.expandedResults.has(resultKey);
        },

        toggleDetails(result) {
            const resultKey = this.getResultKey(result);
            if (this.expandedResults.has(resultKey)) {
                this.expandedResults.delete(resultKey);
            } else {
                this.expandedResults.add(resultKey);
            }
            this.$forceUpdate();
        },

        deleteResult(result) {
            if (!confirm("Вы уверены, что хотите удалить этот результат?")) {
                return;
            }

            this.datasend("results/" + result.id, "DELETE").then((response) => {
                this.showToast(response.message, "success");
                this.getResult();
            });
        },

        formatDate(created_at) {
            if (!created_at) return "Дата не указана";
            try {
                return new Date(created_at).toLocaleString("ru-RU");
            } catch (e) {
                return "Неверная дата";
            }
        },

        formatTime(seconds) {
            if (!seconds && seconds !== 0) return "Время не указано";
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins} мин ${secs} сек`;
        },

        handleImageError(event) {
            event.target.style.display = "none";
        },

        formatUserAnswer(userAnswer) {
            if (userAnswer === null || userAnswer === undefined) {
                return "Нет ответа";
            }

            if (Array.isArray(userAnswer)) {
                if (userAnswer.length === 0) return "Нет выбранных вариантов";
                return userAnswer.join(", ");
            }

            if (userAnswer == "true") return "Да";
            if (userAnswer == "false") return "Нет";

            return userAnswer.toString() || "Пустой ответ";
        },

        formatCorrectAnswer(correct_answer) {
            // return correct_answer;
            // console.log(correct_answer);

            if (correct_answer === null || correct_answer === undefined) {
                return "";
            }

            if (Array.isArray(correct_answer)) {
                // return correct_answer.join(", ");
                return correct_answer
                    .map((item, index) => `${index + 1}. ${item}`)
                    .join("; ");
            }

            if (correct_answer == "true" || correct_answer == true) return "Да";
            if (correct_answer == "false" || correct_answer == false)
                return "Нет";

            return correct_answer.toString();
        },

        getResultClass(percentage) {
            if (percentage >= 90) return "result-excellent";
            if (percentage >= 75) return "result-good";
            if (percentage >= 60) return "result-satisfactory";
            return "result-poor";
        },

        getBadgeClass(percentage) {
            if (percentage >= 90) return "bg-success";
            if (percentage >= 75) return "bg-info";
            if (percentage >= 60) return "bg-warning";
            return "bg-danger";
        },
    },
};
</script>

<style scoped>
.statistics {
    text-align: center;
}

.result-excellent {
    border-left: 4px solid #28a745;
}

.result-good {
    border-left: 4px solid #17a2b8;
}

.result-satisfactory {
    border-left: 4px solid #ffc107;
}

.result-poor {
    border-left: 4px solid #dc3545;
}

.matching-details {
    font-size: 0.9rem;
}

.table th,
.table td {
    vertical-align: middle;
}

.img-thumbnail {
    max-height: 40px;
    object-fit: cover;
}

.btn-group .btn.active {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
}
</style>
