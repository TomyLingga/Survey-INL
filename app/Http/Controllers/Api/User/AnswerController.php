<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\ExtraAnswer;
use App\Models\SurveyPertanyaan;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AnswerController extends Controller
{
    private function isMultipleChoice($type)
    {
        return in_array($type, ['radio', 'checkbox', 'dropdown', 'range']);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
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

            $user_id = Auth::user()->id;
            foreach ($request->answer as $keySp => $valueSp) {
                $spId = explode("-", $keySp)[1];
                $arrayAnswer = explode(";", $valueSp["jawaban"]);
                $arrayExtraAnswers =[];
                foreach ($arrayAnswer as $keyAnswer => $valueAnswer) {
                    //JAWABAN EXTRA MASIH DUPLIKAT
                    $answer = explode("|", $valueAnswer);
                    $questionData = Question::findOrFail($answer[0]);

                    if ($this->isMultipleChoice($questionData->type)) {
                        $option = Option::findOrFail($answer[1]);

                        if ($option->extra_answer == 1) {

                            foreach ($valueSp["extra"] as $keyExtra => $valueExtra) {

                                $extraId = explode("-", $keyExtra)[1];
                                $extraAnswer = $valueExtra;
                                // dd($extraAnswer);

                                $arrayExtraAnswers[] = [$extraId, $valueExtra];
                            }

                        }
                    }
                }
                // if ($keySp == "sp-16") {
                //     dd($arrayExtraAnswers);
                // }
                // dd($arrayExtraAnswers);
                $answerData = [
                    'user_id' => $user_id,
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
                    ExtraAnswer::create($dataExtra);
                }

            }

            DB::commit();

            return response()->json([
                // 'data' => $createdAnswers,
                'message' => 'Answers created successfully',
                'success' => true,
                'code' => 200
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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Answer  $answer
     * @return \Illuminate\Http\Response
     */
    public function show(Answer $answer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Answer  $answer
     * @return \Illuminate\Http\Response
     */
    public function edit(Answer $answer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Answer  $answer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Answer $answer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Answer  $answer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Answer $answer)
    {
        //
    }
}
