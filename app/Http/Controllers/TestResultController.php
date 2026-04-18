<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\TestResult;
use App\Models\Tree;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TestResultController extends Controller
{
    // public function index(): JsonResponse
    // {
    //     $results = TestResult::with('test')->get();
    //     return response()->json(['data' => $results]);
    // }
    public function resultTree(Tree $tree_id): JsonResponse
    {
        $test = Test::where("tree_id", $tree_id->id)->pluck('id')->toArray();
        if ($test) {
            $results = TestResult::with('test', 'user')->whereIn("test_id", $test)->get();
            return response()->json(['data' => $results]);
        }
        return response()->json(['data' => []]);
    }
    public function resultAll(): JsonResponse
    {
        $tree = Tree::where("user_id", Auth::id())->pluck("id")->toArray();

        $test = Test::whereIn("tree_id", $tree)->pluck('id')->toArray();

        $results = TestResult::with('test', 'user')->whereIn("test_id", $test)->orderBy("created_at", "desc")->get();
        return response()->json(['data' => $results]);
    }

    public function bulkDelete(Request $request)
    {
        TestResult::whereIn("id", $request->result_ids)->delete();
        return response()->json(['message' => 'Успешно удалены выбранные резульаты']);
    }




    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'test_id' => 'required|exists:tests,id',
            'user_name' => 'nullable|string|max:255',
            'time_spent' => 'required|integer',
            'user_answers' => 'required|array',
        ]);

        $test = Test::with('questions')->findOrFail($validated['test_id']);
        $calculatedResult = $this->calculateResult($test, $validated['user_answers']);

        TestResult::create([
            'user_id' => Auth::id(),
            'test_id' => $test->id,
            'user_name' => $validated['user_name'] ?? null,
            'total_score' => $calculatedResult['total_score'],
            'max_score' => $calculatedResult['max_score'],
            'percentage' => $calculatedResult['percentage'],
            'grade' => $calculatedResult['grade'],
            'time_spent' => max(0, (int) $validated['time_spent']),
            'question_results' => $calculatedResult['question_results'],
        ]);


        return response()->json(['message' => 'Спасибо за прохождение теста!']);
    }

    public function show(TestResult $result): JsonResponse
    {
        return response()->json(['data' => $result->load('test')]);
    }

    public function destroy(TestResult $result): JsonResponse
    {
        $result->delete();
        return response()->json(['message' => 'Результат успешно удалён']);
    }

    public function clear()
    {
        // TestResult::delete();
        return response()->json(['message' => 'All results cleared successfully']);
    }

    public function testResults($testId): JsonResponse
    {
        $results = TestResult::where('test_id', $testId)->with('test')->get();
        return response()->json(['data' => $results]);
    }

    private function calculateResult(Test $test, array $submittedAnswers): array
    {
        $questions = $test->questions->values();
        $questionResults = [];
        $totalScore = 0;
        $maxScore = 0;

        foreach ($questions as $originalIndex => $question) {
            $options = is_array($question->options) ? $question->options : [];
            $maxPoints = (float) $question->points;
            $maxScore += $maxPoints;

            $userAnswer = $submittedAnswers[(string) $originalIndex] ?? $submittedAnswers[$originalIndex] ?? null;
            $evaluation = $this->evaluateQuestion($question->type, $options, $userAnswer, $maxPoints);
            $totalScore += $evaluation['score'];

            $questionResults[] = [
                'question' => $question->text,
                'userAnswer' => $this->formatUserAnswerForDisplay($question->type, $options, $userAnswer),
                'correct_answer' => $this->getCorrectAnswer($question->type, $options),
                'isCorrect' => $evaluation['isCorrect'],
                'score' => $evaluation['score'],
                'max_score' => $maxPoints,
                'originalIndex' => $originalIndex,
                'answered' => $evaluation['answered'],
            ];
        }

        $percentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100) : 0;

        return [
            'total_score' => $totalScore,
            'max_score' => $maxScore,
            'percentage' => $percentage,
            'grade' => $this->calculateGrade($test, $percentage),
            'question_results' => $questionResults,
        ];
    }

    private function evaluateQuestion(string $type, array $options, mixed $userAnswer, float $maxPoints): array
    {
        $answered = false;
        $isCorrect = false;

        switch ($type) {
            case 'single':
                $answered = $userAnswer !== null && $userAnswer !== '' && is_numeric($userAnswer);
                if ($answered) {
                    $selectedIndex = (int) $userAnswer;
                    $correctIndex = collect($options)->search(fn($option) => ($option['correct'] ?? false) === true);
                    $isCorrect = $correctIndex !== false && $selectedIndex === $correctIndex;
                }
                break;

            case 'truefalse':
                $answered = $userAnswer !== null && $userAnswer !== '';
                if ($answered) {
                    $correctValue = $this->getTrueFalseCorrectValue($options);
                    $isCorrect = (string) $userAnswer === $correctValue;
                }
                break;

            case 'multiple':
                $selectedValues = is_array($userAnswer) ? array_values(array_map('intval', $userAnswer)) : [];
                $answered = count($selectedValues) > 0;
                $correctIndices = collect($options)
                    ->filter(fn($option) => ($option['correct'] ?? false) === true)
                    ->keys()
                    ->map(fn($index) => (int) $index)
                    ->values()
                    ->all();
                sort($selectedValues);
                sort($correctIndices);
                $isCorrect = $answered && $selectedValues === $correctIndices;
                break;

            case 'text':
                $normalizedAnswer = is_string($userAnswer) ? mb_strtolower(trim($userAnswer)) : '';
                $answered = $normalizedAnswer !== '';
                $correctAnswers = collect($options)
                    ->filter(fn($answer) => is_string($answer) && trim($answer) !== '')
                    ->map(fn($answer) => mb_strtolower(trim($answer)))
                    ->values()
                    ->all();
                $isCorrect = $answered && in_array($normalizedAnswer, $correctAnswers, true);
                break;

            case 'matching':
                $selectedPairs = is_array($userAnswer) ? array_values($userAnswer) : [];
                $answered = count($selectedPairs) === count($options)
                    && collect($selectedPairs)->every(fn($value) => $value !== null && trim((string) $value) !== '');
                if ($answered) {
                    $expectedPairs = array_map(
                        fn($pair) => (string) ($pair['right'] ?? ''),
                        $options
                    );
                    $isCorrect = $selectedPairs === $expectedPairs;
                }
                break;

            case 'sorting':
                $selectedOrder = is_array($userAnswer) ? array_values(array_map('intval', $userAnswer)) : [];
                $answered = count($selectedOrder) === count($options);
                if ($answered) {
                    $correctOrder = collect($options)
                        ->map(function ($option, $index) {
                            return [
                                'index' => (int) $index,
                                'position' => (int) ($option['correctPosition'] ?? $index),
                            ];
                        })
                        ->sortBy('position')
                        ->pluck('index')
                        ->values()
                        ->all();
                    $isCorrect = $selectedOrder === $correctOrder;
                }
                break;

            default:
                throw ValidationException::withMessages([
                    'user_answers' => 'Получен неподдерживаемый тип вопроса при проверке результата.',
                ]);
        }

        return [
            'answered' => $answered,
            'isCorrect' => $isCorrect,
            'score' => $isCorrect ? $maxPoints : 0,
        ];
    }

    private function getCorrectAnswer(string $type, array $options): mixed
    {
        return match ($type) {
            'single' => collect($options)->firstWhere('correct', true)['text'] ?? '',
            'truefalse' => $this->getTrueFalseCorrectValue($options) === 'true' ? 'Да' : 'Нет',
            'multiple' => collect($options)
                ->filter(fn($option) => ($option['correct'] ?? false) === true)
                ->pluck('text')
                ->values()
                ->all(),
            'text' => $options,
            'matching' => collect($options)
                ->map(fn($pair) => sprintf('%s → %s', $pair['left'] ?? '', $pair['right'] ?? ''))
                ->implode('; '),
            'sorting' => collect($options)
                ->map(function ($option, $index) {
                    return [
                        'text' => $option['text'] ?? sprintf('Элемент %d', $index + 1),
                        'position' => (int) ($option['correctPosition'] ?? $index),
                    ];
                })
                ->sortBy('position')
                ->values()
                ->map(fn($option, $index) => sprintf('%d. %s', $index + 1, $option['text']))
                ->implode('; '),
            default => '',
        };
    }

    private function formatUserAnswerForDisplay(string $type, array $options, mixed $userAnswer): string
    {
        if ($userAnswer === null || $userAnswer === '' || $userAnswer === []) {
            return '';
        }

        return match ($type) {
            'single' => $options[(int) $userAnswer]['text'] ?? '',
            'truefalse' => (string) $userAnswer === 'true' ? 'Да' : 'Нет',
            'multiple' => collect(is_array($userAnswer) ? $userAnswer : [])
                ->map(fn($index) => $options[(int) $index]['text'] ?? sprintf('Вариант %d', ((int) $index) + 1))
                ->implode(', '),
            'text' => (string) $userAnswer,
            'matching' => collect(is_array($userAnswer) ? $userAnswer : [])
                ->map(function ($rightValue, $index) use ($options) {
                    $leftValue = $options[$index]['left'] ?? sprintf('Элемент %d', $index + 1);
                    return sprintf('%s → %s', $leftValue, (string) $rightValue);
                })
                ->implode('; '),
            'sorting' => collect(is_array($userAnswer) ? $userAnswer : [])
                ->map(function ($itemIndex, $position) use ($options) {
                    $itemText = $options[(int) $itemIndex]['text'] ?? sprintf('Элемент %d', ((int) $itemIndex) + 1);
                    return sprintf('%d. %s', $position + 1, $itemText);
                })
                ->implode('; '),
            default => is_scalar($userAnswer) ? (string) $userAnswer : '',
        };
    }

    private function calculateGrade(Test $test, int $percentage): string
    {
        $grading = is_array($test->grading) ? $test->grading : [];
        if (!$grading) {
            return 'Не оценено';
        }

        usort($grading, fn($left, $right) => ((int) ($right['minScore'] ?? 0)) <=> ((int) ($left['minScore'] ?? 0)));

        foreach ($grading as $grade) {
            if ($percentage >= (int) ($grade['minScore'] ?? 0)) {
                return (string) ($grade['grade'] ?? 'Не оценено');
            }
        }

        return 'Не оценено';
    }

    private function getTrueFalseCorrectValue(mixed $options): string
    {
        if (is_string($options)) {
            return $options === 'true' ? 'true' : 'false';
        }

        $isListArray = is_array($options) && ($options === [] || array_keys($options) === range(0, count($options) - 1));
        if ($isListArray) {
            if (isset($options[0]) && is_string($options[0]) && in_array($options[0], ['true', 'false'], true)) {
                return $options[0];
            }

            $correctOption = collect($options)->firstWhere('correct', true);
            if (is_array($correctOption)) {
                $text = mb_strtolower(trim((string) ($correctOption['text'] ?? '')));
                if (in_array($text, ['да', 'верно', 'true', '1'], true)) {
                    return 'true';
                }
            }
        }

        return 'false';
    }
}
