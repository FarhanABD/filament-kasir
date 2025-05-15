<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
//    API Login
    public function login(Request $request) {
        $request->validate([
           'email' => 'required|email', 
           'password' => 'required', 
        ]);
        $user = User::where('email', $request->email)->first();
        if(!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
              'success' => false,
              'message' => 'Email atau password salah',
              'data' => null
            ],422);
        }
        $token = $user->createToken('API Token')->plainTextToken;
        return response()->json([
          'success' => true,
           'message' => 'Login berhasil',
           'data' => [
             'access_token' => $token,
             'token_type' => 'Bearer',
             'user' => $user
           ]
           ]);
    }
}