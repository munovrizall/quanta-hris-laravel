<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!$request->user()) {
            return ApiResponse::format(false, 401, 'Unauthorized', null);
        }

        $sites = Site::all();

        if ($sites->isEmpty()) {
            return ApiResponse::format(false, 404, 'No sites found', null);
        }

        return ApiResponse::format(
            true,
            200,
            'Sites retrieved successfully',
            $sites
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

        $site = Site::find($id);

        if (!$site) {
            return ApiResponse::format(false, 404, 'Site not found', null);
        }

        return ApiResponse::format(
            true,
            200,
            'Site retrieved successfully',
            $site
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Site $site)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Site $site)
    {
        //
    }
}
