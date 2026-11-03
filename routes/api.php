<?php

use App\Http\Controllers\Api\BiometricEnrollmentController;
use Illuminate\Support\Facades\Route;

Route::post('/biometric/devices/{deviceId}/enrollments', BiometricEnrollmentController::class)
    ->whereNumber('deviceId')
    ->withoutMiddleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class])
    ->middleware('throttle:60,1')
    ->name('api.biometric.enrollments.store');

Route::post('/biometric/devices/{deviceId}/punches', \App\Http\Controllers\Api\AttendancePunchController::class)
    ->whereNumber('deviceId')
    ->withoutMiddleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class])
    ->middleware('throttle:60,1')
    ->name('api.biometric.punches.store');
