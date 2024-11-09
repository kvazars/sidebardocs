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
    /**
     * Display a listing of the resource.
     */
    public function saveImage(Request $request)
    {
        $dirshort = "contentImages/" . Auth::user()->id;
        $dir = public_path($dirshort);
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        //return $request->file('image');
        if ($request->file('image')) {
            $path   = Image::read($request->file('image'));
            $resize = $path->scaleDown(1024, 1024)->toWebp(90);
            $name = Str::random(40) . ".webp";
            $path = "{$dir}/{$name}";
            $pathshort = "{$dirshort}/{$name}";
            Storage::disk("public")->put($path, $resize);
            return response()->json(['success' => 1, 'file' => ['url' => URL::to('/') . "/" . $pathshort]], 200);
        }
    }
    public function saveFile(UploadFileRequest $request)
    {
        $dirshort = "contentFiles/" . Auth::user()->id;
        $dir = public_path($dirshort);
        if (!is_dir(filename: $dir)) {
            mkdir($dir);
        }
        $name = Str::random(40) . "." . $request->file('file')->extension();
        $pathshort = $dirshort . '/' . $name;
        Storage::disk("public")->putFileAs($dir, $request->file('file'), $name);
        return response()->json(['success' => 1, 'file' => ['url' => URL::to('/') . "/" . $pathshort]], 200);
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
            $position = Tree::where("tree_id", $tr->id)->count();

            $tree = Tree::create([
                'name' => $request->name,
                'tree_id' => $request->tree_id,
                'user_id' => $tr->user_id,
                'position' => $position,
            ]);
            $fileId = Content::create([
                'tree_id' => $tree->id,
                'accessibility' => $request->accessibility,
                'data' => $request->data,
            ]);

            $this->changeAvailables($tree->id, $request->availables, $request->accessibility);

            return response()->json(['success' => true, 'message' => 'Файл успешно создан', 'id' => $fileId->tree_id]);
        } else {
            $tree = Tree::find($request->id);
            $fileId = Content::where("tree_id", $request->id)->first();

            $fileId->update([
                'accessibility' => $request->accessibility,
                'data' => $request->data,
            ]);
            $tree->update([
                'name' => $request->name,
            ]);

            $this->changeAvailables($tree->id, $request->availables, $request->accessibility);

            return response()->json(['success' => true, 'message' => 'Данные файла обновлены', 'id' => $fileId->tree_id]);
        }
    }

    public function changeAvailables($tree, $availables, $accessibility)
    {

        Available::where('tree_id', $tree)->delete();

        if (!$accessibility) {
            foreach (json_decode($availables) as $available) {
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

    public function getResource($content)
    {
        $tree = Tree::withTrashed()->find($content);
        if (!$tree) {
            return response()->json(['success' => false, "message" => 'Файла не существует']);
        }
        if (!Auth::user()) {
            $res = Content::where("tree_id", $content)->where("accessibility", true)->first();
            if (!$res) {
                return response()->json(['success' => false, "message" => 'Доступ к файлу запрещен']);
            }
        } else {
            $user = Auth::user();
            if ($user->role == 'user') {
                $gr = UserGroups::where("user_id", $user->id)->first();
                $avia = Available::where("group_id", $gr->group_id)->where("tree_id", $content)->first();
                $access = Content::where("tree_id", $content)->where("accessibility", true)->first();
                if (!$avia and !$access) {
                    return response()->json(['success' => false, "message" => 'Доступ к файлу запрещен']);
                }
            }
        }
        $res = Content::with("tree")->where("tree_id", $content)->first();

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

        if ($request->user()->role == 'ceo' and $request->user()->id != $tree->user_id) {
            return response()->json(["success" => false, 'message' => 'Недостаточно прав']);
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


        $dirFiles = [];
        $u = User::where('role', '!=', 'user')->pluck('id')->toArray();

        $dirs = ['/contentImages/', '/contentFiles/'];
        foreach ($dirs as $dirss) {
            foreach ($u as $value) {
                $dir = public_path('') . $dirss . $value;

                if (is_dir($dir)) {
                    if ($dh = opendir($dir)) {
                        while (($file = readdir($dh)) !== false) {
                            if ($file != '.' && $file != '..') {
                                $dirFiles[] = $dirss . $value . '/' . $file;
                            }
                        }
                        closedir($dh);
                    }
                }
            }
        }


        $dir = public_path('logo');

        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file != '.' && $file != '..') {
                        $dirFiles[] =   'logo/' . $file;
                    }
                }
                closedir($dh);
            }
        }
        $savedImages = [];
        $savedFiles = [];

        $c = Content::pluck('data');
        foreach ($c as $value) {
            $value = json_decode($value);
            foreach ($value as $v) {
                if (isset($v->type))
                    switch ((string)$v->type) {
                        case 'image':
                            $savedImages[] = $v->data->file->url;
                            break;
                        case 'gallery':
                            for ($i = 0; $i < count($v->data->files); $i++) {
                                $savedImages[] = $v->data->files[$i]->url;
                            }
                            break;
                        case 'attaches':
                            $savedFiles[] = $v->data->file->url;
                            break;
                    }
            }
        }
        $about = About::find(1);

        $del = array_diff($dirFiles, array_merge($savedImages, $savedFiles, [$about->logo]));

        Storage::disk('public')->delete($del);



        //удалим все удаленные группы
        // User::onlyTrashed()->forceDelete();
        // $gr = $users->pluck("id")->toArray();
        // UserGroups::whereIn("user_id",$gr)->delete();
        // $users->forceDelete();
        //


        Artisan::call('route:clear');
        Artisan::call('cache:clear');

        return response()->json(['success' => true, 'message' => 'Кэш очищен']);
    }


    public function getFiles(Request $request)
    {
        $userRole = Auth::user()->role;
        $files = [];
        $users = [];
        if ($userRole == 'ceo') {
            $files = Tree::where('user_id', Auth::user()->id)->where('type', 'file')->with(['child', 'parent', 'available'])->withTrashed();
        } else {
            $files = Tree::where('type', 'file')->with(['child', 'user', 'parent', 'available'])->withTrashed();
            $users = User::where('role', '!=', 'user')->get(['id', 'name']);
        }

        $group = Group::get()->sortBy("name");

        if (isset($request->search)) {
            $files->where('name', 'LIKE', '%' . $request->input('search') . '%');
        }

        if (isset($request->user)) {
            $files->where('user_id', $request->user);
        }

        // Model::with(['relation' => function($query){
        //     $query->orderBy('column', 'ASC');
        //  }]);

        $files->orderBy($request->sortBy ?: 'name', $request->sortAsc == 'true' ? 'asc' : 'desc');

        $files = $files->paginate(15);


        foreach ($files as $file) {
            $file->child->accessibility = $file->child->accessibility == 1;

            $a = $file->available->pluck("group_id")->toArray();
            $res = [];
            foreach ($group->toArray() as $g) {
                $res[] = array_merge($g, ["checked" => in_array($g['id'], $a) ? true : false]);
            }
            $file->groups = $res;
        }

        return response()->json(['success' => true, 'data' => ['files' => $files, 'users' => $users]], 200);
    }
}
