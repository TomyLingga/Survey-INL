<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [\App\Http\Controllers\Api\User\AuthController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\Api\User\AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'userMidd'])->group(function () {
    Route::post('/logout', [\App\Http\Controllers\Api\User\AuthController::class, 'logout']);
    Route::post('/update-password', [\App\Http\Controllers\Api\User\AuthController::class, 'update_password']);

    //answer
    Route::post('answer/add/{surveyId}', [App\Http\Controllers\Api\User\AnswerController::class, 'store']);
});

Route::middleware(['middleware' => 'adminOrUser'])->group(function () {
    // Survey
    Route::get('survey', [App\Http\Controllers\Api\Administrator\SurveyController::class, 'index']);
    Route::get('survey/get/{id}', [App\Http\Controllers\Api\Administrator\SurveyController::class, 'show']);

    // Question
    Route::get('question', [App\Http\Controllers\Api\Administrator\QuestionController::class, 'index']);
    Route::get('question/get/{id}', [App\Http\Controllers\Api\Administrator\QuestionController::class, 'show']);

});

Route::group(['middleware' => 'levelten.checker'], function () {
    //user
    Route::get('users', [App\Http\Controllers\Api\Administrator\UserController::class, 'index']);
    Route::get('user/get/{id}', [App\Http\Controllers\Api\Administrator\UserController::class, 'show']);
    Route::post('user/add', [App\Http\Controllers\Api\Administrator\UserController::class, 'user_store']);
    Route::post('user/update/{id}', [App\Http\Controllers\Api\Administrator\UserController::class, 'update']);
    Route::get('user/reset-password/{id}', [App\Http\Controllers\Api\Administrator\UserController::class, 'reset_password']);
    Route::get('user/active/{id}', [App\Http\Controllers\Api\Administrator\UserController::class, 'toggleActive']);

    //category
    Route::get('category', [App\Http\Controllers\Api\Administrator\CategoryController::class, 'index']);
    Route::get('category/get/{id}', [App\Http\Controllers\Api\Administrator\CategoryController::class, 'show']);
    Route::post('category/add', [App\Http\Controllers\Api\Administrator\CategoryController::class, 'store']);
    Route::post('category/update/{id}', [App\Http\Controllers\Api\Administrator\CategoryController::class, 'update']);
    Route::get('category/active/{id}', [App\Http\Controllers\Api\Administrator\CategoryController::class, 'toggleActive']);

    //question
    Route::post('question/add', [App\Http\Controllers\Api\Administrator\QuestionController::class, 'store']);
    Route::post('question/update/{id}', [App\Http\Controllers\Api\Administrator\QuestionController::class, 'update']);
    Route::get('question/active/{id}', [App\Http\Controllers\Api\Administrator\QuestionController::class, 'toggleActive']);

    //survey
    Route::post('survey/add', [App\Http\Controllers\Api\Administrator\SurveyController::class, 'store']);
    Route::post('survey/update/{id}', [App\Http\Controllers\Api\Administrator\SurveyController::class, 'update']);
    Route::get('survey/active/{id}', [App\Http\Controllers\Api\Administrator\SurveyController::class, 'toggleActive']);

    //answer
    Route::get('answer/all/{surveyId}', [App\Http\Controllers\Api\User\AnswerController::class, 'getSurveyAnswers']);
    Route::post('answer/individual/{surveyId}', [App\Http\Controllers\Api\User\AnswerController::class, 'getIndividualSurveyAnswer']);

});

Route::fallback(function () {
    return response()->json(['code' => 401, 'error' => 'Unauthorized'], 401);
});
