<?php
namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required']
        ]);

        if (!Auth::attempt($credentials)) {
            return ApiResponse::format(false, 401, 'Email atau password salah.', null);
        }

        $user = Auth::user();
        $token = $user->createToken('quanta-hris')->plainTextToken;

        return ApiResponse::format(true, 200, 'Login successful', [
            'token' => $token,
            'user' => [
                'karyawan_id' => $user->karyawan_id,
                'nama_lengkap' => $user->nama_lengkap,
                'email' => $user->email,
                'role' => $user->role,
                'departemen' => $user->departemen,
                'jabatan' => $user->jabatan,
                'nomor_telepon' => $user->nomor_telepon,
                'face_embedding' => $user->face_embedding,
            ]
        ]);

    }

    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->tokens()->where('id', $request->user()->currentAccessToken()->id)->delete();

        return ApiResponse::format(true, 200, 'Logout successful', null);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'face_embedding' => 'required',
        ]);

        $user = $request->user();
        $face_embedding = $request->face_embedding;

        $user->face_embedding = $face_embedding;
        $user->save();

        return ApiResponse::format(true, 200, 'Profile updated', [
            'user' => [
                'karyawan_id' => $user->karyawan_id,
                'nama_lengkap' => $user->nama_lengkap,
                'email' => $user->email,
                'role' => $user->role,
                'departemen' => $user->departemen,
                'jabatan' => $user->jabatan,
                'nomor_telepon' => $user->nomor_telepon,
                'face_embedding' => $user->face_embedding,
            ]
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->karyawan_id) {
            return ApiResponse::format(false, 401, 'Unauthorized', null);
        }

        try {
            $user->load(['role', 'perusahaan', 'golonganPtkp.kategoriTer']);

            $data = [
                'karyawan_id' => $user->karyawan_id,
                'nama_lengkap' => $user->nama_lengkap,
                'email' => $user->email,
                'nomor_telepon' => $user->nomor_telepon,
                'tanggal_lahir' => $user->tanggal_lahir,
                'jenis_kelamin' => $user->jenis_kelamin,
                'alamat' => $user->alamat,
                'jabatan' => $user->jabatan,
                'departemen' => $user->departemen,
                'status_kepegawaian' => $user->status_kepegawaian,
                'status_pernikahan' => $user->status_pernikahan,
                'tanggal_mulai_bekerja' => $user->tanggal_mulai_bekerja,
                'gaji_pokok' => (float) ($user->gaji_pokok ?? 0),
                'tunjangan_jabatan' => $user->tunjangan_jabatan,
                'tunjangan_makan_bulanan' => $user->tunjangan_makan_bulanan,
                'tunjangan_transport_bulanan' => $user->tunjangan_transport_bulanan,
                'kuota_cuti_tahunan' => (int) ($user->kuota_cuti_tahunan ?? 0),
                'nomor_rekening' => $user->nomor_rekening,
                'nama_pemilik_rekening' => $user->nama_pemilik_rekening,
                'nomor_bpjs_kesehatan' => $user->nomor_bpjs_kesehatan,
                'face_embedding' => $user->face_embedding,
                'role' => $user->role ? [
                    'role_id' => $user->role->role_id,
                    'name' => $user->role->name,
                ] : null,
                'perusahaan' => $user->perusahaan ? [
                    'perusahaan_id' => $user->perusahaan->perusahaan_id,
                    'nama_perusahaan' => $user->perusahaan->nama_perusahaan,
                    'email' => $user->perusahaan->email,
                    'nomor_telepon' => $user->perusahaan->nomor_telepon,
                    'jam_masuk' => $user->perusahaan->jam_masuk,
                    'jam_pulang' => $user->perusahaan->jam_pulang,
                ] : null,
                'golongan_ptkp' => $user->golonganPtkp ? [
                    'golongan_ptkp_id' => $user->golonganPtkp->golongan_ptkp_id,
                    'nama_golongan_ptkp' => $user->golonganPtkp->nama_golongan_ptkp,
                    'ptkp_tahunan' => $user->golonganPtkp->ptkp_tahunan,
                    'kategori_ter' => $user->golonganPtkp->kategoriTer ? [
                        'kategori_ter_id' => $user->golonganPtkp->kategoriTer->kategori_ter_id,
                        'nama' => $user->golonganPtkp->kategoriTer->nama,
                    ] : null,
                ] : null,
            ];

            return ApiResponse::format(true, 200, 'Profile retrieved successfully', $data);
        } catch (\Exception $e) {
            Log::error('Error building profile: ' . $e->getMessage());
            return ApiResponse::format(false, 500, 'Gagal mengambil profil.', null);
        }
    }
}
