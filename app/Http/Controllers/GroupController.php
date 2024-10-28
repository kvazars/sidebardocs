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
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return ["groups" => GetGroupsResource::collection(Group::with("users")->get()), 
        "ceo" => User::where("role", "ceo")->get(), 
        "admin" => User::where("role", "admin")->get(),
        "system"=> About::find(1)
    ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(CreateGroupRequest $request)
    {
        Group::create([
            'name' => $request->name,
        ]);

        return response()->json(['success' => true, 'message' => 'Группа создана']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Group $group)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Group $group)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(GroupsUpdateRequest $request)
    {
        Group::find($request->id)->update([
            'name' => $request->name,
        ]);

        return response()->json(['success' => true, 'message' => 'Группа успешно обновлена']);
    }

    /**
     * Remove the specified resource from storage.
     */
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
