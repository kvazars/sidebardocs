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
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

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

            // Сохраняем файл напрямую
            $file->storeAs($dirshort, $name, 'public');

            \Log::info("Изображение успешно сохранено: $fullPath");

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
        if (!is_dir(public_path("contentFiles"))) {
            mkdir(public_path("contentFiles"));
        }
        $dirshort = "contentFiles/" . Auth::user()->id;
        $dir = public_path($dirshort);
        if (!is_dir(filename: $dir)) {
            mkdir($dir);
        }
        $uploaded = $request->file('file');
        $originalName = $uploaded->getClientOriginalName();
        $size = $uploaded->getSize();
        $extension = strtolower($uploaded->getClientOriginalExtension());
        $name = Str::random(40) . "." . $uploaded->extension();
        $pathshort = $dirshort . '/' . $name;
        Storage::disk("public")->putFileAs($dir, $uploaded, $name);
        $withoutExt = $originalName !== ''
            ? pathinfo($originalName, PATHINFO_FILENAME)
            : pathinfo($name, PATHINFO_FILENAME);
        $displayName = $withoutExt !== '' ? $withoutExt : $originalName;

        return response()->json([
            'success' => 1,
            'file' => [
                'url' => URL::to('/') . "/" . $pathshort,
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
        $dir = public_path($dirshort);
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        $path   = Image::read(file_get_contents($request->url));
        $resize = $path->scaleDown(1024, 1024)->toWebp(100);

        $name = Str::random(40) . ".webp";
        $path = $dir . "/" . $name;
        $pathshort = $dirshort . '/' . $name;

        Storage::disk("public")->put($path, $resize);
        return response()->json(['success' => 1, 'file' => ['url' => URL::to('/') . "/" . $pathshort]], 200);
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
            Tree::where('slug', $slug)
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
}
