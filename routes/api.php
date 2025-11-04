<?php

use App\Helpers\ApiResponse;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\CutiController;
use App\Http\Controllers\Api\IzinController;
use App\Http\Controllers\Api\LemburController;
use App\Http\Controllers\Api\SlipGajiApiController;
use App\Http\Controllers\Api\SiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return ApiResponse::format(true, 200, 'User data retrieved successfully.', $request->user());
})->middleware('auth:sanctum');

Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {
    // Profile
    Route::get('profile', [AuthController::class, 'profile']);
    // Company routes
    // Route::get('companies', [CompanyController::class, 'index']);
    // Route::get('company/{id}', [CompanyController::class, 'show']);

    Route::get('company/operational-hours', [CompanyController::class, 'getCompanyOperationalHours']);
    Route::get('company/branches', [CompanyController::class, 'getCompanyBranches']);

    // Site routes
    Route::get('sites', [SiteController::class, 'index']);
    Route::get('site/{id}', [SiteController::class, 'show']);

    // Attendance routes
    Route::post('attendance/clock-in', [AttendanceController::class, 'clockIn']);
    Route::post('attendance/clock-out', [AttendanceController::class, 'clockOut']);
    Route::get('attendance/status', [AttendanceController::class, 'attendanceStatus']);
    Route::get('attendance/today-leaves-permits', [AttendanceController::class, 'getTodayLeavesAndPermits']);
    Route::get('attendance/history', [AttendanceController::class, 'history']);

    Route::post('update-profile', [AuthController::class, 'updateProfile']);

    // Permission routes
    Route::post('permission', [PermissionController::class, 'store']);

    // Cuti routes
    Route::post('cuti', [CutiController::class, 'store']);
    Route::get('cuti/detail', [CutiController::class, 'cutiDetail']);

    // Izin routes
    Route::post('izin', [IzinController::class, 'store']);

    // Lembur routes
    Route::post('lembur', [LemburController::class, 'store']);
    Route::get('lembur/eligible', [LemburController::class, 'eligible']);

    // Slip Gaji routes
    Route::get('slip-gaji', [SlipGajiApiController::class, 'index']);
    Route::get('slip-gaji/{tahun}/{bulan}', [SlipGajiApiController::class, 'show'])->where(['tahun' => '\\d{4}', 'bulan' => '\\d{1,2}']);
    Route::get('slip-gaji/{tahun}/{bulan}/download', [SlipGajiApiController::class, 'download'])->where(['tahun' => '\\d{4}', 'bulan' => '\\d{1,2}']);
});
