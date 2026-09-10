<?php

namespace Tests\Feature;

use App\Models\Tree;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResourceImportTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role = 'ceo'): User
    {
        return User::query()->create([
            'name' => 'Import User',
            'login' => 'importer_' . uniqid(),
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function createFolder(User $user, ?int $parentId = null): Tree
    {
        return Tree::query()->create([
            'name' => 'Folder ' . uniqid(),
            'slug' => 'folder-' . uniqid(),
            'tree_id' => $parentId,
            'user_id' => $user->id,
            'type' => 'folder',
            'position' => 0,
        ]);
    }

    /**
     * Собирает ZIP архив в формате экспорта системы:
     * resource.json + files/*
     */
    private function createImportZip(array $resourceData): string
    {
        $tempDir = storage_path('app/temp/test_import_' . uniqid());
        File::makeDirectory($tempDir, 0755, true);

        $exportData = [
            'version' => '2.0',
            'exported_at' => now()->toISOString(),
            'resource' => $resourceData,
            'files' => [],
            'has_tests' => false,
            'tests_count' => 0,
        ];

        File::put(
            $tempDir . '/resource.json',
            json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $zipPath = $tempDir . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach (File::allFiles($tempDir) as $file) {
            $zip->addFile(
                $file->getPathname(),
                'resource.json'
            );
        }
        $zip->close();

        File::deleteDirectory($tempDir);

        return $zipPath;
    }

    public function test_import_creates_resource_inside_parent_folder(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $parentFolder = $this->createFolder($user);

        $zipPath = $this->createImportZip([
            'name' => 'Импортированный документ',
            'data' => [
                ['type' => 'paragraph', 'data' => ['text' => 'Привет, мир']],
            ],
        ]);

        $response = $this->postJson('/api/resource/import', [
            'file' => new \Illuminate\Http\UploadedFile(
                $zipPath,
                'resource.zip',
                'application/zip',
                null,
                true
            ),
            'parent_id' => $parentFolder->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $treeId = $response->json('tree_id');

        $this->assertNotNull($treeId, 'tree_id должен быть в ответе');

        $created = Tree::find($treeId);

        $this->assertNotNull($created, 'Созданный документ должен существовать');
        $this->assertSame($parentFolder->id, (int) $created->tree_id, 'Документ должен быть создан в родительской папке');
        $this->assertSame('file', $created->type);
        $this->assertSame('Импортированный документ', $created->name);
    }

    public function test_import_creates_resource_in_root_when_no_parent(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $zipPath = $this->createImportZip([
            'name' => 'Корневой документ',
            'data' => [
                ['type' => 'paragraph', 'data' => ['text' => 'Текст']],
            ],
        ]);

        $response = $this->postJson('/api/resource/import', [
            'file' => new \Illuminate\Http\UploadedFile(
                $zipPath,
                'resource.zip',
                'application/zip',
                null,
                true
            ),
        ]);

        $response->assertOk();

        $created = Tree::find($response->json('tree_id'));

        $this->assertNotNull($created);
        $this->assertNull($created->tree_id, 'Без parent_id документ создаётся в корне');
    }

    public function test_import_forbidden_for_foreign_parent_folder(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $otherUser = $this->createUser();
        $foreignFolder = $this->createFolder($otherUser);

        $zipPath = $this->createImportZip([
            'name' => 'Документ',
            'data' => [
                ['type' => 'paragraph', 'data' => ['text' => 'Текст']],
            ],
        ]);

        $response = $this->postJson('/api/resource/import', [
            'file' => new \Illuminate\Http\UploadedFile(
                $zipPath,
                'resource.zip',
                'application/zip',
                null,
                true
            ),
            'parent_id' => $foreignFolder->id,
        ]);

        $response->assertForbidden();
    }
}
