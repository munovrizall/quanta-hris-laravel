<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Attendance;
use App\Models\Cabang;
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
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'foto_masuk' => 'nullable|image|mimes:jpeg,png,jpg|max:5000', // Changed from string to image
        ]);

        $user = $request->user();
        $currentDate = Carbon::today()->format('Y-m-d');
        $currentTime = Carbon::now();

        // Check if already clocked in today
        $existingAttendance = Absensi::where('karyawan_id', $user->karyawan_id)
            ->whereDate('tanggal', $currentDate)
            ->first();

        if ($existingAttendance) {
            return ApiResponse::format(
                false,
                400,
                'You have already clocked in today.',
                null
            );
        }

        // Get company branches
        $company = $user->perusahaan;
        if (!$company) {
            return ApiResponse::format(
                false,
                404,
                'Company not found for this user.',
                null
            );
        }

        // Find nearest branch within radius
        $branches = Cabang::where('perusahaan_id', $company->perusahaan_id)->get();

        if ($branches->isEmpty()) {
            return ApiResponse::format(
                false,
                404,
                'No branches found for this company.',
                null
            );
        }

        $nearestBranch = null;
        $shortestDistance = PHP_FLOAT_MAX;

        foreach ($branches as $branch) {
            $distance = $this->calculateDistance(
                $request->latitude,
                $request->longitude,
                $branch->latitude,
                $branch->longitude
            );

            // Check if within radius and is the nearest
            if ($distance <= $branch->radius_lokasi && $distance < $shortestDistance) {
                $shortestDistance = $distance;
                $nearestBranch = $branch;
            }
        }

        // If no branch found within radius
        if (!$nearestBranch) {
            // Find the actual nearest branch even if outside radius
            $nearestBranchInfo = null;
            $shortestDistanceOverall = PHP_FLOAT_MAX;

            foreach ($branches as $branch) {
                $distance = $this->calculateDistance(
                    $request->latitude,
                    $request->longitude,
                    $branch->latitude,
                    $branch->longitude
                );

                if ($distance < $shortestDistanceOverall) {
                    $shortestDistanceOverall = $distance;
                    $nearestBranchInfo = $branch;
                }
            }

            return ApiResponse::format(
                false,
                403,
                'You are outside the allowed location radius of all branches.',
                [
                    'your_location' => [
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                    ],
                    'nearest_branch' => [
                        'nama_cabang' => $nearestBranchInfo->nama_cabang,
                        'distance' => round($shortestDistanceOverall, 2) . 'm',
                        'allowed_radius' => $nearestBranchInfo->radius_lokasi . 'm',
                    ]
                ]
            );
        }

        // Determine status_masuk
        $jamMasuk = Carbon::parse($company->jam_masuk);
        $statusMasuk = $currentTime->format('H:i:s') > $jamMasuk->format('H:i:s') ? 'Telat' : 'Tepat Waktu';

        // Generate new absensi_id (include soft deleted records to avoid duplicate)
        $lastAbsensi = Absensi::withTrashed()->orderBy('absensi_id', 'desc')->first();
        if ($lastAbsensi) {
            $lastNumber = intval(substr($lastAbsensi->absensi_id, 2));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        // absensi_id length is 8, e.g. AB000001
        $absensiId = 'AB' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);

        // Calculate durasi_telat if late
        $durasiTelat = null;
        if ($statusMasuk === 'Telat') {
            // Gunakan diffInMinutes dengan parameter false untuk mendapatkan nilai absolut
            $diffInMinutes = $currentTime->copy()->startOfDay()
                ->addSeconds($currentTime->secondsSinceMidnight())
                ->diffInMinutes(
                    $jamMasuk->copy()->startOfDay()->addSeconds($jamMasuk->secondsSinceMidnight()),
                    false
                );

            // Pastikan nilai positif
            $diffInMinutes = abs($diffInMinutes);

            $hours = floor($diffInMinutes / 60);
            $minutes = $diffInMinutes % 60;
            $durasiTelat = sprintf('%02d:%02d:00', $hours, $minutes);
        }

        // Handle foto_masuk upload
        $fotoMasukPath = null;
        if ($request->hasFile('foto_masuk')) {
            $file = $request->file('foto_masuk');
            $fileName = 'clock_in_' . $user->karyawan_id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $fotoMasukPath = $file->storeAs('attendance/clock_in', $fileName, 'public');
        }

        // Create attendance record
        $attendance = new Absensi();
        $attendance->absensi_id = $absensiId;
        $attendance->karyawan_id = $user->karyawan_id;
        $attendance->cabang_id = $nearestBranch->cabang_id;
        $attendance->tanggal = $currentDate;
        $attendance->waktu_masuk = $currentTime;
        $attendance->koordinat_masuk = $request->latitude . ',' . $request->longitude;
        $attendance->foto_masuk = $fotoMasukPath ?? '';
        $attendance->status_masuk = $statusMasuk;
        $attendance->status_absensi = $statusMasuk === 'Tepat Waktu'
            ? 'Hadir'
            : 'Tidak Tepat';
        $attendance->durasi_telat = $durasiTelat;
        $attendance->save();

        return ApiResponse::format(
            true,
            201,
            'Clock in successful.' . ($statusMasuk === 'Telat' ? ' You are late today.' : ''),
            [
                'absensi_id' => $attendance->absensi_id,
                'karyawan_id' => $attendance->karyawan_id,
                'tanggal' => $currentDate,
                'waktu_masuk' => $currentTime->format('H:i:s'),
                'status_masuk' => $statusMasuk,
                'status_absensi' => $attendance->status_absensi,
                'durasi_telat' => $durasiTelat,
                'foto_masuk' => $fotoMasukPath ? asset('storage/' . $fotoMasukPath) : null,
                'cabang' => [
                    'cabang_id' => $nearestBranch->cabang_id,
                    'nama_cabang' => $nearestBranch->nama_cabang,
                    'alamat' => $nearestBranch->alamat,
                ],
                'distance_from_branch' => round($shortestDistance, 2) . 'm',
            ]
        );
    }

    public function clockOut(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'foto_pulang' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Changed from string to image
        ]);

        $user = $request->user();
        $currentDate = Carbon::today()->format('Y-m-d');
        $currentTime = Carbon::now();

        // Get today's attendance
        $attendance = Absensi::where('karyawan_id', $user->karyawan_id)
            ->where('tanggal', $currentDate)
            ->first();

        // Check if attendance not found
        if (!$attendance) {
            return ApiResponse::format(
                false,
                400,
                'Please clock in first!',
                null
            );
        }

        // Check if already clocked out
        if ($attendance->waktu_pulang) {
            return ApiResponse::format(
                false,
                400,
                'You have already clocked out today.',
                null
            );
        }

        // Get company branches
        $company = $user->perusahaan;
        if (!$company) {
            return ApiResponse::format(
                false,
                404,
                'Company not found for this user.',
                null
            );
        }

        // Find nearest branch within radius (can be different from clock in branch)
        $branches = Cabang::where('perusahaan_id', $company->perusahaan_id)->get();

        if ($branches->isEmpty()) {
            return ApiResponse::format(
                false,
                404,
                'No branches found for this company.',
                null
            );
        }

        $nearestBranch = null;
        $shortestDistance = PHP_FLOAT_MAX;

        foreach ($branches as $branch) {
            $distance = $this->calculateDistance(
                $request->latitude,
                $request->longitude,
                $branch->latitude,
                $branch->longitude
            );

            // Check if within radius and is the nearest
            if ($distance <= $branch->radius_lokasi && $distance < $shortestDistance) {
                $shortestDistance = $distance;
                $nearestBranch = $branch;
            }
        }

        // If no branch found within radius
        if (!$nearestBranch) {
            // Find the actual nearest branch even if outside radius
            $nearestBranchInfo = null;
            $shortestDistanceOverall = PHP_FLOAT_MAX;

            foreach ($branches as $branch) {
                $distance = $this->calculateDistance(
                    $request->latitude,
                    $request->longitude,
                    $branch->latitude,
                    $branch->longitude
                );

                if ($distance < $shortestDistanceOverall) {
                    $shortestDistanceOverall = $distance;
                    $nearestBranchInfo = $branch;
                }
            }

            return ApiResponse::format(
                false,
                403,
                'You are outside the allowed location radius of all branches.',
                [
                    'your_location' => [
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                    ],
                    'nearest_branch' => [
                        'nama_cabang' => $nearestBranchInfo->nama_cabang,
                        'distance' => round($shortestDistanceOverall, 2) . 'm',
                        'allowed_radius' => $nearestBranchInfo->radius_lokasi . 'm',
                    ]
                ]
            );
        }

        // Get company operational hours
        $jamPulang = Carbon::parse($company->jam_pulang);
        $statusPulang = $currentTime->format('H:i:s') < $jamPulang->format('H:i:s') ? 'Pulang Cepat' : 'Tepat Waktu';

        // Calculate durasi_pulang_cepat if early
        $durasiPulangCepat = null;
        if ($statusPulang === 'Pulang Cepat') {
            // Gunakan diffInMinutes dengan parameter false untuk mendapatkan nilai absolut
            $diffInMinutes = $currentTime->copy()->startOfDay()
                ->addSeconds($currentTime->secondsSinceMidnight())
                ->diffInMinutes(
                    $jamPulang->copy()->startOfDay()->addSeconds($jamPulang->secondsSinceMidnight()),
                    false
                );

            // Pastikan nilai positif
            $diffInMinutes = abs($diffInMinutes);

            $hours = floor($diffInMinutes / 60);
            $minutes = $diffInMinutes % 60;
            $durasiPulangCepat = sprintf('%02d:%02d:00', $hours, $minutes);
        }

        // Handle foto_pulang upload
        $fotoPulangPath = null;
        if ($request->hasFile('foto_pulang')) {
            $file = $request->file('foto_pulang');
            $fileName = 'clock_out_' . $user->karyawan_id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $fotoPulangPath = $file->storeAs('attendance/clock_out', $fileName, 'public');
        }

        // Check if eligible for overtime (lembur)
        // Eligible if clock out time is more than 1 hour after company's closing time
        $jamPulangToday = Carbon::today()->setTimeFromTimeString($company->jam_pulang);
        $diffInMinutes = $currentTime->diffInMinutes($jamPulangToday, false);
        $isEligibleLembur = $diffInMinutes > 60; // More than 60 minutes (1 hour) after closing time

        // Update attendance record with new branch if different
        $attendance->cabang_id = $nearestBranch->cabang_id;
        $attendance->waktu_pulang = $currentTime;
        $attendance->koordinat_pulang = $request->latitude . ',' . $request->longitude;
        $attendance->foto_pulang = $fotoPulangPath ?? '';
        $attendance->status_pulang = $statusPulang;
        $attendance->durasi_pulang_cepat = $durasiPulangCepat;

        if (in_array($attendance->status_absensi, ['Hadir', 'Tidak Tepat'], true)) {
            $attendance->status_absensi = (
                $attendance->status_masuk === 'Tepat Waktu' &&
                $statusPulang === 'Tepat Waktu'
            )
                ? 'Hadir'
                : 'Tidak Tepat';
        }

        $attendance->save();

        return ApiResponse::format(
            true,
            200,
            'Clock out successful.' . ($statusPulang === 'Pulang Cepat' ? ' You left early today.' : ''),
            [
                'absensi_id' => $attendance->absensi_id,
                'karyawan_id' => $attendance->karyawan_id,
                'tanggal' => $currentDate,
                'waktu_pulang' => $currentTime->format('H:i:s'),
                'status_pulang' => $statusPulang,
                'status_absensi' => $attendance->status_absensi,
                'durasi_pulang_cepat' => $durasiPulangCepat,
                'foto_pulang' => $fotoPulangPath ? asset('storage/' . $fotoPulangPath) : null,
                'cabang' => [
                    'cabang_id' => $nearestBranch->cabang_id,
                    'nama_cabang' => $nearestBranch->nama_cabang,
                    'alamat' => $nearestBranch->alamat,
                ],
                'distance_from_branch' => round($shortestDistance, 2) . 'm',
                'is_eligible_lembur' => $isEligibleLembur,
            ]
        );
    }

    public function attendanceStatus(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();

        $attendance = Absensi::where('karyawan_id', $user->karyawan_id)
            ->whereDate('tanggal', $today->format('Y-m-d'))
            ->first();

        // Check approved leave (cuti) or permit (izin) for today
        $hasApprovedLeave = Cuti::where('karyawan_id', $user->karyawan_id)
            ->where('status_cuti', 'Disetujui')
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->exists();

        $hasApprovedPermit = Izin::where('karyawan_id', $user->karyawan_id)
            ->where('status_izin', 'Disetujui')
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->exists();

        $isEligibleAttendance = !($hasApprovedLeave || $hasApprovedPermit);

        // Determine clock in and clock out status
        $isClockedIn = $attendance ? true : false;
        $isClockedOut = $attendance && $attendance->waktu_pulang ? true : false;

        return ApiResponse::format(
            true,
            200,
            'Attendance status retrieved successfully',
            [
                'is_clocked_in' => $isClockedIn,
                'is_clocked_out' => $isClockedOut,
                'is_eligible_attendance' => $isEligibleAttendance,
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

    /**
     * Get attendance history for the logged-in employee
     * Optional query params: from (Y-m-d), to (Y-m-d)
     */
    public function history(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->karyawan_id) {
            return ApiResponse::format(false, 401, 'Unauthorized', null);
        }

        $from = $request->query('from');
        $to = $request->query('to');

        try {
            $startDate = $from
                ? Carbon::createFromFormat('Y-m-d', $from)->startOfDay()
                : Carbon::today()->subDays(30)->startOfDay();
            $endDate = $to
                ? Carbon::createFromFormat('Y-m-d', $to)->startOfDay()
                : Carbon::today()->startOfDay();
        } catch (\Throwable $e) {
            return ApiResponse::format(false, 422, 'Format tanggal tidak valid. Gunakan Y-m-d.', null);
        }

        if ($startDate->greaterThan($endDate)) {
            return ApiResponse::format(false, 422, 'Parameter "from" harus lebih awal daripada "to".', null);
        }

        $records = Absensi::where('karyawan_id', $user->karyawan_id)
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $recordsByDate = $records->keyBy(function ($row) {
            return $row->tanggal ? Carbon::parse($row->tanggal)->format('Y-m-d') : null;
        })->filter();

        $data = collect();
        $currentDate = $endDate->copy();
        while ($currentDate->greaterThanOrEqualTo($startDate)) {
            $dateKey = $currentDate->format('Y-m-d');
            $record = $recordsByDate->get($dateKey);

            if ($record) {
                $data->push([
                    'tanggal' => $dateKey,
                    'jam_masuk' => $record->waktu_masuk ? Carbon::parse($record->waktu_masuk)->format('H:i:s') : null,
                    'status_masuk' => $record->status_masuk,
                    'jam_pulang' => $record->waktu_pulang ? Carbon::parse($record->waktu_pulang)->format('H:i:s') : null,
                    'status_pulang' => $record->status_pulang,
                    'status_absensi' => $record->status_absensi,
                ]);
            } elseif (!$currentDate->isWeekend()) {
                $data->push([
                    'tanggal' => $dateKey,
                    'jam_masuk' => null,
                    'status_masuk' => null,
                    'jam_pulang' => null,
                    'status_pulang' => null,
                    'status_absensi' => 'Alfa',
                ]);
            }

            $currentDate->subDay();
        }

        return ApiResponse::format(true, 200, 'Attendance history retrieved successfully.', $data);
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     * Returns distance in meters
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Earth radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
