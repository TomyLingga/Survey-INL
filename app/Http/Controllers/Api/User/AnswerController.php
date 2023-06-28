<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Survey;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AnswerController extends Controller
{
    private $messageFail = 'Something went wrong';
    private $messageMissing = 'Data not found in record';
    private $messageSuccess = 'Success to Fetch Data';

    private function isMultipleChoice($type)
    {
        return in_array($type, ['radio', 'checkbox', 'dropdown', 'range']);
    }

    public function store(Request $request, $idSurvey)
    {
        DB::beginTransaction();

        try{
            $validator = Validator::make($request->all(), [
                'answer' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            $survey = Survey::with('surveyPertanyaans')->findOrFail($idSurvey);
            $userId = Auth::user()->id;
            $createdAnswers = [];

            foreach ($request->answer as $keySp => $valueSp) {

                $spId = explode("-", $keySp)[1];
                $surveyPertanyaan = $survey->surveyPertanyaans->where('id', $spId)->first();

                if (!$surveyPertanyaan || $surveyPertanyaan->survey_id != $idSurvey) {
                    return response()->json([
                        'message' => 'SurveyPertanyaans not found or doesn`t belong to the correct Survey ',
                        'code' => 400,
                        'success' => false
                    ], 400);
                }

                $existingAnswers = Answer::where('user_id', $userId)
                                    ->where('survey_pertanyaan_id', $spId)
                                    ->count();
                if ($existingAnswers > 0) {
                    return response()->json([
                        'message' => 'You have already answered this survey.',
                        'code' => 400,
                        'success' => false
                    ], 400);
                }

                $arrayAnswer = explode(";", $valueSp["jawaban"]);
                $arrayExtraAnswers =[];

                foreach ($arrayAnswer as $valueAnswer) {
                    $answer = explode("|", $valueAnswer);

                    $questionData = Question::findOrFail($answer[0]);

                    if ($this->isMultipleChoice($questionData->type)) {
                        $option = Option::findOrFail($answer[1]);

                        if ($option->extra_answer == 1) {
                            $dataNews = [];
                            foreach ($valueSp["extra"] as $keyExtra => $valueExtra) {

                                $extraId = explode("-", $keyExtra)[1];

                                $dataNews[] = [$extraId, $valueExtra];
                            }

                            $arrayExtraAnswers = $dataNews;
                        }
                    }
                }

                $answerData = [
                    'user_id' => $userId,
                    'survey_pertanyaan_id' => intval($spId),
                    'answer' => $valueSp["jawaban"],
                ];

                $answer = Answer::create($answerData);

                foreach ($arrayExtraAnswers as $value) {
                    $dataExtra = [
                        'answer_id' => $answer->id,
                        'option_id' => $value[0],
                        'value' => $value[1]
                    ];

                    $answer->extraAnswers()->create($dataExtra);
                }

                $createdAnswers[] = $answer->load('extraAnswers');
            }

            DB::commit();

            return response()->json([
                'message' => 'Answers created successfully',
                'success' => true,
                'code' => 200,
                'data' => $createdAnswers
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            DB::rollback();
            return response()->json([
                'message' => "Data Not Found",
                'err' => $ex->getTrace()[0],
                'errMsg' => $ex->getMessage(),
                'code' => 401,
                'success' => false,
            ], 401);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => "Something went wrong",
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'code' => 500,
                'success' => false,
            ], 500);
        }
    }

    public function index($id)
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
}
