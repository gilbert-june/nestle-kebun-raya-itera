<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Alert;

class AlertController extends Controller
{
    /**
     * Get paginated list of users
     */
    public function index(Request $request): JsonResponse
    {
        $alert = Alert::where('name', 'Alert')->first();
        if(empty($alert)) {
            $alert = Alert::create([
                'name' => 'Alert',
                'type' => 'Warning',
                'description' => 'The water is cloudy, fogging cannot be done. The water is not enough, fogging cannot be done.',
                'result' => '',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $alert
        ]);
    }

    /**
     * Toggle user access permission (admin role)
     */
    public function updateAlert(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|max:255',
            'description' => 'required|string|max:255'
        ]);

        $alert = Alert::where('name', 'Alert')->first();
        if(empty($alert)) {
            $alert = Alert::create([
                'name' => 'Alert',
                'type' => 'Warning',
                'description' => 'The water is cloudy, fogging cannot be done. The water is not enough, fogging cannot be done.',
                'result' => '',
            ]);
        }

        $alert = Alert::where('name', 'Alert')->first();
        $alert->update([
            'type' => $request->type,
            'description' => $request->description,
        ]);
        
        return response()->json([
            'success' => true,
            'data' => $alert
        ]);
    }
} 