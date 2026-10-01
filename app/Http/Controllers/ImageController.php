<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use App\Models\Image;

use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:12288'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'The image upload is invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = Auth::id();
        $data = [];
        $disk = Storage::disk('public');
        $manager = new ImageManager(
            new \Intervention\Image\Drivers\Gd\Driver()
        );

        foreach ($request->file('photos') as $key => $file) {
            $createdPaths = [];

            try {
                $name = time() . '-' . $key . '-' . bin2hex(random_bytes(4)) . '.jpg';

                // Store the original upload.
                $disk->put('images/full/' . $name, $file->get());

                // Read file contents directly (works in HTTP context).
                $image = $manager->read($file->get());

                // Resize from the full image each time so every variant is
                // derived from the original rather than from a chain of
                // progressive downscales, which compounds quality loss.
                //
                // `resize` would force the exact width, distorting anything
                // that is already narrower and upscaling small originals.
                // `scaleDown` fits the image inside the box, preserving the
                // aspect ratio and never enlarging it.
                foreach ([['large', 500], ['medium', 200], ['small', 100]] as [$variant, $width]) {
                    $resize = (clone $image)->scaleDown(width: $width);
                    $disk->put(
                        'images/' . $variant . '/' . $name,
                        (string) $resize->encodeByExtension('jpg', quality: 85)
                    );
                }
                $createdPaths = [
                    'images/full/' . $name,
                    'images/large/' . $name,
                    'images/medium/' . $name,
                    'images/small/' . $name,
                ];
            } catch (\Throwable $e) {
                report($e);
                foreach ($createdPaths as $createdPath) {
                    $disk->delete($createdPath);
                }
                return response()->json([
                    'message' => 'One of the images could not be processed.',
                    'errors' => ['photos.' . $key => ['Invalid image file.']],
                ], 422);
            }

            $model = null;
            try {
                $model = Image::create([
                    'full' => $disk->url('images/full/' . $name),
                    'large' => $disk->url('images/large/' . $name),
                    'medium' => $disk->url('images/medium/' . $name),
                    'small' => $disk->url('images/small/' . $name),
                    'user_id' => $userId,
                ]);
                $data[] = $model;
            } catch (\Throwable $e) {
                foreach ($createdPaths as $createdPath) {
                    $disk->delete($createdPath);
                }
                if (isset($model)) {
                    $model->delete();
                }
                return response()->json([
                    'message' => 'The image record could not be created.',
                    'errors' => ['photos.' . $key => ['Could not save this image.']],
                ], 500);
            }
        }

        if ($data) {
            return response()->json(['data' => $data], 201);
        }
        return response()->json([
            'data' => false,
            'errors' => 'No images were created.',
        ], 400);
    }

    public function get(Request $request)
    {
        $id = (int)$request->route('id');
        if ($image = Image::find($id)) {
            return response()->json([
                'data' => $image
            ], 200);
        } else {
            return response()->json([
                'data' => false
            ], 404);
        }
    }


    public function delete(Request $request)
    {
        $id = (int)$request->route('id');
        $image = Image::find($id);
        if (!$image) {
            return response()->json(['data' => false], 404);
        }

        $disk = Storage::disk('public');
        foreach (['full', 'large', 'medium', 'small'] as $variant) {
            $url = $image->{$variant};
            if (!is_string($url) || $url === '') {
                continue;
            }
            $path = str_replace($disk->url(''), '', $url);
            if ($path !== $url && str_starts_with($path, 'images/')) {
                $disk->delete($path);
            }
        }
        $image->delete();

        return response()->json(['data' => true], 200);
    }
}
