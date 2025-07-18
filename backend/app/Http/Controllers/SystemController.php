<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\System;

class SystemController extends Controller
{
    /**
     * Get paginated list of users
     */
    public function index(Request $request): JsonResponse
    {
        $system = System::first();
        return response()->json([
            'success' => true,
            'data' => $system
        ]);
    }

    /**
     * Toggle user access permission (admin role)
     */
    public function toggleActivate(Request $request, $id): JsonResponse
    {
        $system = System::first();
        $system->is_active = !$system->is_active;
        $system->save();
        return response()->json([
            'success' => true,
            'data' => $system
        ]);
    }
} 