<?php

namespace App\Http\Controllers;

use App\Http\Requests\TreeStoreRequest;
use App\Models\Available;
use App\Models\Content;
use App\Models\Group;
use App\Models\Tree;
use App\Models\User;
use App\Models\UserGroups;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TreeController extends Controller
{
    public function index()
    {
        $c = Content::where('accessibility', true)->pluck('tree_id')->toArray();
        if (count($c) > 0) {
            $all = array_merge($c, $this->uploadTree($c));
            $tree = Tree::whereIn('id', $all)->get();
            $users = User::whereIn('id', $tree->pluck("user_id")->toArray())->get()->keyBy("id");
            $userArray = [];
            foreach ($users as $user) {
                $userArray[] = ['id' => "user_" . $user->id, 'name' => $user->name, 'type' => 'folder', 'tree_id' => null];
            }
            $new_collection = collect($tree)->map(function ($arr) use ($users) {
                $arr['tree_id'] = $arr['tree_id'] ?: "user_" . $users[$arr->user_id]['id'];
                return $arr;
            });
            $tree = array_merge($userArray, $new_collection->toArray());
            return response()->json(['success' => true, 'menu' => $tree]);
        } else {
            return [];
        }
    }
    public function userFolder()
    {
        $role = Auth::user()->role;
        $tree = null;
        switch ($role) {
            case 'ceo':
                $tree = Tree::where("user_id", Auth::user()->id)->get();
                break;
            case 'admin':
                $tree = Tree::get();
                $users = User::whereIn('id', $tree->pluck("user_id")->toArray())->get()->keyBy("id");
                $userArray = [];
                foreach ($users as $user) {
                    $userArray[] = ['id' => "user_" . $user->id, 'name' => $user->name, 'type' => 'folder', 'tree_id' => null];
                }
                $new_collection = collect($tree)->map(function ($arr) use ($users) {
                    $arr['tree_id'] = $arr['tree_id'] ?: "user_" . $users[$arr->user_id]['id'];
                    return $arr;
                });
                $tree = array_merge($userArray, $new_collection->toArray());
                break;
            case 'user':
                $a = UserGroups::where("user_id", Auth::user()->id)->first("group_id");
                $c = Available::where('group_id', $a->group_id)->pluck('tree_id')->toArray();
                $cd = Content::where('accessibility', true)->pluck('tree_id')->toArray();
                $c = array_merge($c,$cd);

                if (count($c) > 0) {
                    $all = array_merge($c, $this->uploadTree($c));
                    $tree = Tree::whereIn('id', $all)->get();
                    $users = User::whereIn('id', $tree->pluck("user_id")->toArray())->get()->keyBy("id");
                    $userArray = [];
                    foreach ($users as $user) {
                        $userArray[] = ['id' => "user_" . $user->id, 'name' => $user->name, 'type' => 'folder', 'tree_id' => null];
                    }
                    $new_collection = collect($tree)->map(function ($arr) use ($users) {
                        $arr['tree_id'] = $arr['tree_id'] ?: "user_" . $users[$arr->user_id]['id'];
                        return $arr;
                    });
                    $tree = array_merge($userArray, $new_collection->toArray());
                }
                break;
        }
        return response()->json(['success' => true, 'user' => ["id" => Auth::user()->id, "name" => Auth::user()->name, "role" => $role], 'menu' => $tree]);
    }
    protected $fatherChild = [];
    public function uploadTree($childTree)
    {
        foreach ($childTree as $tr) {
            $trs = Tree::find($tr);
            if($trs){
            if ($trs->tree_id) {
                array_push($this->fatherChild, $trs->tree_id);
                $this->uploadTree([$trs->tree_id]);
            } else {
                array_push($this->fatherChild, 0);
            }}
        }
        return $this->fatherChild;
    }



    public function store(TreeStoreRequest $request)
    {
        if (isset($request->id)) {
            Tree::find($request->id)->update(["name" => $request->name]);
            return response()->json(['success' => true, 'message' => 'Название папки изменено']);
        } else {
            $user = explode("user_", $request->tree_id);
            $user1 = count($user) > 1 ? $user[1] : ($request->tree_id == 'new' ? Auth::user()->id : Tree::where('id', $request->tree_id)->first()->user_id);
            Tree::create([
                "name" => $request->name,
                'tree_id' => $request->tree_id == 'new' || count($user) > 1  ? null : $request->tree_id,
                'user_id' => $user1,
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
