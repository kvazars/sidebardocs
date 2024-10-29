<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
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
            return response()->json(['success' => true, 'token' => $token->plainTextToken, 'name' => $user->name, 'role' => $user->role,]);
        } else {
            return response()->json(['errors' => ["passsword" => ["Ошибка авторизации"]]]);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(["success" => true]);
    }
    public function delete(User $id)
    {
        if($id->id==1){
            return response()->json(["success" => false, "message"=>"Запрещено удалять пользователя!"]);

        }
        UserGroups::where("user_id",$id->id)->delete();
        $id->delete();
        return response()->json(["success" => true, "message"=>"Пользователь успешно удален"]);
    }

    
}
