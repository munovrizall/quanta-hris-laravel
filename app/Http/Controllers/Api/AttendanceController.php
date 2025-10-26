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
            'foto_masuk' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Changed from string to image
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

        // Generate new absensi_id
        $lastAbsensi = Absensi::orderBy('absensi_id', 'desc')->first();
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
        $attendance->status_absensi = 'Hadir';
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

        // Update attendance record with new branch if different
        $attendance->cabang_id = $nearestBranch->cabang_id;
        $attendance->waktu_pulang = $currentTime;
        $attendance->koordinat_pulang = $request->latitude . ',' . $request->longitude;
        $attendance->foto_pulang = $fotoPulangPath ?? '';
        $attendance->status_pulang = $statusPulang;
        $attendance->durasi_pulang_cepat = $durasiPulangCepat;
        $attendance->save();

        return ApiResponse::format(
            true,
            200,
            'Clock out successful.' . ($statusPulang === 'Pulang Cepat' ? ' You left early today.' : ''),
            [
                'absensi_id' => $attendance->absensi_id,
                'waktu_pulang' => $currentTime->format('H:i:s'),
                'status_pulang' => $statusPulang,
                'durasi_pulang_cepat' => $durasiPulangCepat,
                'foto_pulang' => $fotoPulangPath ? asset('storage/' . $fotoPulangPath) : null,
                'cabang' => [
                    'cabang_id' => $nearestBranch->cabang_id,
                    'nama_cabang' => $nearestBranch->nama_cabang,
                ],
                'distance_from_branch' => round($shortestDistance, 2) . 'm',
            ]
        );
    }

    public function isClockedIn(Request $request)
    {

        $attendance = Absensi::where('karyawan_id', $request->user()->id)
            ->where('tanggal', date('Y-m-d'))
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

        $query = Absensi::where('karyawan_id', $user->karyawan_id);

        if ($from) {
            $query->whereDate('tanggal', '>=', $from);
        }
        if ($to) {
            $query->whereDate('tanggal', '<=', $to);
        }
        if (!$from && !$to) {
            $query->whereDate('tanggal', '>=', Carbon::today()->subDays(30)->format('Y-m-d'));
        }

        $records = $query->orderBy('tanggal', 'desc')->get();

        if ($records->isEmpty()) {
            return ApiResponse::format(true, 200, 'No attendance records found for the specified period.', []);
        }

        $data = $records->map(function ($row) {
            return [
                'tanggal' => $row->tanggal ? Carbon::parse($row->tanggal)->format('Y-m-d') : null,
                'jam_masuk' => $row->waktu_masuk ? Carbon::parse($row->waktu_masuk)->format('H:i:s') : null,
                'status_masuk' => $row->status_masuk,
                'jam_pulang' => $row->waktu_pulang ? Carbon::parse($row->waktu_pulang)->format('H:i:s') : null,
                'status_pulang' => $row->status_pulang,
                'status_absensi' => $row->status_absensi,
            ];
        });

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
