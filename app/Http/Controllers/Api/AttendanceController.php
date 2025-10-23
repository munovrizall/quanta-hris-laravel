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
            'cabang_id' => 'required|exists:cabang,cabang_id',
            'foto_masuk' => 'nullable|string',
        ]);

        $user = $request->user();
        $currentDate = Carbon::today()->format('Y-m-d'); // Changed to Carbon instance
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

        // Get cabang and validate location
        $cabang = Cabang::find($request->cabang_id);
        if (!$cabang) {
            return ApiResponse::format(
                false,
                404,
                'Branch not found.',
                null
            );
        }

        // Calculate distance using Haversine formula
        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $cabang->latitude,
            $cabang->longitude
        );

        // Check if user is within the allowed radius
        if ($distance > $cabang->radius_lokasi) {
            return ApiResponse::format(
                false,
                403,
                'You are outside the allowed location radius. Distance: ' . round($distance) . 'm, Allowed: ' . $cabang->radius_lokasi . 'm',
                [
                    'distance' => round($distance, 2),
                    'allowed_radius' => $cabang->radius_lokasi,
                ]
            );
        }

        // Get company operational hours
        $company = $user->perusahaan;
        if (!$company) {
            return ApiResponse::format(
                false,
                404,
                'Company not found for this user.',
                null
            );
        }

        // Determine status_masuk
        $jamMasuk = Carbon::parse($company->jam_masuk);
        $statusMasuk = $currentTime->format('H:i:s') > $jamMasuk->format('H:i:s') ? 'Telat' : 'Tepat Waktu';

        // Generate new absensi_id
        $lastAbsensi = Absensi::orderBy('absensi_id', 'desc')->first();
        if ($lastAbsensi) {
            $lastNumber = intval(substr($lastAbsensi->absensi_id, 3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        $absensiId = 'ABS' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);

        // Calculate durasi_telat if late
        $durasiTelat = null;
        if ($statusMasuk === 'Telat') {
            $diffInMinutes = $currentTime->diffInMinutes($jamMasuk);
            $hours = floor($diffInMinutes / 60);
            $minutes = $diffInMinutes % 60;
            $durasiTelat = sprintf('%02d:%02d:00', $hours, $minutes);
        }

        // Create attendance record
        $attendance = new Absensi();
        $attendance->absensi_id = $absensiId;
        $attendance->karyawan_id = $user->karyawan_id;
        $attendance->cabang_id = $request->cabang_id;
        $attendance->tanggal = $currentDate;
        $attendance->waktu_masuk = $currentTime;
        $attendance->koordinat_masuk = $request->latitude . ',' . $request->longitude;
        $attendance->foto_masuk = $request->foto_masuk ?? '';
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
                'cabang' => $cabang->nama_cabang,
                'distance_from_branch' => round($distance, 2) . 'm',
            ]
        );
    }

    public function clockOut(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'foto_pulang' => 'nullable|string', // base64 or file path
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

        // Validate location
        $cabang = Cabang::find($attendance->cabang_id);
        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $cabang->latitude,
            $cabang->longitude
        );

        if ($distance > $cabang->radius_lokasi) {
            return ApiResponse::format(
                false,
                403,
                'You are outside the allowed location radius. Distance: ' . round($distance) . 'm, Allowed: ' . $cabang->radius_lokasi . 'm',
                [
                    'distance' => round($distance, 2),
                    'allowed_radius' => $cabang->radius_lokasi,
                ]
            );
        }

        // Get company operational hours
        $company = $user->perusahaan;
        $jamPulang = Carbon::parse($company->jam_pulang);
        $statusPulang = $currentTime->format('H:i:s') < $jamPulang->format('H:i:s') ? 'Pulang Cepat' : 'Tepat Waktu';

        // Calculate durasi_pulang_cepat if early
        $durasiPulangCepat = null;
        if ($statusPulang === 'Pulang Cepat') {
            $diffInMinutes = $jamPulang->diffInMinutes($currentTime);
            $hours = floor($diffInMinutes / 60);
            $minutes = $diffInMinutes % 60;
            $durasiPulangCepat = sprintf('%02d:%02d:00', $hours, $minutes);
        }

        // Update attendance record
        $attendance->waktu_pulang = $currentTime;
        $attendance->koordinat_pulang = $request->latitude . ',' . $request->longitude;
        $attendance->foto_pulang = $request->foto_pulang ?? '';
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
                'cabang' => $cabang->nama_cabang,
                'distance_from_branch' => round($distance, 2) . 'm',
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
