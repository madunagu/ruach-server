<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\AddressController;
use App\Http\Controllers\AudioPostController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ChurchController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DevotionalController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HierarchyController;
use App\Http\Controllers\HierarchyTreeController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SocietyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VideoPostController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/login', [LoginController::class, 'login']);

// Route::post('/register', 'Auth\RegisterController@register');



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/addresses', [AddressController::class, 'list']);
    Route::post('/addresses', [AddressController::class, 'create']);
    Route::get('/addresses/{id}', [AddressController::class, 'get']);
    Route::put('/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressController::class, 'delete']);

    Route::get('/churches', [ChurchController::class, 'list']);
    Route::post('/churches', [ChurchController::class, 'create']);
    Route::get('/churches/{id}', [ChurchController::class, 'get']);
    Route::put('/churches/{id}', [ChurchController::class, 'update']);
    Route::delete('/churches/{id}', [ChurchController::class, 'delete']);
    Route::post('/churches/{id}', [ChurchController::class, 'like']);

    Route::get('/audio-posts', [AudioPostController::class, 'list']);
    Route::post('/audio-posts', [AudioPostController::class, 'create']);
    Route::get('/audio-posts/{id}', [AudioPostController::class, 'get']);
    Route::put('/audio-posts/{id}', [AudioPostController::class, 'update']);
    Route::delete('/audio-posts/{id}', [AudioPostController::class, 'delete']);
    Route::post('/audio-posts/{id}', [AudioPostController::class, 'like']);


    Route::get('/video-posts', [VideoPostController::class, 'list']);
    Route::post('/video-posts',  [VideoPostController::class, 'create']);
    Route::get('/video-posts/{id}',  [VideoPostController::class, 'get']);
    Route::put('/video-posts/{id}',  [VideoPostController::class, 'update']);
    Route::delete('/video-posts/{id}',  [VideoPostController::class, 'delete']);
    Route::post('/video-posts/{id}',  [VideoPostController::class, 'like']);

    Route::get('/events',  [EventController::class, 'list']);
    Route::post('/events',  [EventController::class, 'create']);
    Route::get('/events/{id}', [EventController::class, 'get']);
    Route::put('/events/{id}', [EventController::class, 'update']);
    Route::delete('/events/{id}',  [EventController::class, 'delete']);
    Route::post('/events/{id}', [EventController::class, 'attend']);

    Route::get('/devotionals', [DevotionalController::class, 'list']);
    Route::post('/devotionals',  [DevotionalController::class, 'create']);
    Route::get('/devotionals/{id}',  [DevotionalController::class, 'get']);
    Route::put('/devotionals/{id}',  [DevotionalController::class, 'update']);
    Route::delete('/devotionals/{id}',  [DevotionalController::class, 'delete']);
    Route::post('/devotionals/{id}',  [DevotionalController::class, 'devote']);

    Route::get('/hierarchies',  [HierarchyController::class, 'list']);
    Route::post('/hierarchies', [HierarchyController::class, 'create']);
    Route::get('/hierarchies/{id}', [HierarchyController::class, 'get']);
    Route::put('/hierarchies/{id}',  [HierarchyController::class, 'update']);
    Route::delete('/hierarchies/{id}', [HierarchyController::class, 'delete']);

    Route::get('/hierarchy-trees',  [HierarchyTreeController::class, 'list']);
    Route::post('/hierarchy-trees',  [HierarchyTreeController::class, 'create']);
    Route::get('/hierarchy-trees/{id}', [HierarchyTreeController::class, 'get']);
    Route::put('/hierarchy-trees/{id}',  [HierarchyTreeController::class, 'update']);
    Route::delete('/hierarchy-trees/{id}', [HierarchyTreeController::class, 'delete']);

    Route::post('/images', [ImageController::class, 'create']);
    Route::get('/images/{id}',  [ImageController::class, 'get']);
    Route::delete('/images/{id}',  [ImageController::class, 'delete']);

    Route::get('/societies',  [SocietyController::class, 'list']);
    Route::post('/societies',  [SocietyController::class, 'create']);
    Route::get('/societies/{id}',  [SocietyController::class, 'get']);
    Route::put('/societies/{id}',  [SocietyController::class, 'update']);
    Route::delete('/societies/{id}', [SocietyController::class, 'delete']);
    Route::post('/societies/{id}', [SocietyController::class, 'like']);

    Route::get('/users',  [UserController::class, 'list']);
    Route::post('/users',  [UserController::class, 'create']);
    Route::get('/users/{id}',  [UserController::class, 'get']);
    Route::put('/users/{id}',  [UserController::class, 'update']);
    Route::delete('/users/{id}',  [UserController::class, 'delete']);

    Route::get('/comments',  [CommentController::class, 'list']);
    Route::post('/comments',  [CommentController::class, 'create']);
    Route::get('/comments/{id}',  [CommentController::class, 'get']);
    Route::put('/comments/{id}',  [CommentController::class, 'update']);
    Route::delete('/comments/{id}',  [CommentController::class, 'delete']);
    Route::post('/comments/{id}', [CommentController::class, 'like']);

    Route::get('/posts', [PostController::class, 'list']);
    Route::post('/posts', [PostController::class, 'create']);
    Route::get('/posts/{id}', [PostController::class, 'get']);
    Route::put('/posts/{id}', [PostController::class, 'update']);
    Route::delete('/posts/{id}', [PostController::class, 'delete']);
    Route::post('/posts/{id}', [PostController::class, 'like']);

    Route::get('/feed', [FeedController::class, 'load']);
});
