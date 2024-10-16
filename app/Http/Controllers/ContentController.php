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
        //    return $request->all();
        if (isset($request->tree_id)) {
            // return 1;
            Content::create([
                'tree_id' => $request->tree_id,
                // 'name' => $request->name,
                'accessibility' => false,
                'data' => $request->data,
            ]);
            Tree::create([
                'name' => $request->name,
                'tree_id' => $request->tree_id,
                'user_id' => 1,
            ]);
            return response()->json(['success' => true]);
        } else {    
            $content = Content::find($request->id);

            $content->update([
                // 'tree_id' => $request->tree_id,
                // 'name' => $request->name,
                'accessibility' => false,
                'data' => $request->data,
            ]);
            Tree::where('id',$content->tree_id)->update([
                'name' => $request->name,
                // 'tree_id' => $request->tree_id,
                // 'user_id' => 1,
            ]);
            return response()->json(['success' => true]);
        }
    }
    public function getResource(Content $content)
    {
        return response()->json(['content' => $content, "name" => Tree::find($content->tree_id)->name]);
    }

    public function delResource(Content $content) {
        Tree::find($content->tree_id)->delete();
        return response()->json(["success"=> true]);
    }
}
