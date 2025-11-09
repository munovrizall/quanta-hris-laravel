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
     * Riwayat kelayakan lembur per tanggal (mirip struktur attendance history).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->karyawan_id) {
            return ApiResponse::format(false, 401, 'Unauthorized', null);
        }

        $company = $user->perusahaan;
        if (!$company || !$company->jam_pulang) {
            return ApiResponse::format(false, 404, 'Jam kerja perusahaan tidak ditemukan.', null);
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

        $absensiRecords = Absensi::where('karyawan_id', $user->karyawan_id)
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $lemburRecords = Lembur::where('karyawan_id', $user->karyawan_id)
            ->whereBetween('tanggal_lembur', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->keyBy('absensi_id');

        $recordsByDate = $absensiRecords->keyBy(function ($row) {
            return $row->tanggal ? Carbon::parse($row->tanggal)->format('Y-m-d') : null;
        })->filter();

        $jamPulangPerusahaan = Carbon::parse($company->jam_pulang)->format('H:i:s');
        $lemburService = new LemburService();

        $data = collect();
        $currentDate = $endDate->copy();

        while ($currentDate->greaterThanOrEqualTo($startDate)) {
            $dateKey = $currentDate->format('Y-m-d');
            $record = $recordsByDate->get($dateKey);

            if (!$record && $currentDate->isWeekend()) {
                $currentDate->subDay();
                continue;
            }

            $entry = [
                'tanggal' => $dateKey,
                'jam_masuk' => null,
                'status_masuk' => null,
                'jam_pulang' => null,
                'status_pulang' => null,
                'status_absensi' => 'Alfa',
                'eligible_lembur' => false,
                'durasi_lembur_terhitung' => null,
                'jam_pulang_perusahaan' => $jamPulangPerusahaan,
                'lembur_pengajuan' => null,
            ];

            if ($record) {
                $entry['jam_masuk'] = $record->waktu_masuk ? Carbon::parse($record->waktu_masuk)->format('H:i:s') : null;
                $entry['status_masuk'] = $record->status_masuk;
                $entry['jam_pulang'] = $record->waktu_pulang ? Carbon::parse($record->waktu_pulang)->format('H:i:s') : null;
                $entry['status_pulang'] = $record->status_pulang;
                $entry['status_absensi'] = $record->status_absensi;

                if ($record->waktu_pulang) {
                    $scheduledEnd = Carbon::parse($dateKey . ' ' . $company->jam_pulang);
                    $clockOut = Carbon::parse($record->waktu_pulang);
                    $diffMinutes = $scheduledEnd->diffInMinutes($clockOut, false);
                    $entry['eligible_lembur'] = $diffMinutes >= 60;

                    if ($diffMinutes > 0) {
                        $hours = intdiv($diffMinutes, 60);
                        $minutes = $diffMinutes % 60;
                        $entry['durasi_lembur_terhitung'] = sprintf('%02d:%02d:00', $hours, $minutes);
                    }
                }

                $lembur = $lemburRecords->get($record->absensi_id);
                if ($lembur) {
                    $upahLembur = $lembur->total_insentif;
                    if (is_null($upahLembur) && $lembur->durasi_lembur) {
                        $upahLembur = $lemburService->calculateInsentif($lembur->durasi_lembur, $user);
                    }

                    $entry['lembur_pengajuan'] = [
                        'lembur_id' => $lembur->lembur_id,
                        'status_lembur' => $lembur->status_lembur,
                        'durasi_lembur' => $lembur->durasi_lembur,
                        'upah_lembur' => $upahLembur ?? 0,
                        'processed_at' => $lembur->processed_at,
                    ];
                }
            }

            $data->push($entry);
            $currentDate->subDay();
        }

        return ApiResponse::format(true, 200, 'Riwayat lembur berhasil diambil.', $data);
    }
}
