<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\SignupController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\Auth\AuthenticationController;

Route::group([
    'prefix'     => 'v1',
], function () {
    /* REGISTER */
    Route::post('register', [AuthenticationController::class, 'register']);
    Route::post('user/register', [SignupController::class, 'register']);
    Route::post('user/verify-verification-code', [SignupController::class, 'verifyVerificationCode']);

    /* LOGIN */
    Route::post('login', [AuthenticationController::class, 'login'])->middleware('throttle:login');

    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('invite-for-signup', [InvitationController::class, 'inviteForSignup']);

        Route::put('user/update-profile', [SignupController::class, 'updateProfile']);

        /* LOGOUT */
        Route::post('logout', [AuthenticationController::class, 'logout']);
    });
});