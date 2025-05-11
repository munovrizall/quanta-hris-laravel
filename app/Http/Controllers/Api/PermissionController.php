<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    /**
     * Store a new permission request
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'date_permission' => 'required|date',
            'reason' => 'required|string',
            'image' => 'nullable|image|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return ApiResponse::format(
                false,
                422,
                'Validation error',
                ['errors' => $validator->errors()]
            );
        }

        // Handle file upload if provided
        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = $image->store('permission-documents', 'public');
        }

        // Create the permission record
        $permission = Permission::create([
            'user_id' => $request->user_id,
            'date_permission' => $request->date_permission,
            'reason' => $request->reason,
            'image' => $imagePath,
            'approval_status' => 'pending' // Default status
        ]);

        return ApiResponse::format(
            true,
            201,
            'Permission request created successfully',
            $permission
        );
    }
}