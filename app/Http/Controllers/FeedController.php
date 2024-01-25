<?php

namespace App\Http\Controllers;

use App\Models\AudioPost;
use App\Models\Event;
use App\Models\Feed;
use App\Models\Post;
use App\Models\Tag;
use App\Http\Resources\FeedCollection;
use App\Models\User;
use App\Models\VideoPost;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FeedController extends Controller
{
    public function load(Request $request)
    {
        $type = $request['type'];
        if (!empty($type) && !in_array($type, ['audio', 'video', 'post', 'event'])) {
            return response()->json('invalid feed type', 422);
        }

        $user = Auth::user();
        $userId = $user->id;
        $following = $user->following()->pluck('user_id');
        $following[] = 1;

        $feeds = Feed::with([
            'parentable' => function (MorphTo $morphTo) use ($userId) {
                $morphTo->morphWithCount([
                    AudioPost::class => [
                        'comments', 'likes', 'views',
                        'likes as liked' => function (Builder $query) use ($userId) {
                            $query->where('user_id', $userId);
                        },
                    ],
                    VideoPost::class => [
                        'comments', 'likes', 'views',
                        'likes as liked' => function (Builder $query) use ($userId) {
                            $query->where('user_id', $userId);
                        },
                    ],
                    Post::class => [
                        'comments', 'likes', 'views',
                        'likes as liked' => function (Builder $query) use ($userId) {
                            $query->where('user_id', $userId);
                        },
                    ],
                    Event::class => [
                        'comments', 'attendees',
                        'attendees as attending' => function (Builder $query) use ($userId) {
                            $query->where('user_id', $userId);
                        },
                        'views'
                    ],
                ]);

                $morphTo->morphWith([
                    AudioPost::class => ['user', 'poster', 'srcs'],
                    VideoPost::class => ['user', 'poster', 'srcs'],
                    Post::class => ['user', 'poster'],
                    Event::class => ['poster', 'user'],
                ]);
            }
        ])

            ->whereIn('postable_id', $following)
            ->orderBy('created_at', 'desc');
        if (!empty($type)) {
            $feeds = $feeds->where('parentable_type', $type);
        }
        $feeds = $feeds->paginate();
        $result = new FeedCollection($feeds);
        return response()->json($result);
    }

    public function tags(Request $request)
    {
        $validator = $request->validate([
            'tag' => 'integer|required|exists:tags,id',
        ]);

        $tag = $request['tag'];
        $user = Auth::user();
        $userId = $user->id;
        $query = $request['q'];

        //    $tags = Tag::with('taggable')->get();
        //    dd($tags);
        // $following = $user->following()->pluck('user_id');

        $feeds = Feed::with([
            'parentable' => function (MorphTo $morphTo) use ($userId) {
                $morphTo->morphWithCount([
                    AudioPost::class => [
                        'comments', 'likes', 'views',
                        'likes as liked' => function (Builder $query) use ($userId) {
                            $query->where('user_id', $userId);
                        },
                    ],
                    VideoPost::class => [
                        'comments', 'likes', 'views',
                        'likes as liked' => function (Builder $query) use ($userId) {
                            $query->where('user_id', $userId);
                        },
                    ],
                    Post::class => [
                        'comments', 'likes', 'views',
                        'likes as liked' => function (Builder $query) use ($userId) {
                            $query->where('user_id', $userId);
                        },
                    ],
                    Event::class => [
                        'comments', 'attendees',
                        'attendees as attending' => function (Builder $query) use ($userId) {
                            $query->where('user_id', $userId);
                        },
                        'views'
                    ],
                ]);

                $morphTo->morphWith([
                    AudioPost::class => ['user', 'poster', 'srcs'],
                    VideoPost::class => ['user', 'poster', 'srcs'],
                    Post::class => ['user', 'poster'],
                    Event::class => ['poster', 'user'],
                ]);
            }
        ])
            ->whereHasMorph(
                'parentable',
                ['post', 'event', 'audio', 'video'],
                function (Builder $query, string $type) use ($tag) {
                    $query->whereHas('tags', function ($query) use ($tag) {
                        $query->where('tag_id', $tag);
                    });
                }
            )            // ->whereIn('postable_id', $following)
            ->orderBy('created_at', 'desc');

        if (!empty($type)) {
            $feeds = $feeds->where('parentable_type', $type);
        }
        $feeds = $feeds->paginate();
        $result = new FeedCollection($feeds);
        return response()->json($result);
    }

    public function profile(Request $request)
    {
        $validator = $request->validate([
            'user_id' => 'integer|required|exists:users,id',
        ]);

        $profile_id = $request['user_id'];
        $userId = Auth::id();
        $query = $request['q'];

        //    $tags = Tag::with('taggable')->get();
        //    dd($tags);
        // $following = $user->following()->pluck('user_id');

        $feeds = Feed::with([
            'parentable' => function (MorphTo $morphTo) use ($userId) {
                $morphTo->morphWithCount([
                    AudioPost::class => [
                        'comments', 'likes', 'views',
                        'likes as liked' => function (Builder $query) use ($userId) {
                            $query->where('user_id', $userId);
                        },
                    ],
                    VideoPost::class => [
                        'comments', 'likes', 'views',
                        'likes as liked' => function (Builder $query) use ($userId) {
                            $query->where('user_id', $userId);
                        },
                    ],
                    Post::class => [
                        'comments', 'likes', 'views',
                        'likes as liked' => function (Builder $query) use ($userId) {
                            $query->where('user_id', $userId);
                        },
                    ],
                    Event::class => [
                        'comments', 'attendees',
                        'attendees as attending' => function (Builder $query) use ($userId) {
                            $query->where('user_id', $userId);
                        },
                        'views'
                    ],
                ]);

                $morphTo->morphWith([
                    AudioPost::class => ['user', 'poster', 'srcs'],
                    VideoPost::class => ['user', 'poster', 'srcs'],
                    Post::class => ['user', 'poster'],
                    Event::class => ['poster', 'user'],
                ]);
            }
        ])
            ->where('postable_id', $profile_id)
            ->orderBy('created_at', 'desc');

        if (!empty($type)) {
            $feeds = $feeds->where('parentable_type', $type);
        }
        $feeds = $feeds->paginate();
        $result = new FeedCollection($feeds);
        return response()->json($result);
    }

    public function populate()
    {
    }
}
