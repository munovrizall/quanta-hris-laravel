<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function clockIn(Request $request)
    {
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        $attendance = new Attendance;
        $attendance->user_id = $request->user()->id;
        $attendance->date = date('Y-m-d');
        $attendance->time_in = date('H:i:s');
        $attendance->latlon_in = $request->latitude . ',' . $request->longitude;
        $attendance->save();

        return ApiResponse::format(
            true,
            201,
            'Clock in successful.',
            $attendance
        );
    }

    public function clockOut(Request $request)
    {
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        // get today attendance
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->where('date', date('Y-m-d'))
            ->first();

        // check if attendance not found
        if (!$attendance) {
            return ApiResponse::format(
                false,
                400,
                'Clock in first!',
                null
            );
        }

        // save clock out
        $attendance->time_out = date('H:i:s');
        $attendance->latlon_out = $request->latitude . ',' . $request->longitude;
        $attendance->save();

        return ApiResponse::format(
            true,
            201,
            'Clock out successful.',
            $attendance
        );
    }

    public function isClockedIn(Request $request)
    {

        $attendance = Attendance::where('user_id', $request->user()->id)
            ->where('date', date('Y-m-d'))
            ->first();

        return ApiResponse::format(
            true,
            200,
            'Is today checked in retrieved successfully',
            [
                'clocked_in' => $attendance ? true : false,
            ]
        );
    }
}
