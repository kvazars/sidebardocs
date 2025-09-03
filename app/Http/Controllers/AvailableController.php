<?php

namespace App\Http\Controllers;

use App\Models\Available;
use App\Models\Group;
use App\Models\Tree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AvailableController extends Controller
{
    public function index()
    {
        $all = Group::get();
        foreach ($all as $value) {
            $availablesGroups[] = ['id' => $value->id, 'name' => $value->name, 'checked' => false];
        }
        return response()->json(['success' => true, 'groups' => $availablesGroups]);
    }
    public function clearmyaccessfiles()
    {
        $tree = Tree::where("user_id", Auth::user()->id)->pluck('id');
        Available::whereIn('tree_id', $tree)->delete();
        return response()->json(['success' => true, 'message' => 'Успешно сброшены все доступы']);
    }
}
