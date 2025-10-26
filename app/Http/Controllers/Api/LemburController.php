<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Lembur;
use App\Services\LemburService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LemburController extends Controller
{
    /**
     * Ajukan lembur baru (status default Diajukan)
     */
    public function store(Request $request)
    {
        $user = $request->user(); // Karyawan
        if (!$user || !$user->karyawan_id) {
            return ApiResponse::format(false, 401, 'Unauthorized', null);
        }

        $validator = Validator::make($request->all(), [
            'absensi_id' => 'required|exists:absensi,absensi_id',
            'tanggal_lembur' => 'required|date',
            'durasi_lembur' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'deskripsi_pekerjaan' => 'required|string',
            'dokumen_pendukung' => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx|max:5120',
        ]);

        if ($validator->fails()) {
            return ApiResponse::format(false, 422, 'Validation error', [
                'errors' => $validator->errors(),
            ]);
        }

        // Pastikan absensi milik user yang login
        $absensi = Absensi::where('absensi_id', $request->absensi_id)
            ->where('karyawan_id', $user->karyawan_id)
            ->first();
        if (!$absensi) {
            return ApiResponse::format(false, 403, 'Absensi tidak valid untuk pengguna ini.', null);
        }

        // Normalisasi durasi ke HH:MM:SS
        $durasi = $request->durasi_lembur;
        if (strlen($durasi) === 5) {
            $durasi .= ':00';
        }

        // Generate ID lembur baru (LB0001 ...), termasuk yang soft-deleted
        $last = Lembur::withTrashed()
            ->where('lembur_id', 'like', 'LB%')
            ->orderBy('lembur_id', 'desc')
            ->first();
        $nextNumber = $last ? intval(str_replace('LB', '', $last->lembur_id)) + 1 : 1;
        $lemburId = 'LB' . str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);

        // Upload dokumen pendukung: simpan path seperti clock-in
        $dokumenPath = null;
        if ($request->hasFile('dokumen_pendukung')) {
            $file = $request->file('dokumen_pendukung');
            $ext = $file->getClientOriginalExtension();
            $fileName = 'lembur_' . $user->karyawan_id . '_' . time() . '.' . $ext;
            $dokumenPath = $file->storeAs('lembur/dokumen', $fileName, 'public');
        }

        // Simpan pengajuan (insentif dihitung saat disetujui)
        $lembur = new Lembur();
        $lembur->lembur_id = $lemburId;
        $lembur->karyawan_id = $user->karyawan_id;
        $lembur->absensi_id = $absensi->absensi_id;
        $lembur->tanggal_lembur = $request->tanggal_lembur;
        $lembur->durasi_lembur = $durasi;
        $lembur->deskripsi_pekerjaan = $request->deskripsi_pekerjaan;
        $lembur->dokumen_pendukung = $dokumenPath ?? null;
        $lembur->status_lembur = 'Diajukan';
        $lembur->alasan_penolakan = null;
        $lembur->approver_id = null;
        $lembur->processed_at = null;
        $lembur->total_insentif = null;
        $lembur->save();

        $data = [
            'lembur_id' => $lembur->lembur_id,
            'karyawan_id' => $lembur->karyawan_id,
            'absensi_id' => $lembur->absensi_id,
            'tanggal_lembur' => $lembur->tanggal_lembur,
            'durasi_lembur' => $lembur->durasi_lembur,
            'deskripsi_pekerjaan' => $lembur->deskripsi_pekerjaan,
            'status_lembur' => $lembur->status_lembur,
            'dokumen_pendukung' => $dokumenPath ? asset('storage/' . $dokumenPath) : null,
            'created_at' => $lembur->created_at,
        ];

        return ApiResponse::format(true, 201, 'Pengajuan lembur berhasil diajukan.', $data);
    }

    /**
     * Dapatkan informasi kelayakan lembur berdasarkan absensi dan jam pulang > 1 jam dari jam kerja perusahaan
     */
    public function eligible(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->karyawan_id) {
            return ApiResponse::format(false, 401, 'Unauthorized', null);
        }

        $absensiId = $request->query('absensi_id');
        $tanggal = $request->query('tanggal'); // optional Y-m-d

        // Ambil absensi
        if ($absensiId) {
            $absensi = Absensi::where('absensi_id', $absensiId)
                ->where('karyawan_id', $user->karyawan_id)
                ->first();
        } else {
            $query = Absensi::where('karyawan_id', $user->karyawan_id);
            if ($tanggal) {
                $query->whereDate('tanggal', $tanggal);
            } else {
                $query->whereDate('tanggal', Carbon::today()->format('Y-m-d'));
            }
            $absensi = $query->orderBy('waktu_pulang', 'desc')->first();
        }

        if (!$absensi) {
            return ApiResponse::format(false, 404, 'Data absensi tidak ditemukan.', null);
        }

        if (!$absensi->waktu_pulang) {
            return ApiResponse::format(false, 400, 'Belum absen pulang.', null);
        }

        // Jam pulang perusahaan
        $company = $user->perusahaan;
        if (!$company || !$company->jam_pulang) {
            return ApiResponse::format(false, 404, 'Jam kerja perusahaan tidak ditemukan.', null);
        }

        // Susun datetime akhir kerja pada tanggal absensi
        $tanggalStr = $absensi->tanggal ? $absensi->tanggal->format('Y-m-d') : Carbon::parse($absensi->waktu_pulang)->format('Y-m-d');
        $scheduledEnd = Carbon::parse($tanggalStr . ' ' . $company->jam_pulang);
        $clockOut = Carbon::parse($absensi->waktu_pulang);

        $diffMinutes = $scheduledEnd->diffInMinutes($clockOut, false);
        $eligible = $diffMinutes >= 60; // melebihi sejam

        $claimMinutes = max(0, $diffMinutes);
        $hours = intdiv($claimMinutes, 60);
        $minutes = $claimMinutes % 60;
        $durasiClaim = sprintf('%02d:%02d:00', $hours, $minutes);

        $insentif = 0;
        if ($claimMinutes > 0) {
            $service = new LemburService();
            $insentif = $service->calculateInsentif($durasiClaim, $user);
        }

        return ApiResponse::format(true, 200, 'Kelayakan lembur dihitung.', [
            'eligible' => $eligible,
            'absensi' => [
                'absensi_id' => $absensi->absensi_id,
                'tanggal' => $tanggalStr,
                'waktu_pulang' => Carbon::parse($absensi->waktu_pulang)->format('H:i:s'),
                'jam_pulang_perusahaan' => Carbon::parse($company->jam_pulang)->format('H:i:s'),
            ],
            'durasi_lembur_diklaim' => $durasiClaim,
            'jam_diklaim' => (int) ceil($claimMinutes / 60.0),
            'insentif' => $insentif,
        ]);
    }
}

