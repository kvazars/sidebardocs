<?php

namespace App\Http\Controllers;

use App\Http\Requests\TreeStoreRequest;
use App\Models\Content;
use App\Models\Tree;
use App\Models\User;
use Illuminate\Http\Request;

class TreeController extends Controller
{
    public function index()
    {
        $c = Content::where('accessibility', true)->pluck('tree_id')->toArray();
        if (count($c) > 0) {
            $uName = User::find(Tree::find($c[0])->user_id)->name;
            $user = ['id' =>100500, 'name' => $uName, 'type' => 'folder', 'tree_id' => null];

            $all = array_merge($c, $this->uploadTree($c));
            // return $all;
            $tree = Tree::whereIn('id', $all)->get();
            // $tree = array_push($tree, $user);
            // $tree = $tree->toBase()->merge([$user]);
            return $tree->toArray();
            $tree = array_merge($user, $tree->toArray());
            return $tree;
        } else {
            return [];
        }
    }
    protected $fatherChild = [];
    public function uploadTree($childTree)
    {
        foreach ($childTree as $tr) {
            $trs = Tree::find($tr);
            if ($trs->tree_id) {
                array_push($this->fatherChild, $trs->tree_id);
                $this->uploadTree([$trs->tree_id]);
            } else {
                array_push(  $this->fatherChild, 0);
            }
        }
        return $this->fatherChild;
    }

    public function store(TreeStoreRequest $request)
    {
        if (isset($request->id)) {
            Tree::find($request->id)->update(["name" => $request->name]);
            return response()->json(['success' => true, 'message' => 'Название папки изменено']);
        } else {
            Tree::create([
                "name" => $request->name,
                'tree_id' => $request->tree_id == 'new' ? null : $request->tree_id,
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
            return response()->json(['success' => true, 'message' => 'Папка успешно удалена']);
        } else {
            return response()->json(['success' => false, 'message' => 'Данную папку нельзя удалить']);
        }
    }
}
