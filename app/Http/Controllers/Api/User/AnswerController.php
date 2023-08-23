<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\ExtraAnswer;
use App\Models\Survey;
use App\Models\User;
use App\Models\SurveyPertanyaan;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnswerController extends Controller
{
    public function store(Request $request, $surveyId)
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

            $survey = Survey::with('surveyPertanyaans')->findOrFail($surveyId);
            if (Carbon::now()->isBefore($survey->from)) {
                return response()->json([
                    'message' => 'Survey has not started yet.',
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            if (Carbon::now()->isAfter($survey->to)) {
                return response()->json([
                    'message' => 'Survey has ended.',
                    'code' => 400,
                    'success' => false
                ], 400);
            }
            $userId = Auth::user()->id;
            $createdAnswers = [];

            foreach ($request->answer as $keySp => $valueSp) {

                $spId = explode("-", $keySp)[1];
                $surveyPertanyaan = $survey->surveyPertanyaans->where('id', $spId)->first();

                if (!$surveyPertanyaan || $surveyPertanyaan->survey_id != $surveyId) {
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
                        $optionIds = explode("~", $answer[1]);

                        foreach ($optionIds as $optionId) {
                            $option = Option::where('id', $optionId)
                                    ->where('question_id', $questionData->id)
                                    ->first();

                            if (!$option) {

                                return response()->json([
                                    'message' => 'Option not found or doesn`t belong to the correct Question',
                                    'error' => "Option ID $optionId doesn't belong to the Question ID $questionData->id",
                                    'code' => 400,
                                    'success' => false
                                ], 400);
                            }

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

    private function isMultipleChoice($type)
    {
        return in_array($type, ['radio', 'checkbox', 'dropdown', 'range']);
    }

    private function multipleChoiceExceptCheckbox($type)
    {
        return in_array($type, ['radio', 'dropdown', 'range']);
    }

    public function getIndividualSurveyAnswer(Request $request, $surveyId)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
                'code' => 400,
                'success' => false
            ], 400);
        }

        $userId = $request->user_id;
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $survey = Survey::find($surveyId);

        if (!$survey) {
            return response()->json(['error' => 'Survey not found'], 404);
        }

        $surveyPertanyaans = SurveyPertanyaan::where('survey_id', $surveyId)->orderBy('order')->get();

        $surveyPertanyaanResponses = [];

        foreach ($surveyPertanyaans as $surveyPertanyaan) {
            $answerPairs = json_decode($surveyPertanyaan->answers, true);

            $filteredAnswerPairs = array_filter($answerPairs, function ($answerPair) use ($userId) {
                return $answerPair['user_id'] == $userId;
            });

            if (!$filteredAnswerPairs) {
                return response()->json(['error' => 'This user not yet answer this Survey'], 404);
            }

            $surveyPertanyaanId = $surveyPertanyaan->id;
            $surveyPertanyaanValue = $surveyPertanyaan->value;

            $questions = $this->getValueOfAnswer($filteredAnswerPairs);

            $combinedQuestions = [];
            foreach ($questions as $question) {
                $questionId = $question['questionId'];
                $questionValue = $question['questionValue'];
                $answers = $question['answers'];

                if (!isset($combinedQuestions[$questionId])) {
                    $combinedQuestions[$questionId] = [
                        'questionId' => $questionId,
                        'questionValue' => $questionValue,
                        'answers' => [$answers],
                    ];
                } else {
                    $combinedQuestions[$questionId]['answers'][] = $answers;
                }
            }

            $surveyPertanyaanResponses[] = [
                'id' => $surveyPertanyaanId,
                'value' => $surveyPertanyaanValue,
                'questions' => array_values($combinedQuestions),
            ];
        }

        $response = [
            'survey' => $survey,
            'survey_pertanyaans' => $surveyPertanyaanResponses,
        ];

        return response()->json($response);
    }

    public function getSurveyAnswers($surveyId)
    {
        $survey = Survey::find($surveyId);

        if (!$survey) {
            return response()->json(['error' => 'Survey not found'], 404);
        }

        $surveyPertanyaans = SurveyPertanyaan::where('survey_id', $surveyId)->orderBy('order')->get();
        $surveyPertanyaanResponses = [];

        $totalAverage = 0;
        $totalCount = 0;

        foreach ($surveyPertanyaans as $surveyPertanyaan) {
            $answerPairs = json_decode($surveyPertanyaan->answers, true);
            $surveyPertanyaanId = $surveyPertanyaan->id;
            $surveyPertanyaanValue = $surveyPertanyaan->value;

            $questions = $this->getValueOfAnswer($answerPairs);

            // dd($questions);
            $combinedQuestions = [];

            foreach ($questions as $question) {
                $questionId = $question['questionId'];
                $questionValue = $question['questionValue'];
                $questionType = $question['questionType'];
                $answers = $question['answers'];

                if (!isset($combinedQuestions[$questionId])) {
                    $arraySementara = [
                        'questionId' => $questionId,
                        'questionType' => $questionType,
                        'questionValue' => $questionValue,
                        'answers' => [$answers],
                        'persentasi' => isset($question['persentasi']) ? $question['persentasi'] : null
                    ];
                    $combinedQuestions[$questionId] = $arraySementara;
                } else {
                    $combinedQuestions[$questionId]['answers'][] = $answers;
                }
            }

            foreach ($combinedQuestions as &$combinedQuestion) {
                if ($combinedQuestion['questionType'] === 'range') {
                    $questionId = $combinedQuestion['questionId'];
                    $questionValueSum = 0;
                    $questionValueCount = count($combinedQuestion['answers']);

                    foreach ($combinedQuestion['answers'] as $answer) {
                        $questionValueSum += $answer['value'];
                    }

                    $averageQuestionValue = $questionValueCount > 0 ? $questionValueSum / $questionValueCount : 0;
                    $roundedAverageQuestionValue = round($averageQuestionValue, 0, PHP_ROUND_HALF_UP);
                    $combinedQuestion['averageQuestionValue'] = $roundedAverageQuestionValue;

                    $totalAverage += $roundedAverageQuestionValue;
                    $totalCount++;
                }
            }

            $surveyPertanyaanResponses[] = [
                'id' => $surveyPertanyaanId,
                'value' => $surveyPertanyaanValue,
                'questions' => array_values($combinedQuestions),
            ];
        }

        $surveyAverage = $totalCount > 0 ? $totalAverage / $totalCount : 0;

        $response = [
            'survey' => $survey,
            'survey_pertanyaans' => $surveyPertanyaanResponses,
            'averageValue' => $surveyAverage,
        ];

        return response()->json($response);
    }

    private function getValueOfAnswer($answerPairs)
    {
        $questions = [];

        foreach ($answerPairs as $answerPair) {
            $answerId = $answerPair['id'];
            $answerUserId = $answerPair['user_id'];

            $user = User::find($answerUserId);
            $userName = $user->name;

            $answer = $answerPair['answer'];
            $answers = explode(';', $answer);
            $surveyPertanyaanId = $answerPair['survey_pertanyaan_id'];

            foreach ($answers as $answer) {
                $parts = explode('|', $answer);
                $questionId = $parts[0];
                $answerValue = $parts[1];

                $percentageResults = [];

                $question = Question::find($questionId);
                $questionText = $question->question;
                $questionType = $question->type;

                if ($this->isMultipleChoice($questionType)) {
                    $questionOptions = Option::where('question_id', $questionId)->get();
                    $totalAnswersCount = Answer::where('survey_pertanyaan_id', $surveyPertanyaanId)->count();
                    $multiCheckboxValues = explode("~", $answerValue);

                    foreach ($multiCheckboxValues as $multiCheckboxValue) {
                        $selectedOption = Option::find($multiCheckboxValue);
                        $selectedOptionId = $selectedOption->id;
                        $selectedOptionValue = $selectedOption->value;
                        $selectedOptionDescription = $selectedOption->description;

                        $extraAnswers = ExtraAnswer::where('answer_id', $answerId)
                            ->where('option_id', $selectedOptionId)
                            ->first();

                        $multiCheckboxValue = [
                            'id' => $answerId,
                            'user_name' => $userName,
                            'optionId' => $selectedOptionId,
                            'value' => $selectedOptionValue,
                            'description' => $selectedOptionDescription,
                            'extraAnswers' => $extraAnswers,
                        ];

                        if ($this->multipleChoiceExceptCheckbox($questionType)) {
                            foreach ($questionOptions as $questionOption) {
                                $optionId = $questionOption->id;
                                $optionText = $questionOption->description;

                                if ($answer === end($answers)) {
                                    $answerCount = Answer::where('survey_pertanyaan_id', $surveyPertanyaanId)
                                        ->where('answer', 'ILIKE', "%$questionId|$optionId")
                                        ->count();
                                } else {
                                    $answerCount = Answer::where('survey_pertanyaan_id', $surveyPertanyaanId)
                                        ->where('answer', 'ILIKE', "%$questionId|$optionId;%")
                                        ->count();
                                }

                                $optionPercentage = $totalAnswersCount > 0 ? ($answerCount / $totalAnswersCount) * 100 : 0;

                                $percentageResults[] = [
                                    'optionId' => $optionId,
                                    'optionText' => $optionText,
                                    'percentage' => $optionPercentage,
                                ];
                            }
                        }

                        $questions[] = [
                            'questionId' => $questionId,
                            'questionValue' => $questionText,
                            'questionType' => $questionType,
                            'answers' => $multiCheckboxValue,
                            'persentasi' => $percentageResults,
                        ];
                    }
                } else {
                    $answerValue = [
                        'id' => $answerId,
                        'user_name' => $userName,
                        'optionId' => null,
                        'value' => $answerValue,
                        'description' => $answerValue,
                        'extraAnswers' => null,
                    ];

                    $questions[] = [
                        'questionId' => $questionId,
                        'questionValue' => $questionText,
                        'questionType' => $questionType,
                        'answers' => $answerValue,
                    ];
                }
            }
        }

        return $questions;
    }
}
