<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResourceSaveRequest;
use App\Models\Available;
use App\Models\Content;
use App\Models\Group;
use App\Models\Tree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    public function saveFile(Request $request)
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


    public function getImage(Request $request)
    {
        if ($request->image and  file_exists(public_path() . '/' . $request->image)) {
            $data = file_get_contents(public_path() . $request->image);

            $type = explode('.', $request->image);
            $base64 = 'data:image/' . end(array: $type) . ';base64,' . base64_encode($data);
            return response()->json(data: ['success' => true, 'image' =>  $base64]);
        } else {
            return response()->json(data: ['success' => false, 'image' =>  URL::to('/') . "/notfound.webp"]);
        }
    }
    public function getFile(Request $request)
    {
        if ($request->file and file_exists(public_path() . '/' . $request->file)) {
            return response()->json(data: ['success' => true, 'url' => URL::to('/') . $request->file]);
        } else {
            return response()->json(data: ['success' => false]);
        }
    }

    public function saveResource(ResourceSaveRequest $request)
    {
        $fileId = null;
        if (isset($request->tree_id)) {
            $tr = Tree::find($request->tree_id);
            $tree = Tree::create([
                'name' => $request->name,
                'tree_id' => $request->tree_id,
                'user_id' => $tr->user_id,
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
}
