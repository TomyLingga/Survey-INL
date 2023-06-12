<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{   
    public function login(Request $request)
    {  
        try{
            if (! Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'message' => 'Unauthorized',
                    'code' => 401
                ], 401);
            }

            $user = User::where('email', $request->email)->firstOrFail();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json(['message' => 'Login Successfully','code' => 200, 'data' => ['accessToken' => $token,'token_type' => 'Bearer', 'user' => $user]])
                            ->withHeaders([
                                'Content-Type' => 'application/json;charset=utf-8',
                                'Cookie' => 'token='.$token.'; HttpOnly; Max-Age=',
                                'Access-Control-Allow-Origin' => '*'
            ]);
        }catch (\Illuminate\Database\QueryException $ex) {
            // var_dump($ex->errorInfo);
            return response()->json(['info' => 'Login Failed', 'code' => 500], 500);
        }
    }

    public function logout()
    {
        Auth::user()->tokens()->delete();
        return response()->json([
            'message' => 'Logout Success',
            'code' => 200
        ], 200);
    }

}