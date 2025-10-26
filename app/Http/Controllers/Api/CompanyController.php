<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Models\Company;
use App\Models\Cabang;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index(Request $request)
    // {
    //     if (!$request->user()) {
    //         return ApiResponse::format(false, 401, 'Unauthorized', null);
    //     }

    //     $companies = Company::all();

    //     if ($companies->isEmpty()) {
    //         return ApiResponse::format(false, 404, 'No companies found', null);
    //     }

    //     return ApiResponse::format(
    //         true,
    //         200,
    //         'Companies retrieved successfully',
    //         $companies
    //     );
    // }

    public function getCompanyOperationalHours(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->perusahaan) {
            return ApiResponse::format(false, 404, 'Company not found for this user.', null);
        }

        $company = $user->perusahaan;

        return ApiResponse::format(true, 200, 'Operational hours retrieved successfully.', [
            'company_name' => $company->nama_perusahaan,
            'working_hours' => [
                'start_time' => $company->jam_masuk,
                'end_time' => $company->jam_pulang,
            ]
        ]);
    }

    /**
     * Get branches for the authenticated user's company, including coordinates and radius
     */
    public function getCompanyBranches(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->perusahaan) {
            return ApiResponse::format(false, 404, 'Company not found for this user.', null);
        }

        $company = $user->perusahaan;
        $branches = Cabang::where('perusahaan_id', $company->perusahaan_id)
            ->orderBy('nama_cabang')
            ->get(['cabang_id', 'nama_cabang', 'alamat', 'latitude', 'longitude', 'radius_lokasi']);

        if ($branches->isEmpty()) {
            return ApiResponse::format(false, 404, 'No branches found for this company.', null);
        }

        $data = $branches->map(function ($b) {
            return [
                'cabang_id' => $b->cabang_id,
                'nama_cabang' => $b->nama_cabang,
                'alamat' => $b->alamat,
                'latitude' => (float) $b->latitude,
                'longitude' => (float) $b->longitude,
                'radius_lokasi' => (float) $b->radius_lokasi,
            ];
        });

        return ApiResponse::format(true, 200, 'Company branches retrieved successfully.', [
            'perusahaan' => [
                'perusahaan_id' => $company->perusahaan_id,
                'nama_perusahaan' => $company->nama_perusahaan,
            ],
            'branches' => $data,
        ]);
    }
}
