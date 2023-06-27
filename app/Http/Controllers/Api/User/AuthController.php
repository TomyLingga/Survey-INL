<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json(['message' => 'Incorrect email or password', 'code' => 401], 401);
            }

            $user = User::where('email', $request->email)->firstOrFail();

            // Check if the user status != 0
            if ($user->status == 0) {
                return response()->json(['message' => 'Account is inactive', 'code' => 403], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login Successfully',
                'code' => 200,
                'data' => [
                    'accessToken' => $token,
                    'token_type' => 'Bearer',
                    'user' => $user
                ]
            ])->withHeaders([
                'Content-Type' => 'application/json;charset=utf-8',
                'Cookie' => 'token='.$token.'; HttpOnly; Max-Age=',
                'Access-Control-Allow-Origin' => '*'
            ]);
        } catch (\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'info' => 'Login Failed',
                'err' => $ex->getTrace()[0],
                'errMsg' => $ex->getMessage(),
                'code' => 500
            ], 500);
        }
    }

    public function logout()
    {
        Auth::user()->tokens()->delete();
        return response()->json(['message' => 'Logout Success','code' => 200
        ], 200);
    }

    public function update_password(Request $request){
        $mail = auth('sanctum')->user()->email;

        try {

            $validator = Validator::make($request->all(), [
                'password' => [
                    'required',
                    'min:8',
                    'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[^\w\s]).{8,}$/',
                ],
                'c-password' => 'required|same:password',
            ]);

            $validator->setCustomMessages([
                'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one symbol, and one number.',
                'c-password.same' => 'The confirm password must match the password.',
            ]);


            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'success' => false,
                    'code' => 400
                ], 400);
            }
            $data = User::where('email', $mail)->first();

            $data->update([
                'password' => Hash::make($request->password),
                'status' => '2',
                'updated_at' => Carbon::now()
            ]);

            return response()->json([
                'data' => $data,
                'message' => 'Data Updated Successfully',
                'success' => true,
                'code' => 200
            ], 200);
        } catch (\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'message' => 'Something went wrong',
                'err' => $ex->getTrace()[0],
                'errMsg' => $ex->getMessage(),
                'success' => false,
                'code' => 500
            ], 500);
        }
    }

}
