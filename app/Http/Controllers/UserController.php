<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Http\Requests\NewPasswordRequest;
use App\Http\Requests\RegistrationGroupRequest;
use App\Http\Requests\RegistrationRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\Group;
use App\Models\User;
use App\Models\UserGroups;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function update(UserUpdateRequest $request)
    {
        $user = User::find($request->id);
        $user->name = $request->name;
        $user->login = $request->login;
        if ($request->password) {
            $user->password = $request->password;
        }
        $user->save();
        return response()->json(['success' => true, 'message' => 'Пользователь успешно обновлен']);
    }
    public function store(RegistrationRequest $request)
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
            return response()->json(['success' => true, 'token' => $token->plainTextToken, 'name' => $user->name, 'role' => $user->role]);
        } else {
            return response()->json(['errors' => ["passsword" => ["Ошибка авторизации"]]]);
        }
    }
    public function authadmin(Request $request)
    {
        $user = User::find($request->user);
        $token = $user->createToken('api');
        return response()->json(['token' => $token->plainTextToken]);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(["success" => true]);
    }
    public function delete($id)
    {
        if ($id == 1) {
            return response()->json(["success" => false, "message" => "Запрещено удалять пользователя!"]);
        }
        $user = User::withTrashed()->find($id);
        $mess = '';
        if ($user->trashed()) {
            $user->restore();
            $mess = "Успешно восстановлен";
        } else {
            $user->delete();
            $mess = "Успешно удалён";
        }
        return response()->json(["success" => true, "message" => $mess]);
    }

    public function newPassword(NewPasswordRequest $request)
    {
        User::where('id', Auth::user()->id)->update([
            'password' => bcrypt($request->password),
        ]);

        return response()->json(['success' => true, 'message' => 'Пароль успешно обновлен']);
    }
}
