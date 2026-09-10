<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResourceSaveRequest;
use App\Http\Requests\UploadFileRequest;
use App\Models\About;
use App\Models\Available;
use App\Models\Content;
use App\Models\Group;
use App\Models\Tree;
use App\Models\User;
use App\Models\UserGroups;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use App\Models\Test;
use App\Models\Question;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Illuminate\Support\Str;

class ContentController extends Controller
{

    public function saveImage(Request $request)
    {
        try {
            if (!$request->file('image')) {
                return response()->json(['success' => 0, 'message' => 'Файл не найден'], 400);
            }

            $userId = Auth::user()->id;
            $dirshort = "contentImages/{$userId}";

            // Получаем файл
            $file = $request->file('image');
            $mimeType = $file->getMimeType();

            // Определяем расширение
            if ($mimeType === 'image/png') {
                $extension = 'png';
            } elseif ($mimeType === 'image/jpeg') {
                $extension = 'jpg';
            } elseif ($mimeType === 'image/gif') {
                $extension = 'gif';
            } else {
                $extension = $file->getClientOriginalExtension() ?: 'bin';
            }

            $name = Str::random(40) . "." . $extension;
            $fullPath = "{$dirshort}/{$name}";

            Storage::disk('public')->makeDirectory($dirshort);
            $file->storeAs($dirshort, $name, 'public');


            // Возвращаем URL, доступный из браузера
            $url = asset("storage/{$fullPath}");
            return response()->json(['success' => 1, 'file' => ['url' => $url]], 200);
        } catch (\Exception $e) {
            \Log::error('Ошибка сохранения изображения: ' . $e->getMessage());
            return response()->json(['success' => 0, 'message' => $e->getMessage()], 500);
        }
    }
    public function saveFile(UploadFileRequest $request)
    {
        $dirshort = "contentFiles/" . Auth::user()->id;
        Storage::disk('public')->makeDirectory($dirshort);

        $uploaded = $request->file('file');
        $originalName = $uploaded->getClientOriginalName();
        $size = $uploaded->getSize();
        $extension = strtolower($uploaded->getClientOriginalExtension());
        $name = Str::random(40) . "." . $uploaded->extension();
        $pathshort = $dirshort . '/' . $name;
        Storage::disk("public")->putFileAs($dirshort, $uploaded, $name);
        $withoutExt = $originalName !== ''
            ? pathinfo($originalName, PATHINFO_FILENAME)
            : pathinfo($name, PATHINFO_FILENAME);
        $displayName = $withoutExt !== '' ? $withoutExt : $originalName;

        return response()->json([
            'success' => 1,
            'file' => [
                'url' => asset("storage/{$pathshort}"),
                'name' => $displayName,
                'title' => $displayName,
                'size' => $size,
                'extension' => $extension,
            ],
        ], 200);
    }

    public function saveImageByUrl(Request $request)
    {
        $dirshort = "contentImages/" . Auth::user()->id;
        Storage::disk('public')->makeDirectory($dirshort);

        $path   = Image::read(file_get_contents($request->url));
        $resize = $path->scaleDown(1024, 1024)->toWebp(100);

        $name = Str::random(40) . ".webp";
        $pathshort = $dirshort . '/' . $name;

        Storage::disk("public")->put($pathshort, $resize->toString());
        return response()->json(['success' => 1, 'file' => ['url' => asset("storage/{$pathshort}")]], 200);
    }

    public function saveResource(ResourceSaveRequest $request)
    {
        $fileId = null;
        if (isset($request->tree_id)) {
            $tr = Tree::find($request->tree_id);
            if (!$tr) {
                return response()->json([
                    'success' => false,
                    'message' => 'Родительская папка не найдена',
                ], 404);
            }

            if (!$this->canManageTree($request->user(), $tr)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Доступ запрещен',
                ], 403);
            }

            $position = Tree::where("tree_id", $tr->id)->count();

            $tree = Tree::create([
                'name' => $request->name,
                'slug' => $this->generateUniqueTreeSlug($request->name),
                'tree_id' => $request->tree_id,
                'user_id' => $tr->user_id,
                'position' => $position,
            ]);
            $fileId = Content::create([
                'tree_id' => $tree->id,
                'accessibility' => $request->accessibility,
                'accessibilitymanagers' => $request->accessibilitymanagers,
                'accessibilitylink' => $request->boolean('accessibilitylink'),
                'data' => $request->data,
            ]);

            $this->changeAvailables($tree->id, $request->availables, $request->accessibility);

            return response()->json([
                'success' => true,
                'message' => 'Файл успешно создан',
                'id' => $fileId->tree_id,
                'slug' => $tree->slug,
            ]);
        } else {
            $tree = Tree::find($request->id);
            $fileId = Content::where("tree_id", $request->id)->first();
            if (!$tree || !$fileId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Файл не найден',
                ], 404);
            }

            if (!$this->canManageTree($request->user(), $tree)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Доступ запрещен',
                ], 403);
            }

            if ($tree->type === 'file') {
                $tree->slug = $this->generateUniqueTreeSlug($request->name, $tree->id);
            }

            $fileId->update([
                'accessibility' => $request->accessibility,
                'accessibilitymanagers' => $request->accessibilitymanagers,
                'accessibilitylink' => $request->boolean('accessibilitylink'),
                'data' => $request->data,
            ]);
            $tree->update([
                'name' => $request->name,
                'slug' => $tree->slug,
            ]);

            $this->changeAvailables($tree->id, $request->availables, $request->accessibility);

            return response()->json([
                'success' => true,
                'message' => 'Данные файла обновлены',
                'id' => $fileId->tree_id,
                'slug' => $tree->slug,
            ]);
        }
    }

    public function getResourceBySlug($slug)
    {
        $tree = Tree::withTrashed()
            ->where('slug', $slug)
            ->where('type', 'file')
            ->first();

        if (!$tree) {
            return response()->json(['success' => false, "message" => 'Файла не существует']);
        }

        return $this->getResource($tree->id);
    }

    public function changeAvailables($tree, $availables, $accessibility)
    {
        Available::where('tree_id', $tree)->delete();
        if (!$accessibility) {
            $decodedAvailables = json_decode($availables);
            if (!is_array($decodedAvailables)) {
                return;
            }

            foreach ($decodedAvailables as $available) {
                if ($available->checked) {
                    Available::create(
                        [
                            'group_id' => $available->id,
                            'tree_id' => $tree,
                        ]
                    );
                }
            }
        }
    }

    public function getResourceForEdit(Request $request, $content)
    {
        $tree = Tree::withTrashed()->find($content);
        if (!$tree || $tree->type !== 'file') {
            return response()->json(['success' => false, "message" => 'Файла не существует']);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, "message" => 'Доступ запрещен']);
        }

        if (!$this->canManageTree($user, $tree)) {
            return response()->json(['success' => false, "message" => 'Доступ запрещен']);
        }

        return $this->getResource($content);
    }

    public function getResource($content)
    {
        $tree = Tree::withTrashed()->find($content);
        if (!$tree) {
            return response()->json(['success' => false, "message" => 'Файла не существует']);
        }
        $res = Content::with("tree")->where("tree_id", $content)->first();
        if (!$res) {
            return response()->json(['success' => false, "message" => 'Файла не существует']);
        }

        // На публичных роутингах без middleware auth:sanctum Auth::user() может быть null,
        // даже если передан Bearer-токен. Берем пользователя из sanctum-guard напрямую.
        $user = auth('sanctum')->user() ?? Auth::user();
        $isPublic = (bool) $res->accessibility;
        $isLinkOnly = (bool) $res->accessibilitylink;

        if (!$user) {
            // Гость может открыть только публичный файл без режима "только по ссылке для авторизованных".
            if (!$isPublic || $isLinkOnly) {
                return response()->json(['success' => false, "message" => 'Доступ к файлу запрещен']);
            }
        } else {
            $isOwner = (int) $user->id === (int) $tree->user_id;
            $isAdmin = $user->role === 'admin';

            // Владелец и администратор всегда имеют доступ.
            if (!$isOwner && !$isAdmin) {
                // Для авторизованных пользователей режим "по ссылке" разрешает просмотр.
                if (!$isLinkOnly && !$isPublic) {
                    $gr = UserGroups::where("user_id", $user->id)->first();
                    $avia = null;
                    if ($gr) {
                        $avia = Available::where("group_id", $gr->group_id)->where("tree_id", $content)->first();
                    }
                    if (!$avia) {
                        return response()->json(['success' => false, "message" => 'Доступ к файлу запрещен']);
                    }
                }
            }
        }

        $availablesGroups = [];

        $all = Group::get()->sortBy("name");
        $g = Available::where('tree_id', $content)->pluck('group_id')->toArray();

        foreach ($all as $value) {
            $availablesGroups[] = ['id' => $value->id, 'name' => $value->name, 'checked' => in_array($value->id, $g)];
        }

        return response()->json(["name" => $tree->name, 'content' => $res,  'groups' => $availablesGroups]);
    }

    public function delResource(Request $request, $content)
    {
        $tree = Tree::withTrashed()->find($content);
        if (!$tree) {
            return response()->json([
                "success" => false,
                'message' => 'Файл не найден',
            ], 404);
        }

        if (!$this->canManageTree($request->user(), $tree)) {
            return response()->json([
                "success" => false,
                'message' => 'Недостаточно прав',
            ], 403);
        }

        $mess = '';
        if ($tree->trashed()) {
            $tree->restore();
            $mess = "Успешно восстановлен";
        } else {
            $tree->delete();
            $mess = "Успешно удалён";
        }
        return response()->json(["success" => true, 'message' => $mess]);
    }

    public function checkImageResource()
    {
        User::onlyTrashed()->forceDelete();
        Tree::onlyTrashed()->forceDelete();
        Content::onlyTrashed()->forceDelete();

        $stats = [
            'content_updated' => 0,
            'broken_refs_removed' => 0,
            'files_deleted' => 0,
        ];

        $physicalFiles = $this->collectManagedFiles();
        $physicalRelSet = [];
        foreach ($physicalFiles as $item) {
            $physicalRelSet[$item['rel']] = true;
        }

        $usedRelSet = [];

        $contents = Content::query()->get(['id', 'data']);
        foreach ($contents as $content) {
            $blocks = json_decode($content->data, true);
            if (!is_array($blocks)) {
                continue;
            }

            $changed = false;
            $cleanedBlocks = [];

            foreach ($blocks as $block) {
                $cleanedBlock = $this->sanitizeBlockReferences($block, $physicalRelSet, $usedRelSet, $stats);
                if ($cleanedBlock === null) {
                    $changed = true;
                    continue;
                }

                if ($cleanedBlock !== $block) {
                    $changed = true;
                }

                $cleanedBlocks[] = $cleanedBlock;
            }

            if ($changed) {
                $content->update(['data' => json_encode($cleanedBlocks, JSON_UNESCAPED_UNICODE)]);
                $stats['content_updated']++;
            }
        }

        $about = About::find(1);
        if ($about && !empty($about->logo)) {
            $normalizedLogo = $this->normalizeManagedPath($about->logo);
            if ($normalizedLogo) {
                if (isset($physicalRelSet[$normalizedLogo])) {
                    $usedRelSet[$normalizedLogo] = true;
                } else {
                    $about->logo = null;
                    $about->save();
                    $stats['broken_refs_removed']++;
                }
            }
        }

        foreach ($physicalFiles as $file) {
            if (!isset($usedRelSet[$file['rel']])) {
                if (@unlink($file['abs'])) {
                    $stats['files_deleted']++;
                }
            }
        }

        Artisan::call('route:clear');
        Artisan::call('cache:clear');

        return response()->json([
            'success' => true,
            'message' => sprintf(
                'Кэш очищен. Обновлено файлов: %d, удалено битых ссылок: %d, удалено лишних файлов: %d',
                $stats['content_updated'],
                $stats['broken_refs_removed'],
                $stats['files_deleted']
            ),
            'stats' => $stats,
        ]);
    }

    private function collectManagedFiles(): array
    {
        $result = [];

        $roots = [
            ['dir' => public_path('contentImages'), 'prefix' => 'contentImages'],
            ['dir' => public_path('contentFiles'), 'prefix' => 'contentFiles'],
            ['dir' => public_path('logo'), 'prefix' => 'logo'],
            ['dir' => storage_path('app/public/contentImages'), 'prefix' => 'contentImages'],
            ['dir' => storage_path('app/public/contentFiles'), 'prefix' => 'contentFiles'],
            ['dir' => storage_path('app/public/logo'), 'prefix' => 'logo'],
        ];

        foreach ($roots as $root) {
            $dir = $root['dir'];
            $prefix = $root['prefix'];

            if (!is_dir($dir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile()) {
                    continue;
                }

                $relativeInside = str_replace('\\', '/', $iterator->getSubPathname());
                $relativePath = $prefix . '/' . ltrim($relativeInside, '/');

                $result[] = [
                    'abs' => $fileInfo->getPathname(),
                    'rel' => $relativePath,
                ];
            }
        }

        return $result;
    }

    private function sanitizeBlockReferences(array $block, array $physicalRelSet, array &$usedRelSet, array &$stats): ?array
    {
        if (!isset($block['type']) || !isset($block['data']) || !is_array($block['data'])) {
            return $block;
        }

        $type = (string)$block['type'];
        $data = $block['data'];

        if ($type === 'image') {
            $url = $data['file']['url'] ?? ($data['url'] ?? null);
            if ($url) {
                $normalized = $this->normalizeManagedPath((string)$url);
                if ($normalized) {
                    if (!isset($physicalRelSet[$normalized])) {
                        $stats['broken_refs_removed']++;
                        return null;
                    }
                    $usedRelSet[$normalized] = true;
                }
            }
            return $block;
        }

        if ($type === 'gallery') {
            $files = $data['files'] ?? [];
            if (!is_array($files)) {
                return $block;
            }

            $cleanedFiles = [];
            foreach ($files as $fileItem) {
                if (!is_array($fileItem) || !isset($fileItem['url'])) {
                    continue;
                }
                $normalized = $this->normalizeManagedPath((string)$fileItem['url']);
                if ($normalized && !isset($physicalRelSet[$normalized])) {
                    $stats['broken_refs_removed']++;
                    continue;
                }
                if ($normalized) {
                    $usedRelSet[$normalized] = true;
                }
                $cleanedFiles[] = $fileItem;
            }

            if (count($cleanedFiles) === 0) {
                $stats['broken_refs_removed']++;
                return null;
            }

            if ($cleanedFiles !== $files) {
                $block['data']['files'] = $cleanedFiles;
            }

            return $block;
        }

        if ($type === 'attaches') {
            $url = $data['file']['url'] ?? null;
            if ($url) {
                $normalized = $this->normalizeManagedPath((string)$url);
                if ($normalized) {
                    if (!isset($physicalRelSet[$normalized])) {
                        $stats['broken_refs_removed']++;
                        return null;
                    }
                    $usedRelSet[$normalized] = true;
                }
            }
            return $block;
        }

        return $block;
    }

    private function normalizeManagedPath(string $path): ?string
    {
        $rawPath = trim($path);
        if ($rawPath === '') {
            return null;
        }

        $parsed = parse_url($rawPath, PHP_URL_PATH);
        $normalized = str_replace('\\', '/', $parsed ?: $rawPath);
        $normalized = preg_replace('/\/+/', '/', $normalized);
        $normalized = ltrim($normalized, '/');

        if (str_starts_with($normalized, 'storage/')) {
            $normalized = substr($normalized, strlen('storage/'));
        }

        foreach (['contentImages/', 'contentFiles/', 'logo/'] as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return $normalized;
            }
        }

        return null;
    }

    private function generateUniqueTreeSlug(string $name, ?int $ignoreTreeId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'document';
        }

        $slug = $base;
        $suffix = 2;

        while (
            Tree::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreTreeId, fn($q) => $q->where('id', '!=', $ignoreTreeId))
            ->exists()
        ) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'search' => 'required|string|min:2',
            'allId' => 'nullable',
        ]);

        $allIds = json_decode($validated['allId'] ?? '[]', true);
        if (!is_array($allIds)) {
            $allIds = [];
        }

        $allIds = array_values(
            array_filter($allIds, fn($id) => is_numeric($id))
        );

        $searchTerm = trim($validated['search']);

        $tree = Tree::whereIn('id', $allIds)
            ->where('name', 'like', '%' . $searchTerm . '%')
            ->get();
        $content = Content::whereIn('tree_id', $allIds)->with('tree')->get();


        function isFound($jsonArray, $searchTerm)
        {

            $data = is_string($jsonArray) ? json_decode($jsonArray, true) : $jsonArray;

            if ($data === null) {
                return false;
            }

            $searchLower = mb_strtolower($searchTerm, 'UTF-8');


            $searchInArray = function ($array) use (&$searchInArray, $searchLower) {
                foreach ($array as $value) {
                    if (is_string($value) || is_numeric($value)) {

                        $stringValue = mb_strtolower((string)$value, 'UTF-8');
                        if (mb_strpos($stringValue, $searchLower) !== false) {
                            return true;
                        }
                    } elseif (is_array($value)) {
                        if ($searchInArray($value)) {
                            return true;
                        }
                    }
                }
                return false;
            };

            return $searchInArray($data);
        }


        $results = [];
        foreach ($content as $cont) {

            isFound(json_decode($cont->data, true), $searchTerm) ? $results[] = $cont : '';
        }


        return ['tree' => $tree, 'content' => $results];
    }

    private function canManageTree(?User $user, Tree $tree): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        return (int) $user->id === (int) $tree->user_id;
    }


    public function getFiles(Request $request)
    {
        $userRole = Auth::user()->role;
        $files = [];
        $users = [];
        $allowedSortColumns = ['name', 'created_at', 'updated_at', 'id'];
        $sortBy = in_array($request->sortBy, $allowedSortColumns, true)
            ? $request->sortBy
            : 'name';
        $sortDirection = $request->sortAsc == 'true' ? 'asc' : 'desc';

        if ($userRole == 'ceo') {
            $files = Tree::where('user_id', Auth::user()->id)->where('type', 'file')->with(['child', 'parent', 'available'])->withTrashed();
        } else {
            $files = Tree::where('type', 'file')->with(['child', 'user', 'parent', 'available'])->withTrashed();
            $users = User::where('role', '!=', 'user')->get(['id', 'name']);
        }

        $group = Group::get()->sortBy("name");

        if (isset($request->search)) {
            $files->where(DB::raw('lower(name)'), 'like', '%' . strtolower($request->search) . '%');
        }

        if (isset($request->user)) {
            $files->where('user_id', $request->user);
        }

        $files->orderBy($sortBy, $sortDirection);
        $files = $files->paginate(15);

        foreach ($files as $file) {
            if ($file->child) {
                $file->child->accessibility = $file->child->accessibility == 1;
                $file->child->accessibilitymanagers = $file->child->accessibilitymanagers == 1;
            }
            $a = $file->available->pluck("group_id")->toArray();
            $res = [];
            foreach ($group->toArray() as $g) {
                $res[] = array_merge($g, ["checked" => in_array($g['id'], $a) ? true : false]);
            }
            $file->groups = $res;
        }

        return response()->json(['success' => true, 'data' => ['files' => $files, 'users' => $users]], 200);
    }
    public function changeFolder(Tree $id, Request $request)
    {
        $id->update(["tree_id" => $request->tree_id]);
        return response()->json(['success' => true, 'message' => 'Успешно перемещено!'], 200);
    }

    public function exportResource(Request $request, $id)
    {
        // $id — tree_id (как и во всех остальных роутах)
        $content = Content::with('tree')->where('tree_id', $id)->first();

        if (!$content) {
            return response()->json(['success' => false, 'message' => 'Ресурс не найден'], 404);
        }

        // Проверка прав доступа
        $user = Auth::user();
        if ($user->role !== 'admin' && $content->tree->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещен'], 403);
        }

        $includeTests = filter_var($request->input('include_tests', false), FILTER_VALIDATE_BOOLEAN);

        // Отключаем буферизацию
        if (ob_get_level()) {
            ob_end_clean();
        }

        try {
            $dataBlocks = json_decode($content->data, true);
            $files = $this->collectFilesFromBlocks($dataBlocks);

            $tempDir = storage_path('app/temp/export_' . uniqid());
            File::makeDirectory($tempDir, 0755, true);

            $filePaths = [];
            foreach ($files as $key => $fileData) {
                $filePath = $this->copyFileToTemp($fileData, $tempDir);
                if ($filePath) {
                    $filePaths[$key] = $filePath;
                }
            }

            $updatedData = $this->updatePathsInBlocks($dataBlocks, $filePaths);

            $exportData = [
                'version' => '2.0',
                'exported_at' => now()->toISOString(),
                'resource' => [
                    'name' => $content->tree->name,
                    'data' => $updatedData,
                ],
                'files' => $filePaths,
            ];

            if ($includeTests) {
                $tests = Test::where('tree_id', $content->tree_id)->get();
                $exportData['tests'] = $tests->map(function ($test) {
                    return [
                        'id' => $test->id,
                        'title' => $test->title,
                        'description' => $test->description,
                        'timeLimit' => $test->timeLimit,
                        'settings' => $test->settings,
                        'grading' => $test->grading,
                        'questions' => $this->exportQuestions($test->id),
                    ];
                });
                $exportData['has_tests'] = true;
                $exportData['tests_count'] = $tests->count();
            } else {
                $exportData['has_tests'] = false;
                $exportData['tests_count'] = 0;
            }

            // Сохраняем JSON
            $jsonPath = $tempDir . '/resource.json';
            File::put($jsonPath, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Создаем ZIP
            $zipFileName = 'resource_' . $content->tree->slug . '_' . date('Y-m-d') . '.zip';
            $zipPath = storage_path('app/temp/' . $zipFileName);

            if (File::exists($zipPath)) {
                File::delete($zipPath);
            }

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Не удалось создать ZIP архив');
            }

            $this->addDirectoryToZip($zip, $tempDir, '');
            $zip->close();

            if (!File::exists($zipPath) || File::size($zipPath) === 0) {
                throw new \Exception('ZIP архив не создан или пуст');
            }

            File::deleteDirectory($tempDir);

            return response()
                ->download($zipPath, $zipFileName, [
                    'Content-Type' => 'application/zip',
                    'Content-Disposition' => 'attachment; filename="' . $zipFileName . '"',
                    'Cache-Control' => 'private, no-cache, must-revalidate',
                ])
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Export error: ' . $e->getMessage());

            if (isset($tempDir) && File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Импорт содержимого в НОВЫЙ ресурс.
     * Создаёт Tree + Content (+ тесты) в указанной родительской папке
     * и возвращает данные созданного документа.
     */
    public function importResource(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip|max:51200',
            'parent_id' => 'nullable|exists:trees,id',
            'name' => 'nullable|string|max:255',
            'include_tests' => 'nullable|in:0,1,true,false,on,off',
        ]);

        $user = Auth::user();

        // Проверяем права на родительскую папку (если указана)
        $parentId = $request->filled('parent_id') ? (int) $request->input('parent_id') : null;
        if ($parentId) {
            $parent = Tree::find($parentId);
            if (!$parent || !$this->canManageTree($user, $parent)) {
                return response()->json(['success' => false, 'message' => 'Доступ запрещен'], 403);
            }
        }

        try {
            // Читаем и валидируем импортируемый файл
            $importData = $this->readImportFile($request->file('file'));

            $resourceData = $importData['resource'];

            if (empty($resourceData['data']) || !is_array($resourceData['data'])) {
                throw new \Exception('Нет содержимого для импорта');
            }

            // Восстанавливаем файлы из архива
            $filePaths = $this->restoreImportedFiles($importData, $request->file('file'));

            // Обновляем пути в блоках на восстановленные файлы
            $updatedData = $this->updatePathsInBlocks($resourceData['data'], $filePaths);

            // Название: из формы > из файла > по умолчанию
            $resourceName = $request->input('name') ?: ($resourceData['name'] ?? 'Импортированный ресурс');
            $baseName = $resourceName;
            $counter = 1;
            while (Tree::where('tree_id', $parentId)->where('name', $resourceName)->exists()) {
                $resourceName = $baseName . ' (' . $counter . ')';
                $counter++;
            }

            $position = Tree::where('tree_id', $parentId)->count();

            $tree = Tree::create([
                'name' => $resourceName,
                'slug' => $this->generateUniqueTreeSlug($resourceName),
                'tree_id' => $parentId,
                'user_id' => $user->id,
                'position' => $position,
                'type' => 'file',
            ]);

            $content = Content::create([
                'tree_id' => $tree->id,
                'data' => json_encode($updatedData, JSON_UNESCAPED_UNICODE),
                'accessibility' => 0,
                'accessibilitymanagers' => 0,
                'accessibilitylink' => 0,
            ]);

            // Импортируем тесты
            $testsCount = 0;
            if ($this->wantsTests($request) && isset($importData['tests']) && !empty($importData['tests'])) {
                $testsCount = $this->importTests($tree->id, $importData['tests']);
            }

            return response()->json([
                'success' => true,
                'is_new' => true,
                'name' => $resourceName,
                'tree_id' => $tree->id,
                'slug' => $tree->slug,
                'tests_imported' => $testsCount,
                'message' => "Документ '{$resourceName}' успешно создан" . ($testsCount > 0 ? " с {$testsCount} тестами" : ''),
            ]);
        } catch (\Exception $e) {
            \Log::error('Import error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Читает ZIP архив импорта и возвращает распарсенные данные resource.json.
     * Бросает исключение при неверном формате.
     */
    private function readImportFile($file)
    {
        $tempDir = storage_path('app/temp/import_' . uniqid());
        File::makeDirectory($tempDir, 0755, true);

        try {
            $zip = new \ZipArchive();
            if ($zip->open($file->path()) !== true) {
                throw new \Exception('Не удалось открыть ZIP архив');
            }
            $zip->extractTo($tempDir);
            $zip->close();

            $jsonPath = $tempDir . '/resource.json';
            if (!File::exists($jsonPath)) {
                throw new \Exception('В ZIP архиве не найден файл resource.json');
            }

            $jsonContent = File::get($jsonPath);

            $importData = json_decode($jsonContent, true);

            if (!$importData || !isset($importData['resource'])) {
                throw new \Exception('Неверный формат файла импорта');
            }

            if (!isset($importData['version'])) {
                throw new \Exception('Неизвестная версия файла');
            }

            return $importData;
        } finally {
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Восстанавливает файлы из ZIP архива на постоянное хранение.
     */
    private function restoreImportedFiles($importData, $file)
    {
        $filePaths = [];
        if (!isset($importData['files']) || !is_array($importData['files'])) {
            return $filePaths;
        }

        $tempDir = storage_path('app/temp/import_' . uniqid());
        File::makeDirectory($tempDir, 0755, true);

        try {
            $zip = new \ZipArchive();
            if ($zip->open($file->path()) !== true) {
                throw new \Exception('Не удалось открыть ZIP архив');
            }
            $zip->extractTo($tempDir);
            $zip->close();

            foreach ($importData['files'] as $key => $relativePath) {
                $sourcePath = $tempDir . '/' . $relativePath;
                if (File::exists($sourcePath)) {
                    $savedPath = $this->saveImportedFile($sourcePath);
                    if ($savedPath) {
                        $filePaths[$key] = $savedPath;
                    }
                }
            }
        } finally {
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }

        return $filePaths;
    }

    /**
     * Разбирает флаг include_tests из запроса.
     */
    private function wantsTests(Request $request)
    {
        $includeTests = $request->input('include_tests', '1');
        if (is_string($includeTests)) {
            return in_array($includeTests, ['1', 'true', 'on'], true);
        }
        return (bool) $includeTests;
    }

    /**
     * Импорт содержимого в РЕДАКТИРЫЕМЫЙ документ.
     * Ничего не сохраняет в БД: восстанавливает файлы на диск,
     * возвращает блоки для вставки в редактор на клиенте.
     * Тесты (опционально) сразу привязываются к текущему дереву.
     */
    public function importIntoResource(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip|max:51200',
            'tree_id' => 'nullable|exists:trees,id',
            'include_tests' => 'nullable|in:0,1,true,false,on,off',
        ]);

        $user = Auth::user();
        $treeId = $request->filled('tree_id') ? (int) $request->input('tree_id') : null;

        // Если документ уже сохранён — проверяем права на него.
        // Для нового (ещё не сохранённого) документа tree_id нет — просто вставляем блоки.
        if ($treeId) {
            $tree = Tree::find($treeId);
            if (!$tree) {
                return response()->json(['success' => false, 'message' => 'Документ не найден'], 404);
            }

            if (!$this->canManageTree($user, $tree)) {
                return response()->json(['success' => false, 'message' => 'Доступ запрещен'], 403);
            }
        }

        try {
            // Читаем и валидируем импортируемый файл
            $importData = $this->readImportFile($request->file('file'));

            $resourceData = $importData['resource'];

            if (empty($resourceData['data']) || !is_array($resourceData['data'])) {
                throw new \Exception('Нет содержимого для импорта');
            }

            // Восстанавливаем файлы из архива на постоянное хранение
            $filePaths = $this->restoreImportedFiles($importData, $request->file('file'));

            // Обновляем пути в блоках на восстановленные файлы
            $updatedData = $this->updatePathsInBlocks($resourceData['data'], $filePaths);

            // Импортируем тесты (сразу в БД, к текущему дереву)
            $testsCount = 0;
            if ($this->wantsTests($request) && $treeId && isset($importData['tests']) && !empty($importData['tests'])) {
                $testsCount = $this->importTests($treeId, $importData['tests']);
            }

            return response()->json([
                'success' => true,
                'blocks' => $updatedData,
                'name' => $resourceData['name'] ?? null,
                'tests_imported' => $testsCount,
                'message' => 'Содержимое готово к вставке' . ($testsCount > 0 ? " (тестов импортировано: {$testsCount})" : ''),
            ]);
        } catch (\Exception $e) {
            \Log::error('Import into resource error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    private function collectFilesFromBlocks($blocks)
    {
        $files = [];

        if (!is_array($blocks)) {
            return $files;
        }

        foreach ($blocks as $index => $block) {
            if (!isset($block['type']) || !isset($block['data'])) {
                continue;
            }

            // Изображения
            if ($block['type'] === 'image' && isset($block['data']['file']['url'])) {
                $key = 'image_' . $index;
                $files[$key] = [
                    'url' => $block['data']['file']['url'],
                    'type' => 'image',
                    'block_index' => $index,
                ];
            }

            // Галерея
            if ($block['type'] === 'gallery' && isset($block['data']['files'])) {
                foreach ($block['data']['files'] as $fileIndex => $file) {
                    if (isset($file['url'])) {
                        $key = 'gallery_' . $index . '_' . $fileIndex;
                        $files[$key] = [
                            'url' => $file['url'],
                            'type' => 'gallery',
                            'block_index' => $index,
                            'file_index' => $fileIndex,
                        ];
                    }
                }
            }

            // Вложения
            if ($block['type'] === 'attaches' && isset($block['data']['file']['url'])) {
                $key = 'attach_' . $index;
                $files[$key] = [
                    'url' => $block['data']['file']['url'],
                    'type' => 'attach',
                    'block_index' => $index,
                ];
            }
        }

        return $files;
    }

    private function copyFileToTemp($fileData, $tempDir)
    {
        try {
            $url = $fileData['url'];

            // Если путь уже начинается с /storage/, используем его
            if (strpos($url, '/storage/') === 0) {
                $path = $url;
            } else {
                // Убираем домен
                $parsedUrl = parse_url($url);
                $path = $parsedUrl['path'] ?? $url;
            }

            // Пытаемся найти файл
            $fullPath = null;
            $searchPaths = [
                public_path($path),
                storage_path('app/public/' . ltrim($path, '/')),
                storage_path('app/' . ltrim($path, '/')),
            ];

            // Если путь содержит /storage/, пробуем другие варианты
            if (strpos($path, '/storage/') === 0) {
                $relativePath = substr($path, 9); // убираем /storage/
                $searchPaths[] = storage_path('app/public/' . $relativePath);
                $searchPaths[] = public_path('storage/' . $relativePath);
            }

            foreach ($searchPaths as $searchPath) {
                if (File::exists($searchPath)) {
                    $fullPath = $searchPath;
                    break;
                }
            }

            // Если файл не найден, пробуем скачать
            if (!$fullPath && filter_var($url, FILTER_VALIDATE_URL)) {
                $content = @file_get_contents($url);
                if ($content !== false) {
                    $filename = basename($url);
                    if (empty($filename) || !strpos($filename, '.')) {
                        $filename = 'file_' . uniqid() . '.tmp';
                    }
                    $relativePath = 'files/' . $filename;
                    $tempPath = $tempDir . '/' . $relativePath;
                    File::makeDirectory(dirname($tempPath), 0755, true);
                    File::put($tempPath, $content);
                    return $relativePath;
                }
                return null;
            }

            if (!$fullPath) {
                \Log::warning('File not found: ' . $url);
                return null;
            }

            // Копируем файл во временную папку
            $filename = basename($fullPath);
            if (empty($filename)) {
                $extension = File::extension($fullPath);
                $filename = uniqid() . '.' . $extension;
            }

            $relativePath = 'files/' . $filename;
            $tempPath = $tempDir . '/' . $relativePath;
            File::makeDirectory(dirname($tempPath), 0755, true);
            File::copy($fullPath, $tempPath);

            return $relativePath;
        } catch (\Exception $e) {
            \Log::error('Error copying file: ' . $e->getMessage() . ' URL: ' . ($fileData['url'] ?? ''));
            return null;
        }
    }

    public function downloadResource($id)
    {
        // $id — tree_id (как и во всех остальных роутах)
        $content = Content::with('tree')->where('tree_id', $id)->first();

        if (!$content) {
            abort(404, 'Ресурс не найден');
        }

        // Проверка прав через Auth (для web middleware)
        $user = Auth::user();
        if (!$user || ($user->role !== 'admin' && $content->tree->user_id !== $user->id)) {
            abort(403, 'Доступ запрещен');
        }

        // Получаем параметры из запроса
        $includeTests = filter_var(request()->input('include_tests', false), FILTER_VALIDATE_BOOLEAN);

        try {
            // Парсим содержимое
            $dataBlocks = json_decode($content->data, true);
            if (!is_array($dataBlocks)) {
                $dataBlocks = [];
            }

            // Собираем файлы из блоков
            $files = $this->collectFilesFromBlocks($dataBlocks);

            // Создаем временную папку
            $tempDir = storage_path('app/temp/export_' . uniqid());
            if (!File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            // Копируем файлы
            $filePaths = [];
            foreach ($files as $key => $fileData) {
                $filePath = $this->copyFileToTemp($fileData, $tempDir);
                if ($filePath) {
                    $filePaths[$key] = $filePath;
                }
            }

            // Обновляем пути в блоках
            $updatedData = $this->updatePathsInBlocks($dataBlocks, $filePaths);

            // Формируем данные для экспорта
            $exportData = [
                'version' => '2.0',
                'exported_at' => now()->toISOString(),
                'resource' => [
                    'name' => $content->tree->name,
                    'data' => $updatedData,
                ],
                'files' => $filePaths,
            ];

            // Добавляем тесты если нужно
            if ($includeTests) {
                $tests = Test::where('tree_id', $content->tree_id)->get();
                $exportData['tests'] = $tests->map(function ($test) {
                    return [
                        'id' => $test->id,
                        'title' => $test->title,
                        'description' => $test->description,
                        'timeLimit' => $test->timeLimit,
                        'settings' => $test->settings,
                        'grading' => $test->grading,
                        'questions' => $this->exportQuestions($test->id),
                    ];
                });
                $exportData['has_tests'] = true;
                $exportData['tests_count'] = $tests->count();
            } else {
                $exportData['has_tests'] = false;
                $exportData['tests_count'] = 0;
            }

            // Сохраняем JSON
            $jsonPath = $tempDir . '/resource.json';
            File::put($jsonPath, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Создаем ZIP
            $zipFileName = 'resource_' . $content->tree->slug . '_' . date('Y-m-d') . '.zip';
            $zipPath = storage_path('app/temp/' . $zipFileName);

            // Удаляем старый ZIP если есть
            if (File::exists($zipPath)) {
                File::delete($zipPath);
            }

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Не удалось создать ZIP архив');
            }

            // Добавляем все файлы из временной папки
            $this->addDirectoryToZip($zip, $tempDir, '');

            $zip->close();

            // Проверяем, что ZIP создан
            if (!File::exists($zipPath) || File::size($zipPath) === 0) {
                throw new \Exception('ZIP архив не создан или пуст');
            }

            // Очищаем временную папку
            File::deleteDirectory($tempDir);

            // Отдаем файл
            return response()
                ->download($zipPath, $zipFileName, [
                    'Content-Type' => 'application/zip',
                    'Content-Disposition' => 'attachment; filename="' . $zipFileName . '"',
                    'Cache-Control' => 'private, no-cache, must-revalidate',
                ])
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Export error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            // Очищаем временную папку если есть
            if (isset($tempDir) && File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }

            // Возвращаем ошибку в JSON для API или HTML для web
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }

            abort(500, $e->getMessage());
        }
    }



    private function addFilesToZip($zip, $dir, $relativePath = '')
    {
        $files = File::allFiles($dir);

        foreach ($files as $file) {
            $filePath = $file->getPathname();
            $relative = ltrim(str_replace($dir, '', $filePath), DIRECTORY_SEPARATOR);
            $relative = str_replace('\\', '/', $relative);

            // Добавляем файл в ZIP с указанием пути
            $zip->addFile($filePath, $relative);
        }
    }

    private function updatePathsInBlocks($blocks, $filePaths)
    {
        if (!is_array($blocks)) {
            return $blocks;
        }

        foreach ($blocks as $index => &$block) {
            if (!isset($block['type']) || !isset($block['data'])) {
                continue;
            }

            // Изображения
            if ($block['type'] === 'image' && isset($block['data']['file']['url'])) {
                $key = 'image_' . $index;
                if (isset($filePaths[$key])) {
                    // Для импорта - оставляем как есть, путь будет заменен
                    $block['data']['file']['url'] = $filePaths[$key];
                }
            }

            // Галерея
            if ($block['type'] === 'gallery' && isset($block['data']['files'])) {
                foreach ($block['data']['files'] as $fileIndex => &$file) {
                    if (isset($file['url'])) {
                        $key = 'gallery_' . $index . '_' . $fileIndex;
                        if (isset($filePaths[$key])) {
                            $file['url'] = $filePaths[$key];
                        }
                    }
                }
            }

            // Вложения
            if ($block['type'] === 'attaches' && isset($block['data']['file']['url'])) {
                $key = 'attach_' . $index;
                if (isset($filePaths[$key])) {
                    $block['data']['file']['url'] = $filePaths[$key];
                }
            }
        }

        return $blocks;
    }

    private function saveImportedFile($sourcePath)
    {
        try {
            $filename = basename($sourcePath);
            $extension = File::extension($sourcePath);

            // Создаем уникальное имя
            $newFilename = uniqid() . '.' . $extension;

            // Путь для сохранения
            $savePath = 'uploads/' . date('Y/m/d');
            $fullSavePath = storage_path('app/public/' . $savePath);

            File::makeDirectory($fullSavePath, 0755, true);

            $destination = $fullSavePath . '/' . $newFilename;
            File::copy($sourcePath, $destination);

            return '/storage/' . $savePath . '/' . $newFilename;
        } catch (\Exception $e) {
            \Log::error('Error saving imported file: ' . $e->getMessage());
            return null;
        }
    }

    private function addDirectoryToZip($zip, $dir, $relativePath)
    {
        $files = File::allFiles($dir);

        foreach ($files as $file) {
            $filePath = $file->getPathname();
            // Получаем относительный путь от корня временной папки
            $relative = ltrim(str_replace($dir, '', $filePath), DIRECTORY_SEPARATOR);
            // Для Windows заменяем обратные слеши
            $relative = str_replace('\\', '/', $relative);
            $zip->addFile($filePath, $relative);
        }
    }

    private function exportQuestions($testId)
    {
        $questions = Question::where('test_id', $testId)->orderBy('order')->get();

        return $questions->map(function ($question) {
            return [
                'id' => $question->id,
                'stable_key' => $question->stable_key,
                'text' => $question->text,
                'type' => $question->type,
                'points' => $question->points ?? 1,
                'image' => $question->image,
                'options' => $question->options,
                'order' => $question->order ?? 0,
            ];
        });
    }

    private function importTests($treeId, $testsData)
    {
        $count = 0;

        foreach ($testsData as $testData) {
            $test = Test::create([
                'tree_id' => $treeId,
                'title' => $testData['name'] ?? $testData['title'] ?? 'Импортированный тест',
                'description' => $testData['description'] ?? null,
                'timeLimit' => $testData['timeLimit'] ?? ($testData['settings']['time_limit'] ?? null),
                'settings' => is_array($testData['settings'] ?? null)
                    ? $testData['settings']
                    : null,
                'grading' => $testData['grading'] ?? null,
            ]);

            if (!empty($testData['questions'])) {
                foreach ($testData['questions'] as $index => $questionData) {
                    $options = $questionData['options'] ?? [];

                    Question::create([
                        'test_id' => $test->id,
                        'text' => $questionData['question'] ?? $questionData['text'] ?? '',
                        'type' => $questionData['type'] ?? 'single',
                        'points' => $questionData['points'] ?? 1,
                        'image' => $questionData['image'] ?? null,
                        'options' => is_array($options) ? $options : json_decode($options ?: '[]', true),
                        'order' => $questionData['order'] ?? $index,
                    ]);
                }
            }

            $count++;
        }

        return $count;
    }

}
