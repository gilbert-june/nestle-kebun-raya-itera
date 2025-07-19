<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\About;

class AboutController extends Controller
{
    /**
     * Get paginated list of users
     */
    public function index(Request $request): JsonResponse
    {
        $about = About::where('identifier', 'DEFAULT')->first();
        if(empty($about)) {
            $about = About::create([
                'identifier' => 'DEFAULT',
                'title' => 'About',
                'description' => 'About',
            ]);
        }
        
        return response()->json([
            'success' => true,
            'data' => $about
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        // validate request
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10048',
        ]);

        $about = About::where('identifier', 'DEFAULT')->first();
        
        if (!$about) {
            return response()->json([
                'success' => false,
                'message' => 'About data not found'
            ], 404);
        }

        $updateData = [
            'title' => $request->title,
            'description' => $request->description,
        ];

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($about->image && file_exists(storage_path('app/public/' . $about->image))) {
                unlink(storage_path('app/public/' . $about->image));
            }

            // Store new image
            $imagePath = $request->file('image')->store('about', 'public');
            $updateData['image'] = $imagePath;
        }

        $about->update($updateData);
        
        return response()->json([
            'success' => true,
            'data' => $about
        ]);
    }
} 