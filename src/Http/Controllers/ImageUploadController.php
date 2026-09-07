<?php

namespace HolartWeb\AxoraCMS\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    /**
     * Folders an upload is allowed to target. Anything else falls back to "images".
     *
     * @var array<int, string>
     */
    private const ALLOWED_FOLDERS = ['images', 'products', 'catalogs', 'logos', 'content'];

    /**
     * Upload image and return path
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:10240', // 10MB max
            'folder' => 'nullable|string',
        ]);

        try {
            $image = $request->file('image');

            // Whitelist the target folder; never trust client-supplied paths.
            $folder = $request->input('folder', 'images');
            if (! in_array($folder, self::ALLOWED_FOLDERS, true)) {
                $folder = 'images';
            }

            // Derive the extension from the file contents (MIME), not the client name.
            $extension = $image->extension() ?: 'jpg';

            $filename = time().'_'.Str::random(10).'.'.$extension;

            $path = $image->storeAs($folder, $filename, 'public');

            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => asset('storage/'.$path),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке файла',
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
            $path = (string) $request->input('path');

            // Normalise: strip any storage prefix and reject traversal.
            $path = str_replace('/storage/', '', $path);
            $path = ltrim($path, '/');

            if (str_contains($path, '..') || str_contains($path, "\0")) {
                return response()->json([
                    'success' => false,
                    'message' => 'Недопустимый путь',
                ], 422);
            }

            $disk = Storage::disk('public');
            $root = rtrim((string) $disk->path(''), '/');
            $realpath = realpath($disk->path($path));

            if ($realpath === false || ! Str::startsWith($realpath, $root.DIRECTORY_SEPARATOR)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Изображение не найдено',
                ], 404);
            }

            if ($disk->exists($path)) {
                $disk->delete($path);

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
                'message' => 'Ошибка при удалении изображения',
            ], 500);
        }
    }
}
