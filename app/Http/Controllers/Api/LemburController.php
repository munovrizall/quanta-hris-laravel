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

        $validator = Validator::make(
            $request->all(),
            [
                'absensi_id' => 'required|exists:absensi,absensi_id',
                'deskripsi_pekerjaan' => 'required|string',
                'dokumen_pendukung' => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx|max:5120',
            ],
            [
                'absensi_id.required' => 'Absensi wajib dipilih.',
                'absensi_id.exists' => 'Absensi tidak ditemukan di sistem.',
                'deskripsi_pekerjaan.required' => 'Deskripsi pekerjaan tidak boleh kosong.',
                'dokumen_pendukung.file' => 'Dokumen pendukung harus berupa file.',
                'dokumen_pendukung.mimes' => 'Format dokumen pendukung harus jpeg, png, jpg, pdf, doc, atau docx.',
                'dokumen_pendukung.max' => 'Ukuran dokumen pendukung maksimal 5MB.',
            ]
        );

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();

            return ApiResponse::format(false, 422, 'Validasi gagal: ' . $firstError, [
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

        if (!$absensi->waktu_pulang) {
            return ApiResponse::format(false, 400, 'Belum absen pulang, durasi lembur tidak dapat dihitung.', null);
        }

        $company = $user->perusahaan;
        if (!$company || !$company->jam_pulang || !$company->jam_masuk) {
            return ApiResponse::format(false, 404, 'Jam kerja perusahaan tidak lengkap.', null);
        }

        $tanggalLembur = $absensi->tanggal
            ? Carbon::parse($absensi->tanggal)->format('Y-m-d')
            : Carbon::parse($absensi->waktu_pulang)->format('Y-m-d');

        $scheduledEnd = Carbon::parse($tanggalLembur . ' ' . $company->jam_pulang);
        $clockOut = Carbon::parse($absensi->waktu_pulang);
        $diffMinutes = $scheduledEnd->diffInMinutes($clockOut, false);

        if ($diffMinutes < 1) {
            return ApiResponse::format(false, 400, 'Durasi lembur tidak valid karena jam pulang tidak melebihi jam kerja.', null);
        }

        $hours = intdiv($diffMinutes, 60);
        $minutes = $diffMinutes % 60;
        $durasi = sprintf('%02d:%02d:00', $hours, $minutes);

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
        $lembur->tanggal_lembur = $tanggalLembur;
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

        return ApiResponse::format(true, 201, 'Berhasil mengajukan lembur.', $data);
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

        $jamMasukPerusahaan = Carbon::parse($company->jam_masuk)->format('H:i:s');
        $jamPulangPerusahaan = Carbon::parse($company->jam_pulang)->format('H:i:s');
        $operationalMinutes = max(
            0,
            Carbon::parse($company->jam_masuk)->diffInMinutes(Carbon::parse($company->jam_pulang), false)
        );
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
                'absensi_id' => null,
                'jam_masuk' => null,
                'status_masuk' => null,
                'jam_pulang' => null,
                'status_pulang' => null,
                'status_absensi' => 'Alfa',
                'eligible_lembur' => false,
                'durasi_lembur_terhitung' => null,
                'durasi_kerja' => null,
                'jam_pulang_perusahaan' => $jamPulangPerusahaan,
                'jam_masuk_perusahaan' => $jamMasukPerusahaan,
                'lembur_pengajuan' => null,
            ];

            if ($record) {
                $entry['absensi_id'] = $record->absensi_id;
                $entry['jam_masuk'] = $record->waktu_masuk ? Carbon::parse($record->waktu_masuk)->format('H:i:s') : null;
                $entry['status_masuk'] = $record->status_masuk;
                $entry['jam_pulang'] = $record->waktu_pulang ? Carbon::parse($record->waktu_pulang)->format('H:i:s') : null;
                $entry['status_pulang'] = $record->status_pulang;
                $entry['status_absensi'] = $record->status_absensi;

                $actualDurationMinutes = null;
                if ($record->waktu_masuk && $record->waktu_pulang) {
                    $actualDurationMinutes = Carbon::parse($record->waktu_masuk)
                        ->diffInMinutes(Carbon::parse($record->waktu_pulang), false);
                    $actualDurationMinutes = max(0, $actualDurationMinutes);
                    if ($actualDurationMinutes > 0) {
                        $entry['durasi_kerja'] = sprintf(
                            '%02d:%02d:00',
                            intdiv($actualDurationMinutes, 60),
                            $actualDurationMinutes % 60
                        );
                    }
                }

                if ($record->waktu_pulang) {
                    $scheduledEnd = Carbon::parse($dateKey . ' ' . $company->jam_pulang);
                    $clockOut = Carbon::parse($record->waktu_pulang);
                    $diffMinutes = $scheduledEnd->diffInMinutes($clockOut, false);
                    $entry['eligible_lembur'] = $diffMinutes >= 60
                        && $actualDurationMinutes !== null
                        && $actualDurationMinutes > $operationalMinutes;

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
