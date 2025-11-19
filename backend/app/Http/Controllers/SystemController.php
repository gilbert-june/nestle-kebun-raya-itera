<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\System;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SystemController extends Controller
{
    /**
     * Ensure we always have a single system row to work with.
     */
    private function getSystemRecord(): System
    {
        $system = System::first();
        if (!$system) {
            $system = System::create([
                'is_active' => false,
            ]);
        }

        return $system;
    }

    public function index(Request $request): JsonResponse
    {
        $system = $this->getSystemRecord();
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
        $system = $this->getSystemRecord();
        $system->is_active = !boolval($system->is_active);
        $system->save();
        return response()->json([
            'success' => true,
            'data' => $system
        ]);
    }


    public function updatePumpStatus(Request $request): JsonResponse
    {
        Log::info('Updating pump status: ' . $request->status);
        $statusValue = filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $request->merge([
            'status' => $statusValue,
        ]);

        $request->validate([
            'status' => 'required|boolean',
        ]);

        Log::info("starting update pump status");

        try {
            $system = $this->getSystemRecord();
            Log::info("System: " . json_encode($system));
            $system->is_active = boolval($request->status);
            $system->save();

            Log::info("System saved: " . json_encode($system));

            return response()->json([
                'success' => true,
                'data' => $system
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update pump status: ' . $e->getMessage()
            ], 500);
        }
    }
} 