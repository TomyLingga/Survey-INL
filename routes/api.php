<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [\App\Http\Controllers\Api\User\AuthController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\Api\User\AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\Api\User\AuthController::class, 'logout']);
});

Route::group(['middleware' => 'levelten.checker'], function () {
    //user
    Route::get('users', [App\Http\Controllers\Api\Administrator\UserController::class, 'index']); 
    Route::get('user/{id}', [App\Http\Controllers\Api\Administrator\UserController::class, 'show']); 
    Route::post('user/add', [App\Http\Controllers\Api\Administrator\UserController::class, 'user_store']); 
});
