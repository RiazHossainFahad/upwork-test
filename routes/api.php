<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticationController;

Route::group([
    'prefix'     => 'v1',
], function () {
    /* REGISTER */
    Route::post('register', [AuthenticationController::class, 'register']);
    /* LOGIN */
    Route::post('login', [AuthenticationController::class, 'login'])->middleware('throttle:login');

    Route::group(['middleware' => ['auth:sanctum']], function () {
        /* LOGOUT */
        Route::post('logout', [AuthenticationController::class, 'logout']);
    });
});