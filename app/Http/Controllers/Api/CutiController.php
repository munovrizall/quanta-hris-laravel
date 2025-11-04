<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Cuti;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CutiController extends Controller
{
    /**
     * Display leave history for the authenticated employee.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->karyawan_id) {
            return ApiResponse::format(false, 401, 'Unauthorized', null);
        }

        $history = Cuti::where('karyawan_id', $user->karyawan_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Cuti $cuti) {
                $mulai = $cuti->tanggal_mulai ? Carbon::parse($cuti->tanggal_mulai) : null;
                $selesai = $cuti->tanggal_selesai ? Carbon::parse($cuti->tanggal_selesai) : null;
                $durasi = ($mulai && $selesai) ? ($mulai->diffInDays($selesai) + 1) : null;

                return [
                    'cuti_id' => $cuti->cuti_id,
                    'jenis_cuti' => $cuti->jenis_cuti,
                    'tanggal_mulai' => optional($mulai)->toDateString(),
                    'tanggal_selesai' => optional($selesai)->toDateString(),
                    'durasi_hari' => $durasi,
                    'status_cuti' => $cuti->status_cuti,
                    'alasan_penolakan' => $cuti->alasan_penolakan ?: null,
                    'dokumen_pendukung' => $cuti->dokumen_pendukung
                        ? asset('storage/' . $cuti->dokumen_pendukung)
                        : null,
                    'diproses_oleh' => $cuti->approver_id,
                    'diproses_pada' => optional($cuti->processed_at)->toDateTimeString(),
                    'dibuat_pada' => optional($cuti->created_at)->toDateTimeString(),
                    'diperbarui_pada' => optional($cuti->updated_at)->toDateTimeString(),
                ];
            });

        return ApiResponse::format(true, 200, 'Riwayat cuti berhasil diambil.', [
            'karyawan_id' => $user->karyawan_id,
            'sisa_kuota_cuti' => (int) ($user->kuota_cuti_tahunan ?? 0),
            'satuan_kuota' => 'hari',
            'total_pengajuan' => $history->count(),
            'riwayat' => $history,
        ]);
    }

    /**
     * Store a newly created Cuti (leave request).
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->karyawan_id) {
            return ApiResponse::format(false, 401, 'Unauthorized', null);
        }

        // Validation mirrors Filament create fields; file stored as path only
        $validator = Validator::make($request->all(), [
            'jenis_cuti' => 'required|string|max:100',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan' => 'required|string',
            'dokumen_pendukung' => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx|max:5120',
        ]);

        if ($validator->fails()) {
            return ApiResponse::format(false, 422, 'Validation error', [
                'errors' => $validator->errors(),
            ]);
        }

        // Validate leave duration against quota
        $mulai = Carbon::parse($request->tanggal_mulai);
        $selesai = Carbon::parse($request->tanggal_selesai);
        $durasiHari = $mulai->diffInDays($selesai) + 1; // inklusif
        $kuota = (int) ($user->kuota_cuti_tahunan ?? 0);
        if ($kuota <= 0) {
            return ApiResponse::format(false, 400, 'Kuota cuti tahunan habis.', null);
        }
        if ($durasiHari > $kuota) {
            return ApiResponse::format(false, 400, 'Durasi cuti melebihi kuota yang tersedia.', [
                'kuota_tersedia' => $kuota,
                'durasi_diajukan' => $durasiHari,
            ]);
        }

        // Generate next custom ID like Filament (CT0001, includes soft-deleted)
        $last = Cuti::withTrashed()
            ->where('cuti_id', 'like', 'CT%')
            ->orderBy('cuti_id', 'desc')
            ->first();
        $nextNumber = $last ? intval(str_replace('CT', '', $last->cuti_id)) + 1 : 1;
        $cutiId = 'CT' . str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);

        // Handle optional supporting document upload; save path only
        $dokumenPath = null;
        if ($request->hasFile('dokumen_pendukung')) {
            $file = $request->file('dokumen_pendukung');
            $ext = $file->getClientOriginalExtension();
            $fileName = 'cuti_' . $user->karyawan_id . '_' . time() . '.' . $ext;
            $dokumenPath = $file->storeAs('cuti/dokumen', $fileName, 'public');
        }

        // Persist leave request with status forced to Diajukan
        $cuti = new Cuti();
        $cuti->cuti_id = $cutiId;
        $cuti->karyawan_id = $user->karyawan_id;
        $cuti->jenis_cuti = $request->jenis_cuti;
        $cuti->tanggal_mulai = $request->tanggal_mulai;
        $cuti->tanggal_selesai = $request->tanggal_selesai;
        $cuti->keterangan = $request->keterangan;
        $cuti->dokumen_pendukung = $dokumenPath ?? null;
        $cuti->status_cuti = 'Diajukan';
        $cuti->alasan_penolakan = null;
        $cuti->approver_id = null;
        $cuti->processed_at = null;
        $cuti->save();

        // Build response payload; expose full URL for document like attendance photo
        $data = [
            'cuti_id' => $cuti->cuti_id,
            'karyawan_id' => $cuti->karyawan_id,
            'jenis_cuti' => $cuti->jenis_cuti,
            'tanggal_mulai' => $cuti->tanggal_mulai,
            'tanggal_selesai' => $cuti->tanggal_selesai,
            'keterangan' => $cuti->keterangan,
            'status_cuti' => $cuti->status_cuti,
            'dokumen_pendukung' => $dokumenPath ? asset('storage/' . $dokumenPath) : null,
            'durasi_hari' => $durasiHari,
            'created_at' => $cuti->created_at,
        ];

        return ApiResponse::format(true, 201, 'Pengajuan cuti berhasil diajukan.', $data);
    }
}
