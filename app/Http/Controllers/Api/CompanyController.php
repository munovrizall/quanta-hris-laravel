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
    public function index(Request $request)
    {
        if (!$request->user()) {
            return ApiResponse::format(false, 401, 'Unauthorized', null);
        }

        $companies = Company::all();

        if ($companies->isEmpty()) {
            return ApiResponse::format(false, 404, 'No companies found', null);
        }

        return ApiResponse::format(
            true,
            200,
            'Companies retrieved successfully',
            $companies
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        if (!$request->user()) {
            return ApiResponse::format(false, 401, 'Unauthorized', null);
        }

        $company = Company::find($id);

        if (!$company) {
            return ApiResponse::format(false, 404, 'Company not found', null);
        }

        return ApiResponse::format(
            true,
            200,
            'Company retrieved successfully',
            $company
        );
    }
}
