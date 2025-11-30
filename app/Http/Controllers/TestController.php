<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TestController extends Controller
{
    public function index()
    {
        $tests = Test::with('questions')->get();
        return response()->json(['data' => $tests]);
    }

    public function store(Request $request)
    {   
        

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'timeLimit' => 'required|integer|min:1',
            'settings' => 'nullable|array',
            'grading' => 'nullable|array'
        ]);

        $test = Test::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'timeLimit' => $validated['timeLimit'],
            'settings' => [
                'allowDetailedResults' => true,
                'requireUserName' => $request->input('settings.requireUserName', false),
                'showUserNameInResults' => $request->input('settings.showUserNameInResults', true),
                'shuffleQuestions' => $request->input('settings.shuffleQuestions', false),
                'shuffleAnswers' => $request->input('settings.shuffleAnswers', false),
                'shuffleSingleChoice' => $request->input('settings.shuffleSingleChoice', true),
                'shuffleMultipleChoice' => $request->input('settings.shuffleMultipleChoice', true),
                'shuffleMatching' => $request->input('settings.shuffleMatching', true),
            ],
            'grading' => $validated['grading'] ?? null
        ]);

        // Сохраняем вопросы
        if ($request->has('questions')) {
            foreach ($request->questions as $questionData) {
                $test->questions()->create([
                    'text' => $questionData['text'],
                    'type' => $questionData['type'],
                    'points' => $questionData['points'],
                    'image' => $questionData['image'] ?? null,
                    'options' => $questionData['options'] ?? null,
                    'correct_answers' => $questionData['correct_answers'] ?? null,
                    'pairs' => $questionData['pairs'] ?? null,
                    'correct_answer' => $questionData['correct_answer'] ?? null,
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
                'allowDetailedResults' => true,
                'requireUserName' => $request->input('settings.requireUserName', false),
                'showUserNameInResults' => $request->input('settings.showUserNameInResults', true),
                'shuffleQuestions' => $request->input('settings.shuffleQuestions', false),
                'shuffleAnswers' => $request->input('settings.shuffleAnswers', false),
                'shuffleSingleChoice' => $request->input('settings.shuffleSingleChoice', true),
                'shuffleMultipleChoice' => $request->input('settings.shuffleMultipleChoice', true),
                'shuffleMatching' => $request->input('settings.shuffleMatching', true),
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
                    'image' => $questionData['image'] ?? null,
                    'options' => $questionData['options'] ?? null,
                    'correct_answers' => $questionData['correct_answers'] ?? null,
                    'pairs' => $questionData['pairs'] ?? null,
                    'correct_answer' => $questionData['correct_answer'] ?? null,
                    'order' => $questionData['order'] ?? 0
                ]);
            }
        }

        return response()->json(['message' => 'Тест успешно обновлён!']);
    }

    public function destroy(Test $test)
    {

        $test->delete();
        return response()->json(['message' => 'Тест успешно удалён!']);
    }

    public function import(Request $request)
    {

        $request->validate([
            'file' => 'required|file|mimes:json'
        ]);

        $file = $request->file('file');
        $testData = json_decode(file_get_contents($file->getPathname()), true);

        $test = Test::create([
            'title' => $testData['title'] . ' (import)',
            'description' => $testData['description'],
            'timeLimit' => $testData['timeLimit'],
            'settings' => $testData['settings'],
            'grading' => $testData['grading']
        ]);

        foreach ($testData['questions'] as $questionData) {
            $test->questions()->create([
                'text' => $questionData['text'],
                'type' => $questionData['type'],
                'points' => $questionData['points'],
                'image' => $questionData['image'] ?? null,
                'options' => $questionData['options'] ?? null,
                'correct_answers' => $questionData['correct_answers'] ?? null,
                'pairs' => $questionData['pairs'] ?? null,
                'correct_answer' => $questionData['correct_answer'] ?? null,
                'order' => $questionData['order'] ?? 0
            ]);
        }

        return response()->json(['data' => $test->load('questions')], 201);
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
                    'correct_answers' => $question->correct_answers,
                    'pairs' => $question->pairs,
                    'correct_answer' => $question->correct_answer,
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
