<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Attendance;
use App\Models\Cuti;
use App\Models\Izin;
use App\Models\Perusahaan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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
        $company = Perusahaan::find($user->company_id);

        // Check if user is late
        $isLate = false;
        if ($company) {
            $companyTimeIn = $company->time_in;
            $isLate = $timeIn > $companyTimeIn;
        }

        $attendance = new Absensi;
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
        $attendance = Absensi::where('user_id', $request->user()->id)
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

        $attendance = Absensi::where('karyawan_id', $request->user()->id)
            ->where('date', date('Y-m-d'))
            ->first();

        return ApiResponse::format(
            true,
            200,
            'Is today checked in retrieved successfully',
            [
                'is_clocked_in' => $attendance ? true : false,
            ]
        );
    }

    public function getTodayLeavesAndPermits(Request $request)
    {
        $today = Carbon::today();

        // Get approved leaves (cuti) for today
        $leaves = Cuti::with('karyawan')
            ->where('status_cuti', 'Disetujui')
            ->where('tanggal_mulai', '<=', $today)
            ->where('tanggal_selesai', '>=', $today)
            ->get()
            ->map(function ($cuti) {
                return [
                    'nama' => $cuti->karyawan->nama_lengkap,
                    'tipe' => 'Cuti',
                    'alasan' => $cuti->keterangan,
                    'jenis' => $cuti->jenis_cuti,
                    'tanggal_mulai' => Carbon::parse($cuti->tanggal_mulai)->format('Y-m-d'),
                    'tanggal_selesai' => Carbon::parse($cuti->tanggal_selesai)->format('Y-m-d'),
                ];
            });

        // Get approved permits (izin) for today
        $permits = Izin::with('karyawan')
            ->where('status_izin', 'Disetujui')
            ->where('tanggal_mulai', '<=', $today)
            ->where('tanggal_selesai', '>=', $today)
            ->get()
            ->map(function ($izin) {
                return [
                    'nama' => $izin->karyawan->nama_lengkap,
                    'tipe' => 'Izin',
                    'alasan' => $izin->keterangan,
                    'jenis' => $izin->jenis_izin,
                    'tanggal_mulai' => Carbon::parse($izin->tanggal_mulai)->format('Y-m-d'),
                    'tanggal_selesai' => Carbon::parse($izin->tanggal_selesai)->format('Y-m-d'),
                ];
            });

        // Combine and sort by name
        $allLeavesAndPermits = $leaves->concat($permits)->sortBy('nama')->values();

        return ApiResponse::format(true, 200, 'Today\'s leaves and permits retrieved successfully.', [
            'total' => $allLeavesAndPermits->count(),
            'data' => $allLeavesAndPermits
        ]);
    }
}
