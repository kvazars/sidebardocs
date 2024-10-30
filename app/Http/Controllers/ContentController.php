<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResourceSaveRequest;
use App\Http\Requests\UploadFileRequest;
use App\Models\Available;
use App\Models\Content;
use App\Models\Group;
use App\Models\Tree;
use App\Models\User;
use App\Models\UserGroups;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        if ($request->file('image')) {
            $path   = Image::read($request->file('image'));
            $resize = $path->scaleDown(1024, 1024)->toWebp(90);
            $path = "contentImages/" . Auth::user()->id . "/" . Str::random(40) . ".webp";
            Storage::disk("public")->put($path, $resize);
            return response()->json(['success' => 1, 'file' => ['url' => URL::to('/') . "/" . $path]], 200);
        }
    }
    public function saveFile(UploadFileRequest $request)
    {
        $path = Storage::disk("public")->putFile("contentFiles/" . Auth::user()->id, $request->file('file'));
        return response()->json(['success' => 1, 'file' => ['url' => URL::to('/') . "/" . $path]], 200);
    }

    public function saveImageByUrl(Request $request)
    {
        $path   = Image::read(file_get_contents($request->url));
        $resize = $path->scaleDown(1024, 1024)->toWebp(100);
        $path = "contentImages/" . Auth::user()->id . "/" . Str::random(40) . ".webp";
        Storage::disk("public")->put($path, $resize);
        return response()->json(['success' => 1, 'file' => ['url' => URL::to('/') . "/" . $path]], 200);
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
        $tree = Tree::find($content);
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

        $all = Group::get();
        $g = Available::where('tree_id', $content)->pluck('group_id')->toArray();


        foreach ($all as $value) {
            $availablesGroups[] = ['id' => $value->id, 'name' => $value->name, 'checked' => in_array($value->id, $g)];
        }

        return response()->json(["name" => $tree->name, 'content' => $res,  'groups' => $availablesGroups]);
    }

    public function delResource(Request $request, Tree $content)
    {

        if ($request->user()->role == 'user') {
            return response()->json(["success" => false, 'message' => 'Недостаточно прав']);
        }

        if ($request->user()->role == 'ceo' and $request->user()->id != $content->user_id) {
            return response()->json(["success" => false, 'message' => 'Недостаточно прав']);
        }

        Tree::find($content->id)->delete();
        return response()->json(["success" => true, 'message' => 'Файл удален']);
    }

    public function checkImageResource()
    {
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

        //return $savedImages;
        $del = array_diff($dirFiles, array_merge($savedImages, $savedFiles));
        // foreach ($del as $value) {
        //     Storage::delete()
        // }
        Storage::disk('public')->delete($del);
        // return array_diff($dirFiles, array_merge($savedImages, $savedFiles));
        Artisan::call('route:clear');
        Artisan::call('cache:clear');

        return response()->json(['success' => true, 'message' => 'Кэш очищен']);

        // return array_merge($savedImages, $savedFiles);
    }


    public function getFiles()
    {
        $userRole = Auth::user()->role;
        $files = [];
        if ($userRole == 'ceo') {
            $files = Tree::where('user_id', Auth::user()->id)->where('type', 'file')->with(['child', 'parent', 'available'])->get()->keyBy("id");
        } else {
            $files = Tree::where('type', 'file')->with(['child', 'user', 'parent', 'available'])->get()->keyBy("id");
        }
        return ["files" => $files,  "groups" => Group::get()->keyBy("id")];
    }
}
