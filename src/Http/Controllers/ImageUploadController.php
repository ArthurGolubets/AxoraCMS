<?php

namespace HolartWeb\HolartCMS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    /**
     * Upload image and return path
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|max:10240', // 10MB max
            'folder' => 'nullable|string',
        ]);

        try {
            $image = $request->file('image');
            $folder = $request->input('folder', 'images');

            // Generate unique filename
            $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();

            // Store in public disk
            $path = $image->storeAs($folder, $filename, 'public');

            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => asset('storage/' . $path),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке файла: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete image from storage
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        try {
            $path = $request->input('path');

            // Remove /storage/ prefix if present
            $path = str_replace('/storage/', '', $path);

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);

                return response()->json([
                    'success' => true,
                    'message' => 'Изображение удалено',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Изображение не найдено',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении изображения: ' . $e->getMessage(),
            ], 500);
        }
    }
}
