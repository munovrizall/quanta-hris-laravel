<?php
namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required']
        ]);

        if (!Auth::attempt($credentials)) {
            return ApiResponse::format(false, 401, 'Email atau password salah.', null);
        }

        $user = Auth::user();
        $token = $user->createToken('quanta-hris')->plainTextToken;

        return ApiResponse::format(true, 200, 'Login successful', [
            'token' => $token,
            'user' => [
                'karyawan_id' => $user->karyawan_id,
                'nama_lengkap' => $user->nama_lengkap,
                'email' => $user->email,
                'role' => $user->role,
                'departemen' => $user->departemen,
                'jabatan' => $user->jabatan,
                'nomor_telepon' => $user->nomor_telepon,
                'face_embedding' => $user->face_embedding,
            ]
        ]);

    }

    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->tokens()->where('id', $request->user()->currentAccessToken()->id)->delete();

        return ApiResponse::format(true, 200, 'Logout successful', null);
    }

    public function updateProfile(Request $request)
    {

        $request->validate([
            'face_embedding' => 'required',
        ]);

        $user = $request->user();
        $face_embedding = $request->face_embedding;

        $user->face_embedding = $face_embedding;
        $user->save();

        return ApiResponse::format(true, 200, 'Profile updated', null);
    }
}
