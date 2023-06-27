<?php

namespace App\Http\Controllers\Api\Administrator;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class UserController extends Controller
{
    public function index()
    {
        $limit = request('limit', 10); // default limit is 10 if not provided
        $offset = request('offset', 0); // default offset is 0 if not provided

        try {

            $data = User::skip($offset)->take($limit)->orderBy('name')->get();

            if ($data->isEmpty()) {

                return response()->json([
                    'message' => 'Data not found',
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            return response()->json([
                'data' => $data,
                'message' => 'Success to Fetch All Datas',
                'success' => true,
                'code' => 200
            ], 200);

        }catch (\Illuminate\Database\QueryException $ex) {

            return response()->json([
                'message' => 'Something went wrong',
                'err' => $ex->getTrace()[0],
                'errMsg' => $ex->getMessage(),
                'success' => false,
                'code' => 500
            ], 500);
        }
    }

    public function show($id)
    {
        try{
            $data = User::where('id', $id)->first();

            if (!$data) {

                return response()->json([
                    'message' => 'Data not found in record',
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            return response()->json([
                'data' => $data,
                'message' => 'Success to Fetch All Datas',
                'success' => true,
                'code' => 200
            ], 200);

        }catch (\Illuminate\Database\QueryException $ex) {

            return response()->json([
                'message' => 'Something went wrong',
                'err' => $ex->getTrace()[0],
                'errMsg' => $ex->getMessage(),
                'success' => false,
                'code' => 500
            ], 500);
        }
    }

    public function user_store(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            $existingEmail = User::where('email', $request->email)->first();

            if ($existingEmail) {

                return response()->json([
                    'message' => 'Email already used',
                    'code' => 409,
                    'success' => false
                ], 409);
            }

            $MasterUser = User::create([
                'name'                  => $request->name,
                'email'                 => $request->email,
                'password'              => Hash::make('rahasia123'),
                'status'                => '1',
                'created_at'            => Carbon::now()
            ]);

            return response()->json([
                'data' => $MasterUser,
                'message' => 'Data Created Successfully.',
                'code' => 200,
                'success' => true
            ], 200);

        } catch (QueryException $ex) {

            return response()->json([
                'message' => 'Failed to create data',
                'err' => $ex->getTrace()[0],
                'errMsg' => $ex->getMessage(),
                'code' => 500,
                'success' => false
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data = User::findOrFail($id);

            $name = $request->input('name', $data->name);
            $email = $request->input('email', $data->email);

            $data->update([
                'name' => $name,
                'email' => $email,
            ]);

            return response()->json([
                'data' => $data,
                'message' => 'Data Updated Successfully',
                'code' => 200,
                'success' => true
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response()->json([
                'message' => 'Data not found on record',
                'err' => $ex->getTrace()[0],
                'errMsg' => $ex->getMessage(),
                'code' => 401,
                'success' => false
            ], 401);
        } catch (\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'message' => 'Something went wrong',
                'err' => $ex->getTrace()[0],
                'errMsg' => $ex->getMessage(),
                'code' => 500,
                'success' => false
            ], 500);
        }
    }

    public function reset_password($id)
    {
        try {

            $data = User::where('id', $id)->first();

            if (!$data) {

                return response()->json([
                    'message' => 'Record not found.',
                    'code' => 401,
                    'success' => false
                ], 401);
            }

            $data->update([
                'password' => Hash::make('rahasia123')
            ]);

            return response()->json([
                'message' => 'Password Reset Succesfully',
                'code' => 200,
                'success' => true
            ], 200);

        } catch (QueryException $ex) {

            return response()->json([
                'message' => 'Failed to reset password',
                'err' => $ex->getTrace()[0],
                'errMsg' => $ex->getMessage(),
                'code' => 500,
                'success' => false
            ], 500);
        }
    }

    public function toggleActive($id)
    {
        try {

            $data = User::findOrFail($id);

            $newStatusValue = ($data->status == 0) ? 2 : 0;

            $data->update([
                'status' => $newStatusValue,
            ]);

            $message = ($newStatusValue == 0) ? 'Status updated to Non-Active.' : 'Status updated to Active.';

            return response()->json([
                'data' => $data,
                'message' => $message,
                'code' => 200,
                'success' => true
            ], 200);

        } catch (ModelNotFoundException $e) {

            return response()->json([
                'message' => 'Record not found.',
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'code' => 401,
                'success' => false
            ], 401);

        } catch (QueryException $ex) {

            return response()->json([
                'message' => 'Failed to Update',
                'err' => $ex->getTrace()[0],
                'errMsg' => $ex->getMessage(),
                'code' => 500,
                'success' => false
            ], 500);
        }
    }
}
