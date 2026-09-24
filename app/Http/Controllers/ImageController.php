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

    // public function create(Request $request)
    // {
    //     $validator =  Validator::make($request->all(), [
    //         'photo' => 'required',
    //         'photo.*' => 'mimes:jpg,png,gif'
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($validator->messages(), 422);
    //     }

    //     $userId = Auth::user()->id;
    //     $photo = $request['photo'] ;

    //     $name = time() . '.jpg';
    //     Storage::put('public/images/full/' . $name, $photo);
    //     // $image_resize = ImageManager::make($photo);
    //     $manager = new ImageManager(
    //         new \Intervention\Image\Drivers\Gd\Driver()
    //     );
    //     $image_resize = $manager->read($photo);

    //     $image_resize->resize(500, 500);
    //     // dd(storage_path('app/public/images/large/'.$name));
    //     $image_resize->save(storage_path('app/public/images/large/' . $name));
    //     // $save = Storage::putFileAs("public/images/large", new File('images/replacer'), $name);

    //     $image_resize->resize(200, 200);
    //     $image_resize->save(storage_path('app/public/images/medium/' . $name));
    //     // $save = Storage::putFileAs("public/images/medium", new File('images/replacer'), $name);

    //     $image_resize->resize(100, 100);
    //     // $image_resize->save('images/replacer');
    //     $image_resize->save(storage_path('app/public/images/small/' . $name));


    //     // $save = Storage::putFileAs("public/images/small", new File('images/replacer'), $name);

    //     $data = Image::create([
    //         'photo' => $photo,
    //         'full' => Storage::disk('public')->url('images/full/' . $name),
    //         'large' =>  Storage::disk('public')->url('images/large/' . $name),
    //         'medium' =>  Storage::disk('public')->url('images/medium/' . $name),
    //         'small' =>  Storage::disk('public')->url('images/small/' . $name),
    //         'user_id' => $userId
    //     ]);


    //     if ($data) {
    //         return response()->json(['data' => $data], 201);
    //     } else {
    //         return response()->json(['data' => false, 'errors' => 'unknown error occured'], 400);
    //     }
    // }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => ['required', 'string', 'max:14000000'],
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

        foreach ($request->input('photos') as $key => $encodedPhoto) {
            $createdPaths = [];
            $decoded = base64_decode($encodedPhoto, true);
            if ($decoded === false || $decoded === '') {
                return response()->json([
                    'message' => 'One of the images was not valid base64.',
                    'errors' => ['photos.' . $key => ['Invalid image data.']],
                ], 422);
            }
            if (!@getimagesizefromstring($decoded)) {
                return response()->json([
                    'message' => 'One of the images could not be processed.',
                    'errors' => ['photos.' . $key => ['Invalid image file.']],
                ], 422);
            }

            try {
                $name = time() . '-' . $key . '-' . bin2hex(random_bytes(4)) . '.jpg';
                $disk->put('images/full/' . $name, $decoded);
                $image = $manager->read($decoded);

                $image->resize(width: 500);
                $image->save(storage_path('app/public/images/large/' . $name));
                $image->resize(width: 200);
                $image->save(storage_path('app/public/images/medium/' . $name));
                $image->resize(width: 100);
                $image->save(storage_path('app/public/images/small/' . $name));
                $createdPaths = [
                    'images/full/' . $name,
                    'images/large/' . $name,
                    'images/medium/' . $name,
                    'images/small/' . $name,
                ];
            } catch (\Throwable $e) {
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
