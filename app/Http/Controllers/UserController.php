<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Http\Requests\RegistrationGroupRequest;
use App\Http\Requests\RegistrationRequest;
use App\Models\Group;
use App\Models\User;
use App\Models\UserGroups;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function reg(RegistrationRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'login' => $request->login,
            'password' => $request->password,
            'role' => $request->role,
        ]);

        if ($user->role == 'user') {
            if (isset($request->group_id)) {
                $request->validate([
                    'group_id' => 'required|exists:user_groups,group_id',
                ]);
                UserGroups::create([
                    'group_id' => $request->group_id,
                    'user_id' => $user->id,
                ]);
            } else {

            }
        }

        return response()->json(['success' => true, 'message' => 'Новый пользователь был создан']);
    }

    public function login(AuthRequest $request)
    {
        $user = User::where('login', $request->login)->first();
        if ($user and Hash::check($request->password, $user->password)) {
            $token = $user->createToken('api');
            return response()->json(['success' => true, 'token' => $token]);
        } else {
            return response()->json(['success' => false, 'message' => 'Ошибка авторизации']);
        }
    }
}
