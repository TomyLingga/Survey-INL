<?php

namespace App\Http\Controllers\Api\Administrator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Option;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class QuestionController extends Controller
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
            $questions = Question::with('category', 'option')->get();

            if ($questions->isEmpty()) {
                return response()->json([
                    'message' => $this->messageMissing,
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            return response()->json([
                'data' => $questions,
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

            $question = Question::with('category', 'option')->find($id);

            if (!$question) {

                return response()->json([
                    'message' => $this->messageMissing,
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            return response()->json([
                'data' => $question,
                'message' => $this->messageSuccess,
                'success' => true,
                'code' => 200
            ], 200);

        }catch (\Illuminate\Database\QueryException $ex) {

            return response()->json([
                'message' => $this->messageFail,
                'success' => false,
                'code' => 500
            ], 500);
        }
    }

    private function isMultipleChoice($type)
    {
        return in_array($type, ['radio', 'checkbox', 'dropdown', 'range']);
    }

    // private function validateForm(Request $request)
    // {
    //     $rules = [
    //         'category_id' => 'required',
    //         'question' => 'required',
    //         'type' => 'required|in:text,number,file,radio,checkbox,dropdown,range',
    //         'require' => 'required',
    //         'value.*' => 'required_if:type,radio,checkbox,dropdown,range',
    //         'desc.*' => 'required_if:type,radio,checkbox,dropdown,range',
    //         'extra.*' => 'required_if:type,radio,checkbox,dropdown,range',
    //     ];

    //     $messages = [
    //         'category_id.required' => 'The category field is required.',
    //         'question.required' => 'The question field is required.',
    //         'type.required' => 'The type field is required.',
    //         'type.in' => 'Invalid type value. Valid types are: text, number, file, radio, checkbox, dropdown, range.',
    //         'require.required' => 'The require field is required.',
    //         'value.*.required_if' => 'The value field is required for the selected question type.',
    //         'desc.*.required_if' => 'The description field is required for the selected question type.',
    //         'extra.*.required_if' => 'The extra_answer field is required for the selected question type.',
    //     ];

    //     // return $this->validate($request, $rules, $messages);

    //     $validator = Validator::make($request->all(), $rules, $messages);

    //     if ($validator->fails()) {
    //         throw new ValidationException($validator);
    //     }
    // }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // $this->validateForm($request);

            $validator = Validator::make($request->all(), [
                'category_id' => 'required',
                'question' => 'required',
                'type' => 'required|in:text,number,file,radio,checkbox,dropdown,range',
                'require' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            $category = Category::findOrFail($request->category_id);

            $data = Question::create([
                'category_id' => $request->category_id,
                'question' => $request->question,
                'type' => $request->type,
                'require' => $request->require,
                'status' => '1',
            ]);

            if ($this->isMultipleChoice($request->type)) {
                $validator = Validator::make($request->all(), [
                    'value' => 'required',
                    'desc' => 'required',
                    'extra' => 'required',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'message' => $validator->errors(),
                        'code' => 400,
                        'success' => false
                    ], 400);
                }

                $options = [];

                $values = $request->value;
                $descs = $request->desc;
                $extras = $request->extra;

                foreach ($values as $index => $value) {
                    $array = [
                        'question_id' => $data->id,
                        'value' => $value,
                        'description' => $descs[$index],
                        'extra_answer' => $extras[$index],
                        'status' => '1',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $options[] = $array;
                }

                Option::insert($options);
            }

            $data->load('option');

            DB::commit();

            return response()->json([
                'data' => $data,
                'message' => $this->messageCreate,
                'code' => 200,
                'success' => true,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {

            DB::rollback();

            return response()->json([
                'message' => 'Category not found.',
                'code' => 401,
                'success' => false,
            ], 401);
        } catch (\Exception $e) {

            DB::rollback();

            return response()->json([
                'message' => $this->messageFail,
                'err' => $e,
                'code' => 500,
                'success' => false,
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            // $this->validateForm($request);

            $validator = Validator::make($request->all(), [
                'category_id' => 'required',
                'question' => 'required',
                'type' => 'required|in:text,number,file,radio,checkbox,dropdown,range',
                'require' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            $category = Category::findOrFail($request->category_id);

            $question = Question::findOrFail($id);

            $question->category_id = $request->category_id;
            $question->question = $request->question;
            $question->type = $request->type;
            $question->require = $request->require;
            $question->status = '1';
            $question->save();

            if ($this->isMultipleChoice($request->type)) {
                $validator = Validator::make($request->all(), [
                    'value.*' => 'required',
                    'desc.*' => 'required',
                    'extra.*' => 'required',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'message' => $validator->errors(),
                        'code' => 400,
                        'success' => false
                    ], 400);
                }

                $options = [];

                $values = $request->value;
                $descs = $request->desc;
                $extras = $request->extra;

                foreach ($values as $index => $value) {
                    $array = [
                        'question_id' => $question->id,
                        'value' => $value,
                        'description' => $descs[$index],
                        'extra_answer' => $extras[$index],
                        'status' => '1',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $options[] = $array;
                }

                $question->option()->delete(); // Delete existing options
                $question->option()->createMany($options); // Insert new options
            } else {
                $question->option()->delete(); // Delete existing options for non-multiple choice
            }

            $question->load('option');

            DB::commit();

            return response()->json([
                'data' => $question,
                'message' => $this->messageUpdate,
                'code' => 200,
                'success' => true,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            DB::rollback();
            return response()->json([
                'message' => 'Category not found.',
                'code' => 401,
                'success' => false,
            ], 401);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e,
                'code' => 500,
                'success' => false,
            ], 500);
        }
    }

    public function toggleActive($id)
    {
        try {

            $data = Question::findOrFail($id);

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
