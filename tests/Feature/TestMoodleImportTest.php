<?php

namespace Tests\Feature;

use App\Models\Test;
use App\Models\Tree;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TestMoodleImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_accepts_converted_moodle_question_types(): void
    {
        $user = User::query()->create([
            'name' => 'Moodle Import User',
            'login' => 'moodle_import_' . uniqid(),
            'password' => 'password',
            'role' => 'user',
        ]);
        Sanctum::actingAs($user);

        $tree = Tree::query()->create([
            'name' => 'Moodle Import Folder',
            'slug' => 'moodle-import-' . uniqid(),
            'tree_id' => null,
            'user_id' => $user->id,
            'type' => 'folder',
            'position' => 0,
        ]);

        $questions = [
            [
                'id' => 1,
                'type' => 'single',
                'text' => 'На рисунке представлены оси ______ проекции.',
                'points' => 1,
                'options' => [
                    [
                        'id' => 1,
                        'text' => 'прямоугольной изометрической',
                        'correct' => true,
                        'image' => '',
                        'points' => 1,
                    ],
                    [
                        'id' => 2,
                        'text' => 'фронтальной изометрической',
                        'correct' => false,
                        'image' => '',
                        'points' => 0,
                    ],
                ],
                'settings' => ['shuffleAnswers' => false],
            ],
            [
                'id' => 2,
                'type' => 'matching',
                'text' => 'Сопоставьте фигуры',
                'points' => 1,
                'options' => [
                    ['left' => '1', 'right' => 'Цилиндр', 'leftImage' => ''],
                    ['left' => '2', 'right' => 'Призма', 'leftImage' => ''],
                ],
            ],
            [
                'id' => 3,
                'type' => 'truefalse',
                'text' => 'При выполнении эскизов для измерения радиусов применяют монеты',
                'points' => 1,
                'options' => 'true',
            ],
            [
                'id' => 4,
                'type' => 'text',
                'text' => 'Деталь резьбового соединения называется',
                'points' => 1,
                'correctAnswer' => '["шайба"]',
                'options' => ['шайба'],
            ],
        ];

        $response = $this->postJson('/api/tests/import', [
            'tree_id' => $tree->id,
            'format' => 'xml',
            'xml_data' => json_encode([
                'title' => 'Moodle Import',
                'description' => 'questions.xml',
                'timeLimit' => 30,
                'questions' => $questions,
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $response->assertOk();

        $test = Test::query()->where('tree_id', $tree->id)->firstOrFail();
        $this->assertSame('Moodle Import (импорт)', $test->title);
        $this->assertCount(4, $test->questions);

        $types = $test->questions->pluck('type')->all();
        $this->assertSame(['single', 'matching', 'truefalse', 'text'], $types);

        $this->assertTrue($test->questions[0]->options[0]['correct']);
        $this->assertSame('Цилиндр', $test->questions[1]->options[0]['right']);
        $this->assertSame('true', $test->questions[2]->options);
        $this->assertSame(['шайба'], $test->questions[3]->options);
    }
}
