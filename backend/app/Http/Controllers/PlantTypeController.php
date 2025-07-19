<?php

namespace App\Http\Controllers;

use App\Models\PlantType;
use App\Models\PlantTypeImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PlantTypeController extends Controller
{
    /**
     * Display a listing of plant types with their images.
     */
    public function index()
    {
        $plantTypes = PlantType::with(['images' => function ($query) {
            $query->orderBy('is_thumbnail', 'desc')->orderBy('created_at', 'asc');
        }])->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $plantTypes
        ]);
    }

    /**
     * Store a newly created plant type with images.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:10048',
            'thumbnail_index' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create plant type
            $plantType = PlantType::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            // Handle image uploads
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $thumbnailIndex = $request->thumbnail_index;

                foreach ($images as $index => $image) {
                    $filename = time() . '_' . $index . '.' . $image->getClientOriginalExtension();
                    $path = $image->storeAs('plant-types', $filename, 'public');

                    PlantTypeImage::create([
                        'plant_type_id' => $plantType->id,
                        'image' => $path,
                        'is_thumbnail' => $index == $thumbnailIndex
                    ]);
                }
            }

            // Load the created plant type with images
            $plantType->load(['images' => function ($query) {
                $query->orderBy('is_thumbnail', 'desc')->orderBy('created_at', 'asc');
            }]);

            return response()->json([
                'success' => true,
                'message' => 'Plant type created successfully',
                'data' => $plantType
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create plant type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified plant type with images.
     */
    public function show($id)
    {
        $plantType = PlantType::with(['images' => function ($query) {
            $query->orderBy('is_thumbnail', 'desc')->orderBy('created_at', 'asc');
        }])->find($id);

        if (!$plantType) {
            return response()->json([
                'success' => false,
                'message' => 'Plant type not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $plantType
        ]);
    }

    /**
     * Update the specified plant type.
     */
    public function update(Request $request, $id)
    {
        $plantType = PlantType::find($id);

        if (!$plantType) {
            return response()->json([
                'success' => false,
                'message' => 'Plant type not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $plantType->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            $plantType->load(['images' => function ($query) {
                $query->orderBy('is_thumbnail', 'desc')->orderBy('created_at', 'asc');
            }]);

            return response()->json([
                'success' => true,
                'message' => 'Plant type updated successfully',
                'data' => $plantType
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update plant type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified plant type and its images.
     */
    public function destroy($id)
    {
        $plantType = PlantType::with('images')->find($id);

        if (!$plantType) {
            return response()->json([
                'success' => false,
                'message' => 'Plant type not found'
            ], 404);
        }

        try {
            // Delete associated images from storage
            foreach ($plantType->images as $image) {
                if (Storage::disk('public')->exists($image->image)) {
                    Storage::disk('public')->delete($image->image);
                }
            }

            // Delete associated images from database first
            $plantType->images()->delete();

            // Now delete the plant type
            $plantType->delete();

            return response()->json([
                'success' => true,
                'message' => 'Plant type deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete plant type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update images for a plant type.
     */
    public function updateImages(Request $request, $id)
    {
        $plantType = PlantType::find($id);

        if (!$plantType) {
            return response()->json([
                'success' => false,
                'message' => 'Plant type not found'
            ], 404);
        }

        // Only validate images if they are being uploaded
        $validationRules = [];
        if ($request->hasFile('images') && count($request->file('images')) > 0) {
            $validationRules = [
                'images' => 'required|array|min:1',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:10048',
                'thumbnail_index' => 'required|integer|min:0'
            ];
        }

        if (!empty($validationRules)) {
            $validator = Validator::make($request->all(), $validationRules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
        }

        try {
            // Only process images if they are being uploaded
            if ($request->hasFile('images') && count($request->file('images')) > 0) {
                // Delete existing images
                foreach ($plantType->images as $image) {
                    if (Storage::disk('public')->exists($image->image)) {
                        Storage::disk('public')->delete($image->image);
                    }
                }
                $plantType->images()->delete();

                // Upload new images
                $images = $request->file('images');
                $thumbnailIndex = $request->thumbnail_index;

                foreach ($images as $index => $image) {
                    $filename = time() . '_' . $index . '.' . $image->getClientOriginalExtension();
                    $path = $image->storeAs('plant-types', $filename, 'public');

                    PlantTypeImage::create([
                        'plant_type_id' => $plantType->id,
                        'image' => $path,
                        'is_thumbnail' => $index == $thumbnailIndex
                    ]);
                }
            }

            $plantType->load(['images' => function ($query) {
                $query->orderBy('is_thumbnail', 'desc')->orderBy('created_at', 'asc');
            }]);

            return response()->json([
                'success' => true,
                'message' => 'Plant type images updated successfully',
                'data' => $plantType
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update plant type images',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update images for a plant type with partial updates (keep existing, delete some, add new).
     */
    public function updateImagesPartial(Request $request, $id)
    {
        $plantType = PlantType::find($id);

        if (!$plantType) {
            return response()->json([
                'success' => false,
                'message' => 'Plant type not found'
            ], 404);
        }

        try {
            // Handle image deletions
            if ($request->has('images_to_delete')) {
                $imagesToDelete = json_decode($request->images_to_delete, true);
                if (is_array($imagesToDelete)) {
                    foreach ($imagesToDelete as $imageId) {
                        $image = PlantTypeImage::find($imageId);
                        if ($image && $image->plant_type_id == $plantType->id) {
                            // Delete from storage
                            if (Storage::disk('public')->exists($image->image)) {
                                Storage::disk('public')->delete($image->image);
                            }
                            // Delete from database
                            $image->delete();
                        }
                    }
                }
            }

            // Handle existing images updates (thumbnail changes)
            if ($request->has('existing_images')) {
                $existingImages = json_decode($request->existing_images, true);
                if (is_array($existingImages)) {
                    foreach ($existingImages as $imageData) {
                        $image = PlantTypeImage::find($imageData['id']);
                        if ($image && $image->plant_type_id == $plantType->id) {
                            $image->update([
                                'is_thumbnail' => $imageData['is_thumbnail']
                            ]);
                        }
                    }
                }
            }

            // Handle new image uploads
            if ($request->hasFile('images') && count($request->file('images')) > 0) {
                $images = $request->file('images');
                $thumbnailIndex = $request->get('thumbnail_index', -1);

                foreach ($images as $index => $image) {
                    $filename = time() . '_' . $index . '.' . $image->getClientOriginalExtension();
                    $path = $image->storeAs('plant-types', $filename, 'public');

                    PlantTypeImage::create([
                        'plant_type_id' => $plantType->id,
                        'image' => $path,
                        'is_thumbnail' => $index == $thumbnailIndex
                    ]);
                }
            }

            $plantType->load(['images' => function ($query) {
                $query->orderBy('is_thumbnail', 'desc')->orderBy('created_at', 'asc');
            }]);

            return response()->json([
                'success' => true,
                'message' => 'Plant type images updated successfully',
                'data' => $plantType
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update plant type images',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 