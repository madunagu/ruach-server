<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\File;
use Validator;

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
        // $validator =  Validator::make($request->all(), [
        //     'photos' => 'required',
        //     'photos.*' => 'mimes:jpg,png,gif'
        // ]);

        // if ($validator->fails()) {
        //     return response()->json($validator->messages(), 422);
        // }

        $userId = Auth::user()->id;
        $data = [];

        foreach ($request['photos']  as $key => $photo) {
            // $name = time() . '.' . $photo->getClientOriginalExtension();
            $name = time() . $key . '.jpg';
            Storage::put('public/images/full/' . $name, $photo);
            // $image_resize = ImageManager::make($photo);
            $manager = new ImageManager(
                new \Intervention\Image\Drivers\Gd\Driver()
            );
            $image_resize = $manager->read($photo);

            $image_resize->resize(width: 500);
            // dd(storage_path('app/public/images/large/'.$name));
            $image_resize->save(storage_path('app/public/images/large/' . $name));
            // $save = Storage::putFileAs("public/images/large", new File('images/replacer'), $name);

            $image_resize->resize(width: 200);
            $image_resize->save(storage_path('app/public/images/medium/' . $name));
            // $save = Storage::putFileAs("public/images/medium", new File('images/replacer'), $name);

            $image_resize->resize(width: 100);
            // $image_resize->save('images/replacer');
            $image_resize->save(storage_path('app/public/images/small/' . $name));


            // $save = Storage::putFileAs("public/images/small", new File('images/replacer'), $name);

            $image = Image::create([
                'photo' => $photo,
                'full' => Storage::disk('public')->url('images/full/' . $name),
                'large' =>  Storage::disk('public')->url('images/large/' . $name),
                'medium' =>  Storage::disk('public')->url('images/medium/' . $name),
                'small' =>  Storage::disk('public')->url('images/small/' . $name),
                'user_id' => $userId
            ]);

            $data[] = $image;
        }



        if ($data) {
            return response()->json(['data' => $data], 201);
        } else {
            return response()->json(['data' => false, 'errors' => 'unknown error occured'], 400);
        }
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
        if ($image = Image::find($id)) {
            $image->delete();
            return response()->json([
                'data' => true
            ], 200);
        } else {
            return response()->json([
                'data' => false
            ], 404);
        }
    }
}
