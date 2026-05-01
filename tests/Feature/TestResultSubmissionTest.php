<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestResult;
use App\Models\Tree;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TestResultSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_test_tree_endpoint_hides_correct_answers_for_attempt_participants(): void
    {
        $participant = $this->createUser();
        Sanctum::actingAs($participant);

        [$test, $questions, $tree] = $this->createTestWithQuestions([
            [
                'text' => 'Single',
                'type' => 'single',
                'points' => 1,
                'options' => [
                    ['text' => 'A', 'correct' => true],
                    ['text' => 'B', 'correct' => false],
                ],
            ],
            [
                'text' => 'Sorting',
                'type' => 'sorting',
                'points' => 1,
                'options' => [
                    ['text' => 'Second', 'correctPosition' => 1],
                    ['text' => 'First', 'correctPosition' => 0],
                ],
            ],
            [
                'text' => 'Text',
                'type' => 'text',
                'points' => 1,
                'options' => ['secret'],
            ],
        ]);

        $response = $this->getJson("/api/tests/{$tree->id}/get");

        $response->assertOk();
        $response->assertJsonMissing(['correct' => true]);
        $response->assertJsonMissing(['correctPosition' => 1]);
        $response->assertJsonMissing(['secret']);
        $response->assertJsonPath('data.0.questions.0.options.0.text', 'A');
        $response->assertJsonPath('data.0.questions.1.options.0.text', 'Second');
        $response->assertJsonPath('data.0.questions.2.options', null);
    }

    public function test_test_tree_endpoint_keeps_correct_answers_for_tree_owner(): void
    {
        [$test, $questions, $tree] = $this->createTestWithQuestions([
            [
                'text' => 'Single',
                'type' => 'single',
                'points' => 1,
                'options' => [
                    ['text' => 'A', 'correct' => true],
                    ['text' => 'B', 'correct' => false],
                ],
            ],
        ]);

        Sanctum::actingAs($tree->user);

        $response = $this->getJson("/api/tests/{$tree->id}/get");

        $response->assertOk();
        $response->assertJsonPath('data.0.questions.0.options.0.correct', true);
    }

    public function test_participants_cannot_export_full_test_with_answers(): void
    {
        $participant = $this->createUser();
        Sanctum::actingAs($participant);

        [$test] = $this->createTestWithQuestions([
            [
                'text' => 'Single',
                'type' => 'single',
                'points' => 1,
                'options' => [
                    ['text' => 'A', 'correct' => true],
                    ['text' => 'B', 'correct' => false],
                ],
            ],
        ]);

        $this->getJson("/api/tests/{$test->id}/export")->assertForbidden();
    }

    public function test_server_attempt_creates_selected_sanitized_question_set(): void
    {
        $participant = $this->createUser();
        Sanctum::actingAs($participant);

        [$test] = $this->createTestWithQuestions([
            [
                'text' => 'Q1',
                'type' => 'single',
                'points' => 1,
                'options' => [
                    ['text' => 'A', 'correct' => true],
                    ['text' => 'B', 'correct' => false],
                ],
            ],
            [
                'text' => 'Q2',
                'type' => 'single',
                'points' => 1,
                'options' => [
                    ['text' => 'A', 'correct' => false],
                    ['text' => 'B', 'correct' => true],
                ],
            ],
            [
                'text' => 'Q3',
                'type' => 'single',
                'points' => 1,
                'options' => [
                    ['text' => 'A', 'correct' => true],
                    ['text' => 'B', 'correct' => false],
                ],
            ],
        ], ['randomQuestionCount' => 2]);

        $response = $this->postJson("/api/tests/{$test->id}/attempts", [
            'user_name' => 'Tester',
        ]);

        $response->assertCreated();
        $response->assertJsonMissing(['correct' => true]);
        $response->assertJsonCount(2, 'data.test.questions');

        $attempt = TestAttempt::query()->sole();
        $this->assertSame($participant->id, $attempt->user_id);
        $this->assertSame($test->id, $attempt->test_id);
        $this->assertCount(2, $attempt->question_ids);
    }

    public function test_result_submission_uses_server_attempt_question_ids(): void
    {
        $participant = $this->createUser();
        Sanctum::actingAs($participant);

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
                'type' => 'single',
                'points' => 5,
                'options' => [
                    ['text' => 'A', 'correct' => true],
                    ['text' => 'B', 'correct' => false],
                ],
            ],
        ]);

        $attempt = TestAttempt::create([
            'user_id' => $participant->id,
            'test_id' => $test->id,
            'user_name' => 'Tester',
            'question_ids' => [$questions[1]->id, $questions[2]->id],
            'started_at' => now(),
        ]);

        $response = $this->postJson('/api/results', [
            'test_id' => $test->id,
            'attempt_id' => $attempt->id,
            'user_name' => 'Tester',
            'time_spent' => 90,
            'question_ids' => [$questions[0]->id],
            'user_answers' => [
                0 => 1,
                1 => 0,
            ],
        ]);

        $response->assertOk();

        $result = TestResult::query()->sole();
        $this->assertSame('8.00', number_format((float) $result->max_score, 2, '.', ''));
        $this->assertSame('8.00', number_format((float) $result->total_score, 2, '.', ''));
        $this->assertSame($attempt->id, $result->test_attempt_id);
        $this->assertSame($questions[1]->id, $result->question_results[0]['question_id']);
        $this->assertSame($questions[2]->id, $result->question_results[1]['question_id']);
        $this->assertNotNull($attempt->fresh()->completed_at);
    }

    public function test_completed_attempt_cannot_be_submitted_again(): void
    {
        $participant = $this->createUser();
        Sanctum::actingAs($participant);

        [$test, $questions] = $this->createTestWithQuestions([
            [
                'text' => 'Q1',
                'type' => 'single',
                'points' => 1,
                'options' => [
                    ['text' => 'A', 'correct' => true],
                    ['text' => 'B', 'correct' => false],
                ],
            ],
        ]);

        $attempt = TestAttempt::create([
            'user_id' => $participant->id,
            'test_id' => $test->id,
            'user_name' => 'Tester',
            'question_ids' => [$questions[0]->id],
            'started_at' => now(),
        ]);

        $payload = [
            'test_id' => $test->id,
            'attempt_id' => $attempt->id,
            'user_name' => 'Tester',
            'time_spent' => 30,
            'user_answers' => [
                0 => 0,
            ],
        ];

        $this->postJson('/api/results', $payload)->assertOk();
        $this->postJson('/api/results', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['attempt_id']);
        $this->assertDatabaseCount('test_results', 1);
    }

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
        ], ['randomQuestionCount' => 2]);

        $attempt = TestAttempt::create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'user_name' => 'Tester',
            'question_ids' => [$questions[1]->id, $questions[2]->id],
            'started_at' => now(),
        ]);

        $response = $this->postJson('/api/results', [
            'test_id' => $test->id,
            'attempt_id' => $attempt->id,
            'user_name' => 'Tester',
            'time_spent' => 120,
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

    public function test_random_question_tests_require_server_attempt(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        [$test, $questions] = $this->createTestWithQuestions([
            [
                'text' => 'Q1',
                'type' => 'single',
                'points' => 1,
                'options' => [
                    ['text' => 'A', 'correct' => true],
                    ['text' => 'B', 'correct' => false],
                ],
            ],
            [
                'text' => 'Q2',
                'type' => 'single',
                'points' => 1,
                'options' => [
                    ['text' => 'A', 'correct' => true],
                    ['text' => 'B', 'correct' => false],
                ],
            ],
            [
                'text' => 'Q3',
                'type' => 'single',
                'points' => 1,
                'options' => [
                    ['text' => 'A', 'correct' => true],
                    ['text' => 'B', 'correct' => false],
                ],
            ],
        ], ['randomQuestionCount' => 2]);

        $response = $this->postJson('/api/results', [
            'test_id' => $test->id,
            'user_name' => 'Tester',
            'time_spent' => 30,
            'question_ids' => [$questions[0]->id],
            'user_answers' => [
                0 => 0,
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attempt_id']);
        $this->assertDatabaseCount('test_results', 0);
    }

    public function test_result_submission_rejects_retake_when_retake_is_disabled(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        [$test, $questions] = $this->createTestWithQuestions([
            [
                'text' => 'Q1',
                'type' => 'single',
                'points' => 1,
                'options' => [
                    ['text' => 'A', 'correct' => true],
                    ['text' => 'B', 'correct' => false],
                ],
            ],
        ], ['allowRetake' => false]);

        $payload = [
            'test_id' => $test->id,
            'user_name' => 'Tester',
            'time_spent' => 30,
            'question_ids' => [$questions[0]->id],
            'user_answers' => [
                0 => 0,
            ],
        ];

        $this->postJson('/api/results', $payload)->assertOk();
        $this->postJson('/api/results', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['test_id']);
        $this->assertDatabaseCount('test_results', 1);
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

    private function createTestWithQuestions(array $questionDefinitions, array $settings = []): array
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
        $tree->setRelation('user', $treeOwner);

        $test = Test::query()->create([
            'title' => 'Test ' . uniqid(),
            'tree_id' => $tree->id,
            'description' => 'Test description',
            'timeLimit' => 30,
            'settings' => [
                'shuffleQuestions' => false,
                'shuffleAnswers' => false,
                'randomQuestionCount' => 0,
                'allowRetake' => true,
                ...$settings,
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

        return [$test->fresh('questions'), $questions, $tree];
    }
}
