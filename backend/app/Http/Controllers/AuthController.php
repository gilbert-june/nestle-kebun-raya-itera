<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Enums\RoleEnum;


class AuthController extends Controller
{
    /**
     * Redirect the user to the Google OAuth provider.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::updateOrCreate([
                'email' => $googleUser->email,
            ], [
                'google_id' => $googleUser->id,
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'avatar' => $googleUser->avatar,
            ]);

            // Assign role pengunjung
            $user->assignRole(RoleEnum::PENGUNJUNG);

            // Create Sanctum token for API access
            $token = $user->createToken('google-auth-token')->plainTextToken;

            // Redirect to Angular frontend oauth-callback with token (URL-encoded)
            return redirect()->away(env('FRONTEND_URL') . '/oauth-callback?token=' . urlencode($token));

        } catch (\Exception $e) {
            // Redirect to login page with error
            return redirect()->away(env('FRONTEND_URL') . '/login?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Logout the user.
     */
    public function logout(Request $request)
    {
        try {
            // Revoke all tokens for the authenticated user
            if ($request->user()) {
                $request->user()->tokens()->delete();
            }
            
            // Also handle session logout for web routes
            if (Auth::check()) {
                Auth::logout();
            }
            
            // Always invalidate and regenerate session
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);
        } catch (\Exception $e) {
            // Even if there's an error, try to clear the session
            try {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            } catch (\Exception $sessionError) {
                // Ignore session errors
            }

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        }
    }

    /**
     * Get the authenticated user.
     */
    public function user(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        // Append Role
        $user->role = $user->getRoleNames()->first();

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }
}
