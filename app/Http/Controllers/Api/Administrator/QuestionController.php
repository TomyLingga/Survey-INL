<?php
namespace App\Http\Controllers\Api\Administrator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Option;
use App\Models\Category;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
            $questions = Question::with('options')->get();

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
                'err' => $ex->getTrace()[0],
                'errMsg' => $ex->getMessage(),
                'success' => false,
                'code' => 500
            ], 500);
        }
    }

    public function show($id)
    {
        try {

            $question = Question::with('options')->find($id);

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

        } catch (\Illuminate\Database\QueryException $ex) {

            return response()->json([
                'message' => $this->messageFail,
                'err' => $ex->getTrace()[0],
                'errMsg' => $ex->getMessage(),
                'success' => false,
                'code' => 500
            ], 500);
        }
    }

    private function isMultipleChoice($type)
    {
        return in_array($type, ['radio', 'checkbox', 'dropdown', 'range']);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            $validator = Validator::make($request->all(), [
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

            $data = Question::create([
                'category_id' => '1',
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

            $data->load('options');

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
                'err' => $ex->getTrace()[0],
                'errMsg' => $ex->getMessage(),
                'code' => 401,
                'success' => false,
            ], 401);
        } catch (\Exception $e) {

            DB::rollback();

            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'code' => 500,
                'success' => false,
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
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

            $question = Question::with('options')->findOrFail($id);

            $question->update([
                'category_id' => '1',
                'question' => $request->question,
                'type' => $request->type,
                'require' => $request->require,
                'status' => '1',
            ]);

            if ($this->isMultipleChoice($request->type)) {
                $validator = Validator::make($request->all(), [
                    'option_id.*' => 'required',
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

                $values = $request->value;
                $descs = $request->desc;
                $extras = $request->extra;
                $optionIds = $request->option_id;

                foreach ($values as $index => $value) {
                    $optionData = [
                        'question_id' => $question->id,
                        'value' => $value,
                        'description' => $descs[$index],
                        'extra_answer' => $extras[$index],
                        'status' => '1',
                        'updated_at' => now(),
                    ];

                    if (isset($optionIds[$index])) {
                        Option::where('id', $optionIds[$index])->update($optionData);
                    } else {
                        $option = Option::create($optionData);
                        $question->options()->save($option);
                    }
                }
            } else {
                $question->options()->delete();
            }

            $question->load('options');

            return response()->json([
                'data' => $question,
                'message' => $this->messageUpdate,
                'code' => 200,
                'success' => true,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response()->json([
                'message' => 'Data not found.',
                'err' => $ex->getTrace()[0],
                'errMsg' => $ex->getMessage(),
                'code' => 401,
                'success' => false,
            ], 401);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
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
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'code' => 500,
                'success' => false
            ], 500);
        }
    }
}
