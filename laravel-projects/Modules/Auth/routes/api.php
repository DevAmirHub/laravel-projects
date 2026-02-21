<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\VerifyOtpController;

// /api/v1/check-user-authentication  post
Route::prefix('v1')->group(function () {
    Route::post('check-user-authentication', [AuthController::class, 'checkUserAuthentication'])
    ->name('auth.checkUserAuthentication')
    ->middleware('throttle:check-user-authentication');

    Route::post('send-otp', [VerifyOtpController::class, 'sendOtp'])
    ->name('auth.sendOtp')
    ->middleware('throttle:verify-otp');
});
