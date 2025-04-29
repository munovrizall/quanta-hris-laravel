<?php

use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return ApiResponse::format(true, 200, 'User data retrieved successfully.', $request->user());
})->middleware('auth:sanctum');

Route::post('login', function (Request $request) {
    $credentials = $request->only('email', 'password');

    if (Auth::attempt($credentials)) {
        $user = Auth::user();
        $token = $user->createToken('quanta-hris')->plainTextToken;

        // Menggunakan helper ApiResponse untuk login berhasil
        return ApiResponse::format(true, 200, 'Login successful', ['token' => $token]);
    }

    // Menggunakan helper ApiResponse untuk login gagal
    return ApiResponse::format(false, 401, 'Unauthorized', null);
});