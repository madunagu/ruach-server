<?php

namespace App\Http\Controllers;

use App\Models\AudioPost;
use App\Models\Devotional;
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
    private const FEED_TYPES = ['audio', 'video', 'post', 'event', 'devotional'];

    public function load(Request $request)
    {
        $type = $request['type'] ?? null;
        if (!empty($type) && !in_array($type, self::FEED_TYPES)) {
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
                    Devotional::class => [
                        'comments', 'likes', 'views',
                        'likes as liked' => function (Builder $query) use ($userId) {
                            $query->where('user_id', $userId);
                        },
                    ],
                ]);

                $morphTo->morphWith([
                    AudioPost::class => ['user', 'poster', 'srcs'],
                    VideoPost::class => ['user', 'poster', 'srcs'],
                    Post::class => ['user', 'poster'],
                    Event::class => ['poster', 'user'],
                    Devotional::class => ['user', 'poster'],
                ]);
            }
        ])
            ->where('postable_type', 'user')
            ->whereIn('postable_id', $following)
            ->orderBy('created_at', 'desc');
        if (!empty($type)) {
            $feeds = $feeds->where('parentable_type', $type);
        }
        $length = (int)(empty($request['perPage']) ? 15 : $request['perPage']);
        $feeds = $feeds->paginate($length);
        $result = new FeedCollection($feeds);
        return response()->json($result);
    }

    public function tags(Request $request)
    {
        $validator = $request->validate([
            'tag' => 'integer|required|exists:tags,id',
        ]);

        $tag = $request['tag'];
        $type = $request['type'] ?? null;
        $user = Auth::user();
        $userId = $user->id;
        $query = $request['q'];

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
                    Devotional::class => [
                        'comments', 'likes', 'views',
                        'likes as liked' => function (Builder $query) use ($userId) {
                            $query->where('user_id', $userId);
                        },
                    ],
                ]);

                $morphTo->morphWith([
                    AudioPost::class => ['user', 'poster', 'srcs'],
                    VideoPost::class => ['user', 'poster', 'srcs'],
                    Post::class => ['user', 'poster'],
                    Event::class => ['poster', 'user'],
                    Devotional::class => ['user', 'poster'],
                ]);
            }
        ])
            ->whereHasMorph(
                'parentable',
                ['post', 'event', 'audio', 'video', 'devotional'],
                function (Builder $query, string $type) use ($tag) {
                    $query->whereHas('tags', function ($query) use ($tag) {
                        $query->where('tag_id', $tag);
                    });
                }
            )
            ->orderBy('created_at', 'desc');

        if (!empty($type)) {
            $feeds = $feeds->where('parentable_type', $type);
        }
        $length = (int)(empty($request['perPage']) ? 15 : $request['perPage']);
        $feeds = $feeds->paginate($length);
        $result = new FeedCollection($feeds);
        return response()->json($result);
    }

    public function profile(Request $request)
    {
        $validator = $request->validate([
            'user_id' => 'integer|required|exists:users,id',
        ]);

        $profile_id = $request['user_id'];
        $type = $request['type'] ?? null;
        $userId = Auth::id();
        $query = $request['q'];

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
                    Devotional::class => [
                        'comments', 'likes', 'views',
                        'likes as liked' => function (Builder $query) use ($userId) {
                            $query->where('user_id', $userId);
                        },
                    ],
                ]);

                $morphTo->morphWith([
                    AudioPost::class => ['user', 'poster', 'srcs'],
                    VideoPost::class => ['user', 'poster', 'srcs'],
                    Post::class => ['user', 'poster'],
                    Event::class => ['poster', 'user'],
                    Devotional::class => ['user', 'poster'],
                ]);
            }
        ])
            ->where('postable_id', $profile_id)
            ->orderBy('created_at', 'desc');

        if (!empty($type)) {
            $feeds = $feeds->where('parentable_type', $type);
        }
        $length = (int)(empty($request['perPage']) ? 15 : $request['perPage']);

        $feeds = $feeds->paginate($length);
        $result = new FeedCollection($feeds);
        return response()->json($result);
    }

    public function populate()
    {
    }
}
