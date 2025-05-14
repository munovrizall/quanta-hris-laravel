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
            'time_in' => 'sometimes|date_format:H:i:s',
        ]);

        $user = $request->user();
        $currentDate = date('Y-m-d');
        $timeIn = $request->input('time_in', date('H:i:s'));

        // Get the user's company and its time_in value
        $company = \App\Models\Company::find($user->company_id);

        // Check if user is late
        $isLate = false;
        if ($company) {
            $companyTimeIn = $company->time_in;
            $isLate = $timeIn > $companyTimeIn;
        }

        $attendance = new Attendance;
        $attendance->user_id = $user->id;
        $attendance->date = $currentDate;
        $attendance->time_in = $timeIn;
        $attendance->latlon_in = $request->latitude . ',' . $request->longitude;
        $attendance->is_late = $isLate;
        $attendance->save();

        return ApiResponse::format(
            true,
            201,
            'Clock in successful.' . ($isLate ? ' You are late today.' : ''),
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
