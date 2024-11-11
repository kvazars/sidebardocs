<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateGroupRequest;
use App\Http\Requests\GroupsUpdateRequest;
use App\Http\Resources\GetGroupsResource;
use App\Models\About;
use App\Models\Group;
use App\Models\User;
use App\Models\UserGroups;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        return [
            "groups" => GetGroupsResource::collection(Group::with("users")->get()->sortBy("name")),
            "ceo" => User::withTrashed()->where("role", "ceo")->get(),
            "admin" => User::withTrashed()->where("role", "admin")->get(),
            "system" => About::find(1)
        ];
    }

    public function store(CreateGroupRequest $request)
    {
        Group::create([
            'name' => $request->name,
        ]);

        return response()->json(['success' => true, 'message' => 'Группа создана']);
    }


    public function update(GroupsUpdateRequest $request)
    {
        Group::find($request->id)->update([
            'name' => $request->name,
        ]);

        return response()->json(['success' => true, 'message' => 'Группа успешно обновлена']);
    }


    public function delete(Group $group)
    {
        $all = UserGroups::where("group_id", $group->id)->count();
        if ($all) {
            return response()->json(["success" => false, "message" => "Нельзя удалить группу, пока в ней есть люди"]);
        }
        $group->delete();
        return response()->json(["success" => true, "message" => "Группа успешно удалена"]);
    }
}
