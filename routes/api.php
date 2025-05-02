<?php

use App\Helpers\ApiResponse;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\SiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return ApiResponse::format(true, 200, 'User data retrieved successfully.', $request->user());
})->middleware('auth:sanctum');

Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {
    // Company routes
    Route::get('companies', [CompanyController::class, 'index']);
    Route::get('company/{id}', [CompanyController::class, 'show']);

    // Site routes
    Route::get('sites', [SiteController::class, 'index']);
    Route::get('site/{id}', [SiteController::class, 'show']);

    // Attendance routes
    Route::post('attendance/clock-in', [AttendanceController::class, 'clockIn']);
    Route::post('attendance/clock-out', [AttendanceController::class,'clockOut']);
    Route::get('attendance/is-clocked-in', [AttendanceController::class,'isClockedIn']);
});