<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\TestResult;
use App\Models\Tree;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

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
            'question_results' => 'nullable|array'
        ]);

        TestResult::create(["user_id" => Auth::id()] + $validated);


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
