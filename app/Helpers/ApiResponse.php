<?php

namespace App\Helpers;

class ApiResponse
{
    /**
     * General API Response format
     *
     * @param bool $success
     * @param int $code
     * @param string $message
     * @param array|object|null $data
     * @return \Illuminate\Http\JsonResponse
     */
    public static function format($success, $code, $message, $data = null)
    {
        return response()->json([
            'success' => $success,
            'code' => $code,
            'message' => $message,
            'data' => $data
        ], $code);
    }
}
