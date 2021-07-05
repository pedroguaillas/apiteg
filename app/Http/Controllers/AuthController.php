<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use App\User;

class AuthController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function register(Request $request)
    {
        $name = $request->input('name');
        $user = $request->input('user');
        $email = $request->input('email');
        $rol = $request->input('rol');
        $password = Hash::make($request->input('password'));

        $register = User::create([
            'name' => $name,
            'user' => $user,
            'rol' => $rol,
            'email' => $email,
            'password' => $password
        ]);

        if ($register) {
            return response()->json([
                'success' => true,
                'message' => 'Register Success!',
                'data' => $register,
            ], 201);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Register Fail!',
                'data' => '',
            ], 401);
        }
    }

    public function login(Request $request)
    {
        //validate incoming request 
        $this->validate($request, [
            'user' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['user', 'password']);

        // if (!$token = JWTAuth::attempt($credentials)) {
        if (!$token = Auth::attempt($credentials)) {
            return response()->json(['message' => 'Usuario o contraseña incorrecto'], 401);
        }

        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'user' => JWTAuth::user()
        ]);
        // return $this->respondWithToken($token);
    }

    public function refreshToken()
    {
        $refreshed = JWTAuth::refresh(JWTAuth::getToken());
        JWTAuth::setToken($refreshed)->toUser();
        return response()->json([
            'token' => $refreshed
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return response()->json(['logout' => true]);
    }
}
