<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateGroupRequest;
use App\Http\Resources\GetGroupsResource;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return GetGroupsResource::collection(Group::with("users")->get());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(CreateGroupRequest $request)
    {
        Group::create([
            'name' => $request->name,
            'description' => '',
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
    public function update(Request $request, Group $group)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Group $group)
    {
        //
    }
}
