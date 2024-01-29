<?php

namespace App\Traits;

use App\Http\Controllers\AudioPostController;
use App\Http\Controllers\ChurchController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DevotionalController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SocietyController;
use App\Http\Controllers\VideoPostController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Like;

trait Interactable
{
    public  $models = [
        AudioPostController::class => 'audio',
        VideoPostController::class => 'video',
        ChurchController::class => 'church',
        EventController::class => 'event',
        SocietyController::class => 'society',
        PostController::class => 'post',
        DevotionalController::class => 'devotional',
        CommentController::class => 'comment',
    ];

    function saveRelated(array $data, Model $created = null): array
    {
        //TODO: check if method exist to avoid obvios errors on these
        //TODO: remove previous relationships before adding new
        if (!empty($data['church_id'])) {
            $created->churches()->attach((int)$data['church_id']);
        }
        if (!empty($data['address_ids'])) {
            $created->addresses()->attach($data['address_ids']);
        }
        if (!empty($data['image_ids'])) {
            $created->images()->attach($data['image_ids']);
        }
        if (!empty($data['tag_ids'])) {
            $created->tags()->attach($data['tag_ids']);
        }
        if (!empty($data['hierarchy_ids'])) {
            $created->hierarchies()->attach($data['hierarchy_ids']);
        }
        return $data;
    }

    public function like(Request $request)
    {

        $user_id = Auth::user()->id;
        $id = (int)$request->route('id');
        $type = $this->models[static::class];
        if ($like = Like::where('user_id', $user_id)->where('likeable_id', $id)->where('likeable_type', $type)->first()) {
            $like->delete();
            return response()->json(['data' => false], 200);
        } else {
            Like::create(
                [
                    'user_id' => $user_id,
                    'likeable_id' => $id,
                    'likeable_type' => $type
                ]
            );
            return response()->json(['data' => true], 200);
        }
    }
}
