<?php

namespace App\Http\Controllers;

use App\Http\Requests\TreeStoreRequest;
use App\Models\About;
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
        $about = About::first();
        $content = Content::first();
        return response()->json(['success' => true, 'menu' => [], "about" => $about, "content" => $content]);
    }

    public function saveresourceadmin(Request $request)
    {
        Content::where("tree_id", $request->id)->update(["accessibility" => $request->accessibility, "accessibilitymanagers" => $request->accessibilitymanagers]);
        Available::where('tree_id', $request->id)->delete();
        if (!$request->accessibility) {
            foreach (json_decode($request->groups) as $available) {
                // return $available;
                if ($available->checked) {
                    Available::create(
                        [
                            'group_id' => $available->id,
                            'tree_id' => $request->id,
                        ]
                    );
                }
            }
        }
        return response()->json(['success' => true, 'message' => "Успешно"]);
    }

    public function upanddown($operation, Tree $id)
    {
        $trees = Tree::where("tree_id", $id->tree_id)->where("user_id", $id->user_id)->orderBy("position")->orderBy("id")->get();
        $treD = [];
        $newPosition = null;
        foreach ($trees as $num => $tr) {

            if ($id->id == $tr->id && !in_array($tr->id, $treD)) {
                $newPosition = $operation == 'up' ? $num + 1 : $num - 1;
                $newPosition = $newPosition < 0 ? 0 : ($newPosition >= count($trees) ? count($trees) - 1 : $newPosition);

                $tr->position = $newPosition;
                $tr->save();
                $idNext = $operation == 'up' ? $num + 1 : $num - 1;
                $elem = isset($trees[$idNext]);
                if ($elem) {
                    $idNext = $trees[$idNext]->id;
                    Tree::find($idNext)->update(["position" => $num]);
                    $treD[] = $tr->id;
                }
            }
            if (!in_array($tr->id, $treD)) {
                $treD[] = $tr->id;
                $tr->position = $num;
                $tr->save();
            }
        }
        return response()->json(['success' => true, 'message' => "Успешно перемещено"]);
    }
    public function userFolder()
    {
        $role = Auth::user()->role;
        $about = About::first();
        $content = Content::first();
        $tree = null;
        switch ($role) {
            case 'ceo':
                $tree = Tree::where("user_id", Auth::user()->id)->get();
                $managersFiles = [];
                $cd = Content::where('accessibilitymanagers', true)->pluck('tree_id')->toArray();

                foreach ($cd as $key => $c) {
                    
                    if (
                        Tree::where('id', $c)->where('user_id', Auth::user()->id)->first()
                    ) {
                        unset($cd[$key]);
                    }
                }

                if (count($cd) > 0) {
                    $all = array_merge($cd, $this->uploadTree($cd));
                    $s = Tree::whereIn('id', $all)->whereIn('user_id', User::pluck('id')->toArray())->get();
                    $users = User::whereIn('id', $s->pluck("user_id")->toArray())->get()->keyBy("id");

                    $userArray = [];
                    foreach ($users as $user) {
                        $userArray[] = ['id' => "user_" . $user->id, 'user_id' => $user->id, 'name' => $user->name, 'type' => 'folder', 'tree_id' => null];
                    }
                    $new_collection = collect($s)->map(function ($arr) use ($users) {
                        $arr['tree_id'] = $arr['tree_id'] ?: "user_" . $users[$arr->user_id]['id'];
                        return $arr;
                    });

                    $managersFiles = array_merge($userArray, $new_collection->toArray());
                    // return $tree;
                }
                $tree = array_merge($tree->toArray(), $managersFiles);
                break;
            case 'admin':
                $tree = Tree::whereIn('user_id', User::pluck('id')->toArray())->get();
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
                $c = [];
                if ($a) {
                    $c = Available::where('group_id', $a->group_id)->pluck('tree_id')->toArray();
                }
                $cd = Content::where('accessibility', true)->pluck('tree_id')->toArray();
                $c = array_merge($c, $cd);

                if (count($c) > 0) {
                    $all = array_merge($c, $this->uploadTree($c));
                    $tree = Tree::whereIn('id', $all)->whereIn('user_id', User::pluck('id')->toArray())->get();
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
        return response()->json(['success' => true, 'user' => ["id" => Auth::user()->id, "name" => Auth::user()->name, "role" => $role], 'menu' => $tree, "about" => $about, "content" => $content]);
    }
    protected $fatherChild = [];
    public function uploadTree($childTree)
    {
        foreach ($childTree as $tr) {
            $trs = Tree::find($tr);
            if ($trs) {
                if ($trs->tree_id) {
                    array_push($this->fatherChild, $trs->tree_id);
                    $this->uploadTree([$trs->tree_id]);
                } else {
                    array_push($this->fatherChild, 0);
                }
            }
        }
        return $this->fatherChild;
    }
    public function store(TreeStoreRequest $request)
    {
        if (isset($request->id)) {
            $tree = Tree::find($request->id);
            if ($tree->user_id == Auth::user()->id || Auth::user()->role == 'admin') {
                $tree->update(["name" => $request->name]);
                return response()->json(['success' => true, 'message' => 'Название папки изменено']);
            } else {
                return response()->json(['success' => false, 'message' => 'Доступ запрещен'], 403);
            }
        } else {
            $tree = $request->tree_id == 'new' ? null : Tree::find($request->tree_id);
            if ($tree) {
                if ($tree->user_id != Auth::user()->id) {
                    if (Auth::user()->role != 'admin') {
                        return response()->json(['success' => false, 'message' => 'Доступ запрещен'], 403);
                    }
                }
            }
            $user = explode("user_", $request->tree_id);
            $user1 = count($user) > 1 ? $user[1] : ($request->tree_id == 'new' ? Auth::user()->id : Tree::where('id', $request->tree_id)->first()->user_id);
            $position = Tree::where("tree_id", $request->tree_id == 'new' ? null : $request->tree_id)->count();
            Tree::create([
                "name" => $request->name,
                'tree_id' => $request->tree_id == 'new' || count($user) > 1  ? null : $request->tree_id,
                'user_id' => $user1,
                'position' => $position,
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
