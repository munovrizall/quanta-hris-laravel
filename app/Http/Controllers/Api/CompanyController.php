<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Models\Company;
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
}
