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

        if ($request->role == 'user') {
            $request->validate([
                'group_id' => 'required|exists:groups,id',
            ]);
        }

        $user = User::create([
            'name' => $request->name,
            'login' => $request->login,
            'password' => $request->password,
            'role' => $request->role,
        ]);

        if ($user->role == 'user') {
            UserGroups::create([
                'group_id' => $request->group_id,
                'user_id' => $user->id,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Новый пользователь был создан']);
    }

    public function auth(AuthRequest $request)
    {
        $user = User::where('login', $request->login)->first();
        if ($user and Hash::check($request->password, $user->password)) {
            $token = $user->createToken('api');
            return response()->json(['success' => true, 'token' => $token->plainTextToken, 'name' => $user->name, 'role' => $user->role,]);
        } else {
            return response()->json(['errors' => ["passsword" => ["Ошибка авторизации"]]]);
        }
    }
}
