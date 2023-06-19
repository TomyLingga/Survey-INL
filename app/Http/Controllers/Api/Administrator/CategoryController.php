<?php

namespace App\Http\Controllers\Api\Administrator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;


class CategoryController extends Controller
{
    private $messageFail = 'Something went wrong';
    private $messageMissing = 'Data not found in record';
    private $messageAll = 'Success to Fetch All Datas';
    private $messageSuccess = 'Success to Fetch Data';
    private $messageCreate = 'Success to Create Data';
    private $messageUpdate = 'Success to Update Data';

    public function index()
    {
        try {
            $categories = Category::with('question')->get();

            if ($categories->isEmpty()) {

                return response()->json([
                    'message' => $this->messageMissing,
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            return response()->json([
                'data' => $categories,
                'message' => $this->messageAll,
                'success' => true,
                'code' => 200
            ], 200);
        } catch (\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'message' => $this->messageFail,
                'success' => false,
                'code' => 500
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $data = Category::with('question')->find($id);

            if (!$data) {

                return response()->json([
                    'message' => $this->messageMissing,
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            return response()->json([
                'data' => $data,
                'message' => $this->messageSuccess,
                'success' => true,
                'code' => 200
            ], 200);
        } catch (\Illuminate\Database\QueryException $ex) {

            return response()->json([
                'message' => $this->messageFail,
                'success' => false,
                'code' => 500
            ], 500);
        }
    }

    private function validateForm(Request $request)
    {
        $rules = [
            'name' => 'required',
        ];

        $messages = [
            'name.required' => 'Name is Required.',
        ];

        return $this->validate($request, $rules, $messages);
    }

    public function store(Request $request)
    {
        try {
            $this->validateForm($request);

            $data = Category::create([
                'name' => $request->name,
                'status' => '1',
            ]);

            return response()->json([
                'data' => $data,
                'message' => $this->messageCreate,
                'code' => 200,
                'success' => true
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $this->messageFail,
                'err' => $e,
                'code' => 500,
                'success' => false
            ], 500);
        }

    }

    public function update(Request $request, $id)
    {
        try {
            $data = Category::findOrFail($id);
            $name = $request->get('name', $data->name);

            $data->update([
                'name' => $name,
            ]);

            return response()->json([
                'data' => $data,
                'message' => $this->messageUpdate,
                'code' => 200,
                'success' => true
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {

            return response()->json([
                'message' => $this->messageMissing,
                'code' => 401,
                'success' => false
            ], 401);
        } catch (\Illuminate\Database\QueryException $ex) {

            return response()->json([
                'message' => $this->messageFail,
                'code' => 500,
                'success' => false
            ], 500);
        }
    }

    public function toggleActive($id)
    {
        try {

            $data = Category::findOrFail($id);

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
                'message' => $this->messageFail,
                'code' => 500,
                'success' => false
            ], 500);
        }
    }
}
