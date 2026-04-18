<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Http\Requests\NewPasswordRequest;
use App\Http\Requests\RegistrationRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use App\Models\UserGroups;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function update(UserUpdateRequest $request)
    {
        $user = User::find($request->id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь не найден',
            ], 404);
        }

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
        if ($user && Hash::check($request->password, $user->password)) {
            $token = $user->createToken('api');
            return response()->json(['success' => true, 'token' => $token->plainTextToken, 'name' => $user->name, 'role' => $user->role]);
        } else {
            return response()->json(['errors' => ["password" => ["Ошибка авторизации"]]]);
        }
    }
    public function authadmin(Request $request)
    {
        $validated = $request->validate([
            'user' => 'required|integer|exists:users,id',
        ]);

        $currentUser = $request->user();
        if (!$currentUser || $currentUser->role !== 'admin') {
            throw ValidationException::withMessages([
                'user' => 'Недостаточно прав для авторизации от имени другого пользователя.',
            ]);
        }

        $user = User::find($validated['user']);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь не найден',
            ], 404);
        }

        $token = $user->createToken('admin-impersonation');
        return response()->json([
            'success' => true,
            'token' => $token->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
        ]);
    }
    public function logout(Request $request)
    {
        if ($request->user()?->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }
        return response()->json(["success" => true]);
    }
    public function delete($id)
    {
        if ($id == 1) {
            return response()->json(["success" => false, "message" => "Запрещено удалять пользователя!"]);
        }
        $user = User::withTrashed()->find($id);
        if (!$user) {
            return response()->json([
                "success" => false,
                "message" => "Пользователь не найден",
            ], 404);
        }
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
