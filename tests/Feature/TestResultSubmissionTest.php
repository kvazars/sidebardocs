<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Test;
use App\Models\TestResult;
use App\Models\Tree;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TestResultSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_result_is_calculated_only_for_selected_question_subset(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        [$test, $questions] = $this->createTestWithQuestions([
            [
                'text' => 'Q1',
                'type' => 'single',
                'points' => 2,
                'options' => [
                    ['text' => 'A', 'correct' => true],
                    ['text' => 'B', 'correct' => false],
                ],
            ],
            [
                'text' => 'Q2',
                'type' => 'single',
                'points' => 3,
                'options' => [
                    ['text' => 'A', 'correct' => false],
                    ['text' => 'B', 'correct' => true],
                ],
            ],
            [
                'text' => 'Q3',
                'type' => 'truefalse',
                'points' => 5,
                'options' => 'true',
            ],
        ]);

        $response = $this->postJson('/api/results', [
            'test_id' => $test->id,
            'user_name' => 'Tester',
            'time_spent' => 120,
            'question_ids' => [$questions[1]->id, $questions[2]->id],
            'user_answers' => [
                0 => 1,
                1 => 'true',
            ],
        ]);

        $response->assertOk();

        $result = TestResult::query()->sole();

        $this->assertSame('8.00', number_format((float) $result->max_score, 2, '.', ''));
        $this->assertSame('8.00', number_format((float) $result->total_score, 2, '.', ''));
        $this->assertSame('100.00', number_format((float) $result->percentage, 2, '.', ''));
        $this->assertCount(2, $result->question_results);
        $this->assertSame($questions[1]->id, $result->question_results[0]['question_id']);
        $this->assertSame($questions[2]->id, $result->question_results[1]['question_id']);
        $this->assertSame($questions[1]->stable_key, $result->question_results[0]['question_stable_key']);
        $this->assertSame($questions[2]->stable_key, $result->question_results[1]['question_stable_key']);
    }

    public function test_question_ids_define_the_answer_order_for_result_checking(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        [$test, $questions] = $this->createTestWithQuestions([
            [
                'text' => 'First in bank',
                'type' => 'single',
                'points' => 2,
                'options' => [
                    ['text' => 'A', 'correct' => true],
                    ['text' => 'B', 'correct' => false],
                ],
            ],
            [
                'text' => 'Second in bank',
                'type' => 'single',
                'points' => 4,
                'options' => [
                    ['text' => 'A', 'correct' => false],
                    ['text' => 'B', 'correct' => true],
                ],
            ],
        ]);

        $response = $this->postJson('/api/results', [
            'test_id' => $test->id,
            'user_name' => 'Tester',
            'time_spent' => 75,
            'question_ids' => [$questions[1]->id, $questions[0]->id],
            'user_answers' => [
                0 => 1,
                1 => 0,
            ],
        ]);

        $response->assertOk();

        $result = TestResult::query()->sole();

        $this->assertSame('6.00', number_format((float) $result->total_score, 2, '.', ''));
        $this->assertSame($questions[1]->id, $result->question_results[0]['question_id']);
        $this->assertSame($questions[0]->id, $result->question_results[1]['question_id']);
        $this->assertSame($questions[1]->stable_key, $result->question_results[0]['question_stable_key']);
        $this->assertSame($questions[0]->stable_key, $result->question_results[1]['question_stable_key']);
        $this->assertTrue($result->question_results[0]['isCorrect']);
        $this->assertTrue($result->question_results[1]['isCorrect']);
    }

    public function test_result_submission_rejects_question_ids_from_another_test(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        [$test, $questions] = $this->createTestWithQuestions([
            [
                'text' => 'Owned question',
                'type' => 'single',
                'points' => 1,
                'options' => [
                    ['text' => 'A', 'correct' => true],
                    ['text' => 'B', 'correct' => false],
                ],
            ],
        ]);

        [, $foreignQuestions] = $this->createTestWithQuestions([
            [
                'text' => 'Foreign question',
                'type' => 'single',
                'points' => 1,
                'options' => [
                    ['text' => 'A', 'correct' => true],
                    ['text' => 'B', 'correct' => false],
                ],
            ],
        ]);

        $response = $this->postJson('/api/results', [
            'test_id' => $test->id,
            'user_name' => 'Tester',
            'time_spent' => 30,
            'question_ids' => [$questions[0]->id, $foreignQuestions[0]->id],
            'user_answers' => [
                0 => 0,
                1 => 0,
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['question_ids']);
        $this->assertDatabaseCount('test_results', 0);
    }

    private function createUser(): User
    {
        return User::query()->create([
            'name' => 'Test User',
            'login' => 'tester_' . uniqid(),
            'password' => 'password',
            'role' => 'user',
        ]);
    }

    private function createTestWithQuestions(array $questionDefinitions): array
    {
        $treeOwner = $this->createUser();
        $tree = Tree::query()->create([
            'name' => 'Tree ' . uniqid(),
            'slug' => 'tree-' . uniqid(),
            'tree_id' => null,
            'user_id' => $treeOwner->id,
            'type' => 'folder',
            'position' => 0,
        ]);

        $test = Test::query()->create([
            'title' => 'Test ' . uniqid(),
            'tree_id' => $tree->id,
            'description' => 'Test description',
            'timeLimit' => 30,
            'settings' => [
                'shuffleQuestions' => false,
                'shuffleAnswers' => false,
                'randomQuestionCount' => 0,
            ],
            'grading' => [
                ['minScore' => 0, 'max_score' => 59, 'grade' => 'Неудовлетворительно'],
                ['minScore' => 60, 'max_score' => 100, 'grade' => 'Отлично'],
            ],
        ]);

        $questions = collect($questionDefinitions)->values()->map(
            fn(array $question, int $index) => Question::query()->create([
                'test_id' => $test->id,
                'text' => $question['text'],
                'type' => $question['type'],
                'points' => $question['points'],
                'image' => null,
                'options' => $question['options'],
                'order' => $index,
            ])
        );

        return [$test->fresh('questions'), $questions];
    }
}
