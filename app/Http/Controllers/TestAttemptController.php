<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\TestAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TestAttemptController extends Controller
{
    public function store(Request $request, Test $test): JsonResponse
    {
        $validated = $request->validate([
            'user_name' => 'nullable|string|max:255',
        ]);

        $test->load('questions');
        $this->ensureAttemptIsAllowed($test, $validated['user_name'] ?? null);

        $questionIds = $this->selectQuestionIdsForAttempt($test);
        $attempt = TestAttempt::create([
            'user_id' => Auth::id(),
            'test_id' => $test->id,
            'user_name' => $validated['user_name'] ?? null,
            'question_ids' => $questionIds,
            'started_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'attempt_id' => $attempt->id,
                'test' => $this->buildAttemptTestPayload($test, $questionIds),
            ],
        ], 201);
    }

    private function selectQuestionIdsForAttempt(Test $test): array
    {
        $questions = $test->questions->values();
        $bankCount = $questions->count();
        $randomQuestionCount = (int) data_get($test->settings, 'randomQuestionCount', 0);

        if ($randomQuestionCount > 0 && $randomQuestionCount < $bankCount) {
            return $questions
                ->shuffle()
                ->take($randomQuestionCount)
                ->pluck('id')
                ->values()
                ->all();
        }

        return $questions->pluck('id')->values()->all();
    }

    private function buildAttemptTestPayload(Test $test, array $questionIds): array
    {
        $questionMap = $test->questions->keyBy('id');
        $questions = collect($questionIds)
            ->map(fn($id) => $questionMap->get($id))
            ->filter()
            ->values()
            ->map(fn($question) => $this->sanitizeQuestionForAttempt($question))
            ->all();

        return [
            'id' => $test->id,
            'title' => $test->title,
            'description' => $test->description,
            'timeLimit' => $test->timeLimit,
            'settings' => $test->settings,
            'grading' => $test->grading,
            'tree_id' => $test->tree_id,
            '_bankQuestionCount' => $test->questions->count(),
            'questions' => $questions,
        ];
    }

    private function sanitizeQuestionForAttempt($question): array
    {
        return [
            'id' => $question->id,
            'stable_key' => $question->stable_key,
            'text' => $question->text,
            'type' => $question->type,
            'points' => $question->points,
            'image' => $question->image,
            'options' => $this->sanitizeQuestionOptionsForAttempt($question->type, $question->options),
            'order' => $question->order,
        ];
    }

    private function sanitizeQuestionOptionsForAttempt(string $type, mixed $options): mixed
    {
        if (!is_array($options)) {
            return $type === 'truefalse' ? null : $options;
        }

        return match ($type) {
            'single', 'multiple' => collect($options)
                ->map(function ($option) {
                    if (is_array($option)) {
                        unset($option['correct']);
                    }

                    return $option;
                })
                ->values()
                ->all(),
            'truefalse', 'text' => null,
            'matching' => $this->sanitizeMatchingOptionsForAttempt($options),
            'sorting' => collect($options)
                ->map(function ($option) {
                    if (is_array($option)) {
                        unset($option['correctPosition']);
                    }

                    return $option;
                })
                ->values()
                ->all(),
            default => $options,
        };
    }

    private function sanitizeMatchingOptionsForAttempt(array $options): array
    {
        $rightOptions = collect($options)
            ->map(fn($pair) => is_array($pair) ? (string) ($pair['right'] ?? '') : '')
            ->shuffle()
            ->values()
            ->all();

        return collect($options)
            ->values()
            ->map(function ($pair, $index) use ($rightOptions) {
                if (!is_array($pair)) {
                    return $pair;
                }

                return [
                    'left' => $pair['left'] ?? '',
                    'leftImage' => $pair['leftImage'] ?? null,
                    'right' => $rightOptions[$index] ?? '',
                ];
            })
            ->all();
    }

    private function ensureAttemptIsAllowed(Test $test, ?string $userName): void
    {
        if ((bool) data_get($test->settings, 'allowRetake', true)) {
            return;
        }

        $normalizedUserName = mb_strtolower(trim((string) $userName));
        $authId = Auth::id();

        $query = $test->results();
        $query->where(function ($attemptQuery) use ($authId, $normalizedUserName) {
            if ($authId) {
                $attemptQuery->where('user_id', $authId);
            }

            if ($normalizedUserName !== '') {
                $method = $authId ? 'orWhereRaw' : 'whereRaw';
                $attemptQuery->{$method}('LOWER(TRIM(user_name)) = ?', [$normalizedUserName]);
            }
        });

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'test_id' => 'Повторное прохождение этого теста запрещено.',
            ]);
        }
    }
}
