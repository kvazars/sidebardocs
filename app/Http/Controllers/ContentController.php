<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResourceSaveRequest;
use App\Models\Content;
use App\Models\Tree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function saveImage(Request $request)
    {
        $path = Storage::disk("public")->putFile("contentImages", $request->file('image'));
        return response()->json(['success' => 1, 'file' => ['url' => URL::to('/') . "/" . $path]], 200);
    }
    public function saveFile(Request $request)
    {
        $path = Storage::disk("public")->putFile("contentFiles", $request->file('file'));
        return response()->json(['success' => 1, 'file' => ['url' => URL::to('/') . "/" . $path]], 200);
    }

    public function saveImageByUrl(Request $request)
    {
        $contents = file_get_contents($request->url);
        $name = explode('.', $request->url);
        $name = 'contentImages/' . Str::random(40) . '.' . end($name);
        Storage::disk('public')->put($name, $contents);

        return response()->json(['success' => 1, 'file' => ['url' => URL::to('/') . "/" . $name]], 200);
    }


    public function getImage(Request $request)
    {

        if ($request->image and  file_exists(public_path() . '/' . $request->image)) {
            // $img = explode('/contentImages', $request->image);

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
        // return response()->json(data: ['success' => true, 'url' => URL::to('/') .$request->file]);

        if ($request->file and file_exists(public_path() . '/' . $request->file)) {
            // $img = explode('/contentImages', $request->image);
            // return 1;
            // $data = file_get_contents(public_path() . $request->file);

            return response()->json(data: ['success' => true, 'url' => URL::to('/') .$request->file]);
        } else {
            return response()->json(data: ['success' => false]);
        }
    }

    public function saveResource(ResourceSaveRequest $request)
    {
        $fileId = null;
        if (isset($request->tree_id)) {
            // return 1;
            $tree = Tree::create([
                'name' => $request->name,
                'tree_id' => $request->tree_id,
                'user_id' => Auth::user()->id,
            ]);
            $fileId = Content::create([
                'tree_id' => $tree->id,
                // 'name' => $request->name,
                'accessibility' => $request->accessibility?:false,
                'data' => $request->data,
            ]);

            return response()->json(['success' => true, 'message' => 'Файл успешно создан', 'id' => $fileId->tree_id]);
        } else {
            $tree = Tree::find($request->id);
            $fileId = Content::where("tree_id", $request->id)->first();

            $fileId->update([
                // 'name' => $request->name,
                'accessibility' => false,
                'data' => $request->data,
            ]);
            $tree->update([
                'name' => $request->name,
                // 'user_id' => 1,
            ]);
            return response()->json(['success' => true, 'message' => 'Данные файла обновлены', 'id' => $fileId->tree_id]);
        }
    }
    public function getResource($content)
    {

        $tree = Tree::find($content);
        if (!$tree) {
            return response()->json(['success' => false, "message" => 'Файла не существует']);
        }
        $res = Content::with("tree")->where("tree_id", $content)->first();
        return response()->json(['content' => $res, "name" => $tree->name]);
    }

    public function delResource($content)
    {
        // return Tree::find($content->id);
        Tree::find($content)->delete();
        return response()->json(["success" => true, 'message' => 'Файл удален']);
    }
}
