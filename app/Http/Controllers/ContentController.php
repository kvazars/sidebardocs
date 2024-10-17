<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Tree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function saveImage(Request $request)
    {
        $path = Storage::disk("public")->putFile("contentImages", $request->file('image'));
        return response()->json(['success' => 1, 'file' => ['url' => env('APP_URL') . $path]], 200);
    }
    public function saveFile(Request $request)
    {
        $path = Storage::disk("public")->putFile("contentFiles", $request->file('file'));
        return response()->json(['success' => 1, 'file' => ['url' => env('APP_URL') . $path]], 200);
    }

    public function saveImageByUrl(Request $request)
    {
        $contents = file_get_contents($request->url);
        $name = explode('.', $request->url);
        $name = 'contentImages/' . Str::random(40) . '.' . end($name);
        Storage::disk('public')->put($name, $contents);

        return response()->json(['success' => 1, 'file' => ['url' => env('APP_URL') . 'public/' . $name]], 200);
    }

    public function saveResource(Request $request)
    {
        $fileId = null;
        //    return $request->all();
        if (isset($request->tree_id)) {
            // return 1;
            $tree = Tree::create([
                'name' => $request->name,
                'tree_id' => $request->tree_id,
                'user_id' => 1,
            ]);
            $fileId = Content::create([
                'tree_id' => $tree->id,
                // 'name' => $request->name,
                'accessibility' => false,
                'data' => $request->data,
            ]);

            return response()->json(['success' => true, 'id' => $fileId->tree_id]);
        } else {
            $tree = Tree::find($request->id);
            $content = Content::where("tree_id", $tree->tree_id)->first();

            $fileId = $content->update([
                // 'name' => $request->name,
                'accessibility' => false,
                'data' => $request->data,
            ]);
            $tree->update([
                'name' => $request->name,
                // 'user_id' => 1,
            ]);
            return response()->json(['success' => true, 'id' => $fileId->tree_id]);
        }
    }
    public function getResource($content)
    {

        $tree = Tree::find($content);
        $res = Content::where("tree_id", $content)->first();
        return response()->json(['content' => $res, "name" => $tree->name]);
    }

    public function delResource($content)
    {
        // return Tree::find($content->id);
        Tree::find($content)->delete();
        return response()->json(["success" => true]);
    }
}
