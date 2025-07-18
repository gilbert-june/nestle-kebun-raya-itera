<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Enums\RoleEnum;

class UserController extends Controller
{
    /**
     * Get paginated list of users
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $perPage = min($perPage, 100); // Limit maximum per page
        
        $users = User::with('roles')
            ->select([
                'id',
                'name',
                'email',
                'avatar',
                'google_id'
            ])
            ->orderBy('name')
            ->paginate($perPage);
        
        // Transform the data to include can_access based on peneliti role
        $users->getCollection()->transform(function ($user) {
            $user->can_access = $user->hasRole(RoleEnum::PENELITI);
            return $user;
        });
        
        return response()->json($users);
    }

    /**
     * Toggle user access permission (admin role)
     */
    public function toggleAccess(Request $request, $id): JsonResponse
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        try {
            if ($user->hasRole(RoleEnum::PENELITI)) {
                // Remove peneliti role
                $user->removeRole(RoleEnum::PENELITI);
                $user->assignRole(RoleEnum::PENGUNJUNG);
                $message = 'Peneliti access removed successfully';
            } else {
                // Add peneliti role
                $user->assignRole(RoleEnum::PENELITI);
                $user->removeRole(RoleEnum::PENGUNJUNG);
                $message = 'Peneliti access granted successfully';
            }
            
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update access: ' . $e->getMessage()
            ], 500);
        }
    }
} 