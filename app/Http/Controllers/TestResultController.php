<?php

namespace App\Http\Controllers;

use App\Models\TestResult;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TestResultController extends Controller
{
    public function index(): JsonResponse
    {
        $results = TestResult::with('test')->get();
        return response()->json(['data' => $results]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'test_id' => 'required|exists:tests,id',
            'user_name' => 'nullable|string|max:255',
            'total_score' => 'required|numeric',
            'max_score' => 'required|numeric',
            'percentage' => 'required|numeric',
            'grade' => 'required|string',
            'time_spent' => 'required|integer',
            'settings' => 'nullable|array',
            'question_results' => 'nullable|array'
        ]);

        TestResult::create($validated);


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
}
