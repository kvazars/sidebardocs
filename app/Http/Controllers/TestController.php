<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\Tree;
use Illuminate\Http\Request;
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

        $test = Test::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'timeLimit' => $validated['timeLimit'],
            'tree_id' => $validated['tree_id'],
            'settings' => [
                'shuffleQuestions' => $request->input('settings.shuffleQuestions', false),
                'shuffleAnswers' => $request->input('settings.shuffleAnswers', false),
            ],
            'grading' => $validated['grading'] ?? null
        ]);


        if ($request->has('questions')) {
            foreach ($request->questions as $questionData) {
                $test->questions()->create([
                    'text' => $questionData['text'],
                    'type' => $questionData['type'],
                    'points' => $questionData['points'],
                    'image' => $this->processImage($questionData['image']),
                    'options' => $this->processQuestionOptions($questionData),
                    'order' => $questionData['order'] ?? 0
                ]);
            }
        }

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

        $test->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'timeLimit' => $validated['timeLimit'],
            'settings' => [
                'shuffleQuestions' => $request->input('settings.shuffleQuestions', false),
                'shuffleAnswers' => $request->input('settings.shuffleAnswers', false),
            ],
            'grading' => $validated['grading'] ?? null
        ]);

        // Обновляем вопросы
        if ($request->has('questions')) {
            $test->questions()->delete(); // Удаляем старые вопросы

            foreach ($request->questions as $questionData) {
                $test->questions()->create([
                    'text' => $questionData['text'],
                    'type' => $questionData['type'],
                    'points' => $questionData['points'],
                    'image' => $this->processImage($questionData['image']),
                    'options' => $this->processQuestionOptions($questionData),
                    'order' => $questionData['order'] ?? 0
                ]);
            }
        }

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
        ]);
        $format = $request->input('format');

        $file = $request->file('file');
        if ($format === 'json') {
            $testData = json_decode(file_get_contents($file->getPathname()), true);
        } elseif ($format === 'xml') {
            if ($request->has('xml_data')) {
                $testData = ["grading" => json_decode($request->grading)] + json_decode($request->input('xml_data'), true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Ошибка декодирования XML данных');
                }
            }
        }
        $test = Test::create([
            'title' => $testData['title'] . ' (импорт)',
            'description' => $testData['description'],
            'timeLimit' => $testData['timeLimit'],
            'settings' => $testData['settings'],
            'grading' => $testData['grading'],
            'tree_id' => $request->tree_id,
        ]);

        foreach ($testData['questions'] as $questionData) {
            $test->questions()->create([
                'text' => $questionData['text'],
                'type' => $questionData['type'],
                'points' => $questionData['points'],
                'image' => $this->processImage($questionData['image']),
                'options' => $this->processQuestionOptions($questionData),
                'items' => $questionData['items'] ?? null,
                'order' => $questionData['order'] ?? 0
            ]);
        }

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
                    'text' => $question->text,
                    'type' => $question->type,
                    'points' => $question->points,
                    'image' => $question->image,
                    'options' => $question->options,
                    'items' => $question['items'] ?? null,
                    'order' => $question->order
                ];
            })->toArray()
        ];

        $filename = "test-{$test->id}.json";
        $filepath = public_path($filename);

        file_put_contents($filepath, json_encode($testData, JSON_PRETTY_PRINT));

        return response()->download($filepath)->deleteFileAfterSend(true);
    }
}
