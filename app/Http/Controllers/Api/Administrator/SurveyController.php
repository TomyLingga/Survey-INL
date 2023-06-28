<?php

namespace App\Http\Controllers\Api\Administrator;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\Question;
use App\Models\SurveyPertanyaan;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SurveyController extends Controller
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
            $surveys = Survey::with(['surveyPertanyaans' => function ($query) {
                $query->orderBy('order');
            }])->get();

            if ($surveys->isEmpty()) {

                return response()->json([
                    'message' => $this->messageMissing,
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            return response()->json([
                'data' => $surveys,
                'message' => $this->messageAll,
                'success' => true,
                'code' => 200
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'success' => false,
                'code' => 500
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $survey = Survey::with([
                'surveyPertanyaans' => function ($query) {
                    $query->orderBy('order')->with([
                        'questions' => function ($query) {
                            $query->orderBy('category_id')->with('options');
                        },
                        'questions.category',
                        'answers',
                        'answers.extraAnswers'
                    ]);
                }
            ])->find($id);

            if (!$survey) {
                return response()->json([
                    'message' => $this->messageMissing,
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            return response()->json([
                'survey' => $survey,
                'message' => $this->messageSuccess,
                'success' => true,
                'code' => 200
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'success' => false,
                'code' => 500
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'desc' => 'required',
                'from' => 'required',
                'to' => 'required',
                'order_sp.*' => 'required',
                'value.*' => 'required',
                'question_order.*' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            $data = Survey::create([
                'title' => $request->title,
                'desc' => $request->desc,
                'from' => $request->from,
                'to' => $request->to,
                'status' => '1',
            ]);

            $ordersSp = $request->order_sp;
            $values = $request->value;

            foreach ($values as $index => $arrayValue) {
                $array = [
                    'survey_id' => $data->id,
                    'order' => $ordersSp[$index],
                    'value' => $arrayValue,
                    'status' => '1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $sp = SurveyPertanyaan::create($array);

                $questionOrder = explode(";", $request->question_order);

                $qOrderIndex = explode("|", $questionOrder[$index]);

                if ($sp->order == $qOrderIndex[0]) {
                    $qId = explode(",", $qOrderIndex[1]);

                    $qId = array_map('intval', $qId);

                    $counter = 1;

                    foreach ($qId as $id) {
                        Question::findOrFail($id);
                        $sp->questions()->attach($id, ['order' => $counter]);
                        $counter++;
                    }
                }
            }

            DB::commit();

            return response()->json([
                'data' => $data,
                'message' => $this->messageCreate,
                'code' => 200,
                'success' => true,
            ], 200);

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
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'desc' => 'required',
                'from' => 'required',
                'to' => 'required',
                'order_sp.*' => 'required',
                'value.*' => 'required',
                'question_order.*' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            $survey = Survey::with('surveyPertanyaans')->findOrFail($id);
            $survey->title = $request->title ?? $survey->title;
            $survey->desc = $request->desc ?? $survey->desc;
            $survey->from = $request->from ?? $survey->from;
            $survey->to = $request->to ?? $survey->to;
            $survey->status = '1';
            $survey->save();

            $ordersSp = $request->order_sp;
            $values = $request->value;
            $surveyPertanyaanId = $request->survey_pertanyaan_id;

            $existingIds = $survey->surveyPertanyaans->pluck('id')->toArray();
            $deleteIds = array_diff($existingIds, $surveyPertanyaanId);

            foreach ($deleteIds as $deleteId) {
                $surveyPertanyaan = SurveyPertanyaan::find($deleteId);
                $surveyPertanyaan->questions()->detach();
            }

            $survey->surveyPertanyaans()->whereIn('id', $deleteIds)->delete();

            foreach ($values as $index => $arrayValue) {
                $array = [
                    'survey_id' => $survey->id,
                    'order' => $ordersSp[$index],
                    'value' => $arrayValue,
                    'status' => '1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (isset($surveyPertanyaanId[$index])) {
                    $sp = SurveyPertanyaan::find($surveyPertanyaanId[$index]);
                    if ($sp) {
                        $sp->update($array);
                    }
                } else {
                    $sp = SurveyPertanyaan::create($array);
                    $survey->surveyPertanyaans()->save($sp);
                }

                $questionOrder = explode(";", $request->question_order[$index]);
                $qOrderIndex = explode("|", $questionOrder[0]);

                if ($sp->order == $qOrderIndex[0]) {
                    $qIds = explode(",", $qOrderIndex[1]);
                    $qIds = array_map('intval', $qIds);

                    $counter = 1;

                    $sp->questions()->detach();

                    foreach ($qIds as $questionId) {
                        Question::findOrFail($questionId);
                        $sp->questions()->attach($questionId, ['order' => $counter]);
                        $counter++;
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => $this->messageUpdate,
                'code' => 200,
                'success' => true,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            DB::rollback();
            return response()->json([
                'message' => $this->messageMissing,
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

    public function toggleActive($id)
    {
        try {

            $data = Survey::findOrFail($id);

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
