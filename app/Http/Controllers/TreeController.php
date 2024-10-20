<?php

namespace App\Http\Controllers;

use App\Models\Tree;
use Illuminate\Http\Request;

class TreeController extends Controller
{
    public function index()
    {
        return Tree::get();
    }


    public function store(Request $request)
    {
        if (isset($request->id)) {
            Tree::find($request->id)->update(["name" => $request->name]);
            return response()->json(['success' => true, 'message' => 'Название папки изменено']);
        } else {
            Tree::create([
                "name" => $request->name,
                'tree_id' => $request->tree_id ?: null,
                'user_id' => 1,
                'type' => 'folder',
            ]);
            return response()->json(['success' => true, 'message' => 'Новая папка успешно создана']);
        }
    }
    public function delete($del)
    {
        $tree = Tree::where('tree_id', $del)->first();
        if (!$tree) {
            Tree::find($del)->delete();
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false, 'message' => 'Данную папку нельзя удалить']);
        }
    }
}
