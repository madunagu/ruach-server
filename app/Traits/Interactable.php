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
use App\Services\ActivityNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use App\Models\Like;

trait Interactable
{
    public $models = [
        AudioPostController::class => 'audio',
        VideoPostController::class => 'video',
        ChurchController::class => 'church',
        EventController::class => 'event',
        SocietyController::class => 'society',
        PostController::class => 'post',
        DevotionalController::class => 'devotional',
        CommentController::class => 'comment',
    ];

    public function saveRelated(array $data, Model $created = null): array
    {
        //TODO: check if method exist to avoid obvios errors on these
        //TODO: remove previous relationships before adding new
        //
        // `syncWithoutDetaching` rather than `attach`: a draft may already have
        // linked these rows by uuid, and claimDraftRelations() has just moved
        // them onto the real key. Plain attach would then insert a duplicate
        // pivot row for every one of them.
        if (!empty($data['church_id'])) {
            $created->churches()->syncWithoutDetaching([(int) $data['church_id']]);
        }
        if (!empty($data['address_ids'])) {
            $created->addresses()->syncWithoutDetaching($this->clean($data['address_ids']));
        }
        if (!empty($data['image_ids'])) {
            $created->images()->syncWithoutDetaching($this->clean($data['image_ids']));
        }
        if (!empty($data['tag_ids'])) {
            $created->tags()->syncWithoutDetaching($this->clean($data['tag_ids']));
        }
        if (!empty($data['hierarchy_ids'])) {
            $created->hierarchies()->syncWithoutDetaching($this->clean($data['hierarchy_ids']));
        }

        return $data;
    }

    public function clean($data)
    {
        $subIds = $data;

        // If Flutter sent it as a comma-separated string "3,4", explode it into an array [3, 4]
        if (is_string($subIds)) {
            $subIds = explode(',', $subIds);
        }

        // Clean up any accidental whitespace or empty entries
        $subIds = array_filter(array_map('intval', $subIds));
        return $subIds;
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

            // Tell the content owner, but only if the row still resolves.
            $subject = null;
            try {
                $subject = $this->models[static::class];
                $class = [
                    'audio' => \App\Models\AudioPost::class,
                    'video' => \App\Models\VideoPost::class,
                    'post' => \App\Models\Post::class,
                    'event' => \App\Models\Event::class,
                    'devotional' => \App\Models\Devotional::class,
                ][$type] ?? null;
                if ($class !== null) {
                    $subject = $class::find($id);
                }
            } catch (\Throwable $e) {
                report($e);
                $subject = null;
            }

            app(ActivityNotifier::class)->liked($user_id, $subject, $type);

            return response()->json(['data' => true], 200);
        }
    }
}
