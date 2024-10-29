<?php

namespace App\Http\Controllers;

use App\Models\Available;
use App\Models\Group;
use Illuminate\Http\Request;

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
}
