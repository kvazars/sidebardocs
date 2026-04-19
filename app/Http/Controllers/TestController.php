<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\Tree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Laravel\Facades\Image;

// use Illuminate\Http\JsonResponse;

class TestController extends Controller
{
    public function testTree(Tree $tree_id)
    {
        $tests = Test::with('questions')->where("tree_id", $tree_id->id)->get();
        return response()->json(['data' => $tests]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'timeLimit' => 'required|integer|min:1',
            'settings' => 'nullable|array',
            'grading' => 'nullable|array',
            'tree_id' => 'required|exists:trees,id'
        ]);

        DB::transaction(function () use ($request, $validated) {
            $test = Test::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'timeLimit' => $validated['timeLimit'],
                'tree_id' => $validated['tree_id'],
                'settings' => $this->buildTestSettings($request->input('settings', [])),
                'grading' => $validated['grading'] ?? null
            ]);

            $this->syncQuestions($test, $request->input('questions', []));
        });

        return response()->json(['message' => 'Тест успешно создан!'], 201);
    }

    public function show(Test $test)
    {

        return response()->json(['data' => $test->load('questions')]);
    }


    private function processImage($imageData, $maxWidth = 800)
    {
        if (!$imageData) {
            return null;
        }
        try {
            if (strpos($imageData, 'data:image') === 0) {
                $base64 = substr($imageData, strpos($imageData, ',') + 1);
                $image = Image::read(base64_decode($base64));
            } else {
                $image = Image::read($imageData);
            }
            $image = $image->scale($maxWidth);

            $results = [
                'webp' => 'data:image/webp;base64,' . base64_encode($image->toWebp(80)->toString()),
                'jpg'  => 'data:image/jpeg;base64,' . base64_encode($image->toJpeg(85)->toString()),
                'png'  => 'data:image/png;base64,' . base64_encode($image->toPng()->toString())
            ];
            $getBytes = fn($uri) => strlen(base64_decode(substr($uri, strpos($uri, ',') + 1)));
            $sizes = array_map($getBytes, $results);
            $bestFormat = array_keys($sizes, min($sizes))[0];
            return $results[$bestFormat];
        } catch (\Exception $e) {
            return $imageData;
        }
    }

    public function update(Request $request, Test $test)
    {

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'timeLimit' => 'required|integer|min:1',
            'settings' => 'nullable|array',
            'grading' => 'nullable|array'
        ]);

        DB::transaction(function () use ($request, $validated, $test) {
            $test->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'timeLimit' => $validated['timeLimit'],
                'settings' => $this->buildTestSettings($request->input('settings', [])),
                'grading' => $validated['grading'] ?? null
            ]);

            if ($request->has('questions')) {
                $test->questions()->delete();
                $this->syncQuestions($test, $request->input('questions', []));
            }
        });

        return response()->json(['message' => 'Тест успешно обновлён!']);
    }


    private function processQuestionOptions($questionData)
    {
        if (!isset($questionData['options'])) {
            return $questionData['options'] ?? null;
        }

        $options = $questionData['options'];

        // Обработка в зависимости от типа вопроса
        switch ($questionData['type']) {
            case 'single':
            case 'multiple':
                // Для вопросов с вариантами выбора
                foreach ($options as &$option) {
                    if (isset($option['image']) && $option['image']) {
                        $option['image'] = $this->processImage($option['image'], 300);
                    }
                }
                break;

            case 'matching':
                // Для вопросов на сопоставление
                foreach ($options as &$pair) {
                    if (isset($pair['leftImage']) && $pair['leftImage']) {
                        $pair['leftImage'] = $this->processImage($pair['leftImage'], 300);
                    }
                }
                break;
        }

        return $options;
    }


    public function destroy(Test $test)
    {

        $test->delete();
        return response()->json(['message' => 'Тест успешно удалён!']);
    }

    public function import(Request $request)
    {

        $request->validate([
            'tree_id' => 'required|integer|exists:trees,id',
            'file' => 'required|file|mimes:json,xml',
            'format' => 'required|in:json,xml',
            'xml_data' => 'required_if:format,xml|string',
        ]);
        $format = $request->input('format');

        $file = $request->file('file');
        if ($format === 'json') {
            $testData = json_decode(file_get_contents($file->getPathname()), true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($testData)) {
                throw ValidationException::withMessages([
                    'file' => 'Не удалось прочитать JSON-файл теста.',
                ]);
            }
        } elseif ($format === 'xml') {
            $decodedXmlData = json_decode($request->input('xml_data'), true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decodedXmlData)) {
                throw ValidationException::withMessages([
                    'xml_data' => 'Ошибка декодирования XML данных.',
                ]);
            }

            $grading = json_decode($request->input('grading', 'null'), true);
            $testData = ['grading' => is_array($grading) ? $grading : null] + $decodedXmlData;
        }

        $testData = $this->normalizeImportedTestData($testData);

        DB::transaction(function () use ($request, $testData) {
            $test = Test::create([
                'title' => $testData['title'] . ' (импорт)',
                'description' => $testData['description'],
                'timeLimit' => $testData['timeLimit'],
                'settings' => $testData['settings'],
                'grading' => $testData['grading'],
                'tree_id' => $request->tree_id,
            ]);

            $this->syncQuestions($test, $testData['questions']);
        });

        return response()->json(['message' => 'Импорт произошёл успешно!']);
    }

    public function export(Test $test): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {

        $testData = [
            'id' => $test->id,
            'title' => $test->title,
            'description' => $test->description,
            'timeLimit' => $test->timeLimit,
            'settings' => $test->settings,
            'grading' => $test->grading,
            'questions' => $test->questions->map(function ($question) {
                return [
                    'id' => $question->id,
                    'stable_key' => $question->stable_key,
                    'text' => $question->text,
                    'type' => $question->type,
                    'points' => $question->points,
                    'image' => $question->image,
                    'options' => $question->options,
                    'order' => $question->order
                ];
            })->toArray()
        ];

        $filename = "test-{$test->id}.json";
        $filepath = public_path($filename);

        file_put_contents($filepath, json_encode($testData, JSON_PRETTY_PRINT));

        return response()->download($filepath)->deleteFileAfterSend(true);
    }

    private function syncQuestions(Test $test, array $questions): void
    {
        foreach ($questions as $index => $questionData) {
            $normalizedQuestion = $this->normalizeQuestionData($questionData, $index);

            $test->questions()->create([
                'stable_key' => $normalizedQuestion['stable_key'],
                'text' => $normalizedQuestion['text'],
                'type' => $normalizedQuestion['type'],
                'points' => $normalizedQuestion['points'],
                'image' => $this->processImage($normalizedQuestion['image'] ?? null),
                'options' => $this->processQuestionOptions($normalizedQuestion),
                'order' => $normalizedQuestion['order'] ?? $index,
            ]);
        }
    }

    private function buildTestSettings(array $settings = []): array
    {
        return [
            'shuffleQuestions' => (bool) data_get($settings, 'shuffleQuestions', false),
            'shuffleAnswers' => (bool) data_get($settings, 'shuffleAnswers', false),
            'showCorrectAnswersAfterFinish' => (bool) data_get($settings, 'showCorrectAnswersAfterFinish', false),
            'allowRetake' => (bool) data_get($settings, 'allowRetake', true),
            'allowQuestionNavigation' => (bool) data_get($settings, 'allowQuestionNavigation', true),
            'randomQuestionCount' => max(0, min(10000, (int) data_get($settings, 'randomQuestionCount', 0))),
        ];
    }

    private function normalizeImportedTestData(array $testData): array
    {
        if (empty($testData['title']) || !is_string($testData['title'])) {
            throw ValidationException::withMessages([
                'file' => 'В импортируемом тесте отсутствует название.',
            ]);
        }

        if (empty($testData['questions']) || !is_array($testData['questions'])) {
            throw ValidationException::withMessages([
                'file' => 'В импортируемом тесте отсутствуют вопросы.',
            ]);
        }

        $normalizedQuestions = [];
        foreach ($testData['questions'] as $index => $questionData) {
            $normalizedQuestions[] = $this->normalizeQuestionData($questionData, $index);
        }

        return [
            'title' => $testData['title'],
            'description' => $testData['description'] ?? null,
            'timeLimit' => max(1, (int) ($testData['timeLimit'] ?? 30)),
            'settings' => $this->buildTestSettings($testData['settings'] ?? []),
            'grading' => isset($testData['grading']) && is_array($testData['grading']) ? $testData['grading'] : null,
            'questions' => $normalizedQuestions,
        ];
    }

    private function normalizeQuestionData(array $questionData, ?int $index = null): array
    {
        $type = $questionData['type'] ?? null;
        $supportedTypes = ['single', 'multiple', 'truefalse', 'text', 'matching', 'sorting'];

        if (!$type || !in_array($type, $supportedTypes, true)) {
            throw ValidationException::withMessages([
                'questions' => 'Обнаружен неподдерживаемый тип вопроса при сохранении теста.',
            ]);
        }

        $options = $questionData['options'] ?? null;
        if ($options === null && isset($questionData['items']) && is_array($questionData['items'])) {
            $options = $questionData['items'];
        }
        if ($options === null && isset($questionData['answers']) && is_array($questionData['answers'])) {
            $options = $questionData['answers'];
        }

        if ($type === 'truefalse' && is_array($options) && isset($options[0]) && is_string($options[0])) {
            $options = $options[0];
        }

        $normalizedQuestion = [
            'stable_key' => (string) ($questionData['stable_key'] ?? Str::uuid()),
            'text' => (string) ($questionData['text'] ?? ''),
            'type' => $type,
            'points' => max(1, (int) ($questionData['points'] ?? 1)),
            'image' => $questionData['image'] ?? null,
            'options' => $options,
            'order' => $questionData['order'] ?? $index ?? 0,
        ];

        $validationError = $this->validateQuestionData($normalizedQuestion);
        if ($validationError !== null) {
            $questionNumber = $index !== null ? $index + 1 : '?';

            throw ValidationException::withMessages([
                'questions' => sprintf('Вопрос %s: %s', $questionNumber, $validationError),
            ]);
        }

        return $normalizedQuestion;
    }

    private function validateQuestionData(array $questionData): ?string
    {
        $text = trim((string) ($questionData['text'] ?? ''));
        $type = $questionData['type'] ?? null;
        $options = $questionData['options'] ?? null;

        if ($text === '') {
            return 'не заполнен текст вопроса.';
        }

        switch ($type) {
            case 'single':
            case 'multiple':
                if (!is_array($options) || count($options) < 2) {
                    return 'должно быть не менее 2 вариантов ответа.';
                }

                $correctCount = 0;
                foreach ($options as $optionIndex => $option) {
                    $optionText = trim((string) ($option['text'] ?? ''));
                    $hasImage = !empty($option['image']);

                    if ($optionText === '' && !$hasImage) {
                        return sprintf(
                            'вариант ответа %d не заполнен.',
                            $optionIndex + 1
                        );
                    }

                    if (($option['correct'] ?? false) === true) {
                        $correctCount++;
                    }
                }

                if ($type === 'single' && $correctCount !== 1) {
                    return 'для одиночного выбора должен быть выбран ровно один правильный ответ.';
                }

                if ($type === 'multiple' && $correctCount < 1) {
                    return 'для множественного выбора должен быть выбран хотя бы один правильный ответ.';
                }

                break;

            case 'truefalse':
                if (is_array($options) && isset($options[0]) && is_string($options[0])) {
                    $options = $options[0];
                }

                if (!in_array($options, ['true', 'false'], true)) {
                    return 'для вопроса Да/Нет не выбран правильный ответ.';
                }
                break;

            case 'text':
                if (!is_array($options) || count($options) === 0) {
                    return 'не указаны правильные ответы.';
                }

                foreach ($options as $answerIndex => $answer) {
                    if (trim((string) $answer) === '') {
                        return sprintf(
                            'правильный ответ %d не заполнен.',
                            $answerIndex + 1
                        );
                    }
                }
                break;

            case 'matching':
                if (!is_array($options) || count($options) < 2) {
                    return 'должно быть не менее 2 пар для сопоставления.';
                }

                foreach ($options as $pairIndex => $pair) {
                    $left = trim((string) ($pair['left'] ?? ''));
                    $right = trim((string) ($pair['right'] ?? ''));
                    $hasLeftImage = !empty($pair['leftImage']);

                    if ($left === '' && !$hasLeftImage && $right === '') {
                        return sprintf('пара %d не заполнена.', $pairIndex + 1);
                    }

                    if ($left === '' && !$hasLeftImage) {
                        return sprintf('левая часть пары %d не заполнена.', $pairIndex + 1);
                    }

                    if ($right === '') {
                        return sprintf('правая часть пары %d не заполнена.', $pairIndex + 1);
                    }
                }
                break;

            case 'sorting':
                if (!is_array($options) || count($options) < 2) {
                    return 'должно быть не менее 2 элементов для сортировки.';
                }

                foreach ($options as $itemIndex => $item) {
                    $itemText = trim((string) ($item['text'] ?? ''));
                    $hasImage = !empty($item['image']);

                    if ($itemText === '' && !$hasImage) {
                        return sprintf(
                            'элемент %d не заполнен.',
                            $itemIndex + 1
                        );
                    }
                }
                break;
        }

        return null;
    }
}
