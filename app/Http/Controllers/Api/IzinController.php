<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Izin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IzinController extends Controller
{
    /**
     * Display permission request history for the authenticated employee.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->karyawan_id) {
            return ApiResponse::format(false, 401, 'Unauthorized', null);
        }

        $history = Izin::where('karyawan_id', $user->karyawan_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Izin $izin) {
                return [
                    'izin_id' => $izin->izin_id,
                    'jenis_izin' => $izin->jenis_izin,
                    'tanggal_mulai' => optional($izin->tanggal_mulai)->format('Y-m-d'),
                    'tanggal_selesai' => optional($izin->tanggal_selesai)->format('Y-m-d'),
                    'status_izin' => $izin->status_izin,
                    'alasan_penolakan' => $izin->alasan_penolakan ?: null,
                    'dokumen_pendukung' => $izin->dokumen_pendukung
                        ? asset('storage/' . $izin->dokumen_pendukung)
                        : null,
                    'diproses_oleh' => $izin->approver_id,
                    'diproses_pada' => optional($izin->processed_at)->toDateTimeString(),
                    'dibuat_pada' => optional($izin->created_at)->toDateTimeString(),
                    'diperbarui_pada' => optional($izin->updated_at)->toDateTimeString(),
                ];
            });

        return ApiResponse::format(true, 200, 'Riwayat izin berhasil diambil.', [
            'karyawan_id' => $user->karyawan_id,
            'total_pengajuan' => $history->count(),
            'riwayat' => $history,
        ]);
    }

    /**
     * Store a newly created Izin (permission request).
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->karyawan_id) {
            return ApiResponse::format(false, 401, 'Unauthorized', null);
        }

        // Validation mirrors Filament create fields; file stored as path only
        $validator = Validator::make($request->all(), [
            'jenis_izin' => 'required|string|max:100',
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

        // Generate next custom ID like Filament (IZ0001, includes soft-deleted)
        $last = Izin::withTrashed()
            ->where('izin_id', 'like', 'IZ%')
            ->orderBy('izin_id', 'desc')
            ->first();
        $nextNumber = $last ? intval(str_replace('IZ', '', $last->izin_id)) + 1 : 1;
        $izinId = 'IZ' . str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);

        // Handle optional supporting document upload; save path only
        $dokumenPath = null;
        if ($request->hasFile('dokumen_pendukung')) {
            $file = $request->file('dokumen_pendukung');
            $ext = $file->getClientOriginalExtension();
            $fileName = 'izin_' . $user->karyawan_id . '_' . time() . '.' . $ext;
            $dokumenPath = $file->storeAs('izin/dokumen', $fileName, 'public');
        }

        // Persist permission request with status forced to Diajukan
        $izin = new Izin();
        $izin->izin_id = $izinId;
        $izin->karyawan_id = $user->karyawan_id;
        $izin->jenis_izin = $request->jenis_izin;
        $izin->tanggal_mulai = $request->tanggal_mulai;
        $izin->tanggal_selesai = $request->tanggal_selesai;
        $izin->keterangan = $request->keterangan;
        $izin->dokumen_pendukung = $dokumenPath ?? null;
        $izin->status_izin = 'Diajukan';
        $izin->alasan_penolakan = null;
        $izin->approver_id = null;
        $izin->processed_at = null;
        $izin->save();

        // Build response payload; expose full URL for document similar to attendance photo
        $data = [
            'izin_id' => $izin->izin_id,
            'karyawan_id' => $izin->karyawan_id,
            'jenis_izin' => $izin->jenis_izin,
            'tanggal_mulai' => $izin->tanggal_mulai,
            'tanggal_selesai' => $izin->tanggal_selesai,
            'keterangan' => $izin->keterangan,
            'status_izin' => $izin->status_izin,
            'dokumen_pendukung' => $dokumenPath ? asset('storage/' . $dokumenPath) : null,
            'created_at' => $izin->created_at,
        ];

        return ApiResponse::format(true, 201, 'Pengajuan izin berhasil diajukan.', $data);
    }
}
