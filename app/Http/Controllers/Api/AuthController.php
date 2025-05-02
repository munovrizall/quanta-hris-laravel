<?php
namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('quanta-hris')->plainTextToken;

            return ApiResponse::format(true, 200, 'Login successful', [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'department' => $user->department,
                    'position' => $user->position,
                    'phone' => $user->phone,
                ]
            ]);
        }

        return ApiResponse::format(false, 401, 'Unauthorized', null);
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
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'face_embedding' => 'required',
        ]);

        $user = $request->user();
        $image = $request->file('image');
        $face_embedding = $request->face_embedding;

        //save image
        $image->storeAs('public/images', $image->hashName());
        $user->image_url = $image->hashName();
        $user->face_embedding = $face_embedding;
        $user->save();

        return ApiResponse::format(true, 200, 'Profile updated', null);
    }
}