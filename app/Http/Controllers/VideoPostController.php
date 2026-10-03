<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Validator;

use wapmorgan\MediaFile\MediaFile;

use App\Models\VideoPost;
use App\Http\Resources\AudioPostCollection;
use App\Traits\Interactable;
use App\Models\VideoSrc;
use App\Models\Feed;
use App\Jobs\ProcessMediaVariants;
use App\Services\LyricsService;
use App\Services\PlayableFactory;

class VideoPostController extends Controller
{
    use Interactable;

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'string|required|max:255',
            'full_text' => 'nullable|string',
            'description' => 'nullable|string|max:255',
            'church_id' => 'nullable|integer|exists:churches,id',
            'size' => 'nullable|integer',
            'length' => 'nullable|integer',
            'language' => 'nullable|string',
            'address_id' => 'nullable|integer|exists:addresses,id',
            'video' => 'required|file|mimes:mp4,mkv,avi,mov,webm,wmv|max:204800',
        ]);

        $data = collect($request->all())->toArray();
        $userId = Auth::id();
        $data['user_id'] = $userId;
        $data['poster_id'] = $userId;
        $data['poster_type'] = 'user';

        $file = $request->file('video');
        $extension = $file->extension() ?: 'mp4';

        $name = time() . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $path = 'video/full/' . $name;

        $fileMoved = Storage::disk('public')->putFileAs('video/full', $file, $name);
        if (!$fileMoved) {
            return response()->json(['data' => false, 'errors' => 'Failed to store video file.'], 500);
        }

        $data['src_url'] = Storage::disk('public')->url($path);
        $data['size'] = Storage::disk('public')->size($path);

        $details = $this->getTrackDetails($path);
        $data['length'] = $details['length'] ?? null;

        $videoPost = VideoPost::create($data);

        // Adopt any relations the client saved against this draft's uuid.
        $videoPost->claimDraftRelations();

        // Best-effort embedded-lyrics extraction (fast, no ext deps).
        try {
            $lyrics = app(LyricsService::class)->extractFromDisk($path);
            if ($lyrics && empty($videoPost->full_text)) {
                $videoPost->update([
                    'full_text' => app(LyricsService::class)->normalize($lyrics, (int) $data['length']),
                    'lyrics_status' => 'ready',
                ]);
            } elseif (!empty($videoPost->full_text)) {
                $videoPost->update(['lyrics_status' => 'ready']);
            }
        } catch (\Throwable $e) {
        }

        // Adaptive renditions (480p/720p) + thumbnails run async so uploads stay fast.
        try {
            ProcessMediaVariants::dispatch('video', $videoPost->id, $path);
            $videoPost->update(['media_status' => 'processing']);
        } catch (\Throwable $e) {
        }

        Feed::create(['parentable_type' => 'video', 'postable_type' => 'user', 'postable_id' => $userId, 'parentable_id' => $videoPost->id]);

        if ($details) {
            VideoSrc::create([
                'length' => $details['length'],
                'format' => $extension,
                'src' => $data['src_url'],
                'quality' => $details['height'] ?? 0,
                'width' => $details['width'] ?? null,
                'height' => $details['height'] ?? null,
                'size' => $data['size'],
                'variant' => 'original',
                'mime' => $file->getClientMimeType() ?? 'video/mp4',
                'status' => 'ready',
                'video_post_id' => $videoPost->id,
            ]);
        }

        $interacted = $this->saveRelated($data, $videoPost);

        // A video is also an audio recording, so it also gets an audio post and
        // a playable. Both are best-effort: a failure here must not lose the
        // upload the user actually asked for.
        try {
            $playable = app(PlayableFactory::class)->forVideo($videoPost, $path);
            $data['playable_id'] = $playable->id;
        } catch (\Throwable $e) {
            report($e);
        }

        $result = VideoPost::with(['srcs', 'poster', 'user'])
            ->with('hierarchies', 'addresses', 'tags', 'images', 'churches')
            ->withCount([
                'comments',
                'likes',
                'likes as liked' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
                'views',
                'views as viewed' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])
            ->find($videoPost->id);

        if ($result) {
            return response()->json(['data' => $result], 201);
        } else {
            return response()->json(['data' => false, 'errors' => 'unknown error occured'], 400);
        }
    }

    public function getTrackDetails(String $path): ?array
    {
        $storagePath = storage_path('app/public/' . $path);
        if (!is_file($storagePath)) {
            return null;
        }

        try {
            $media = MediaFile::open($storagePath);
            if ($media->isVideo()) {
                $video = $media->getVideo();
                return [
                    'length' => $video->getLength(),
                    'width' => $video->getWidth(),
                    'height' => $video->getHeight(),
                    'frame_rate' => $video->getFramerate(),
                ];
            }
        } catch (\Throwable $e) {
            // File is not a detectable media type — return null so callers
            // can fall back gracefully instead of crashing.
        }

        return null;
    }

    public function related(Request $request)
    {
        $video = VideoPost::find($request['id']);
        if (!$video) {
            return response()->json(['data' => false], 404);
        }
        $names = explode(' ', $video->name);
        $videos = VideoPost::where('name', 'like', $video->name);
        foreach ($names as $key => $name) {
            $videos->orWhere('name', 'like', "%$name%");
            $videos->orWhere('description', 'like', "%$name%");
        }
        $data = $videos->with('hierarchies')
            ->whereNot('video_posts.id', $video->id)->paginate();
        return response()->json($data);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'integer|required|exists:video_posts,id',
            'name' => 'string|required|max:255',
            'full_text' => 'nullable|string',
            'description' => 'nullable|string|max:255',
            'church_id' => 'nullable|integer|exists:churches,id',
            'size' => 'nullable|integer',
            'length' => 'nullable|integer',
            'language' => 'nullable|string',
            'address_id' => 'nullable|integer|exists:addresses,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $userId = Auth::id();
        $data = collect($request->all())->toArray();
        $data['user_id'] = $userId;
        $id = $request->route('id');
        $videoPost = VideoPost::find($id);

        if (!$videoPost) {
            return response()->json(['data' => false, 'errors' => 'video post not found'], 404);
        }

        $interacted = $this->saveRelated($data, $videoPost);
        $result = $videoPost->update($data);
        $result = VideoPost::with(['srcs', 'poster', 'user'])
            ->with('hierarchies', 'addresses', 'tags', 'images', 'churches')
            ->withCount([
                'comments',
                'likes',
                'likes as liked' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
                'views',
                'views as viewed' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])
            ->find($videoPost->id);


        if ($result) {
            return response()->json(['data' => $result], 200);
        } else {
            return response()->json(['data' => false, 'errors' => 'unknown error occured'], 400);
        }
    }

    public function get(Request $request)
    {
        $id = (int)$request->route('id');
        $userId = Auth::user()->id;
        if ($video = VideoPost::with(['srcs', 'images', 'user', 'churches', 'addresses', 'poster'])
            ->withCount([
                'comments',
                'likes',
                'likes as liked' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
                'views',
                'views as viewed' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])
            ->find($id)
        ) {
            return response()->json([
                'data' => $video
            ], 200);
        } else {
            return response()->json([
                'data' => false
            ], 404);
        }
    }

    public function list(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'nullable|string|min:3'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }
        $userId = Auth::id();

        $query = $request['q'];
        $videos = VideoPost::with(['images', 'user', 'poster', 'srcs'])
            ->withCount([
                'comments',
                'likes',
                'likes as liked' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
                'views',
                'views as viewed' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ]);
        $tag = $request['tag'];
        if ($tag) {
            $videos = $videos->whereHas('tags', function ($query) use ($tag) {
                $query->where('tag_id', $tag);
            });
        }
        if ($query) {
            $videos = $videos->search($query);
        }

        $videos->orderBy('video_posts.created_at', 'DESC');
        $length = (int)(empty($request['perPage']) ? 15 : $request['perPage']);
        $videos = $videos->paginate($length);
        $data = new AudioPostCollection($videos);
        return response()->json($data);
    }


    public function delete(Request $request)
    {
        $id = (int)$request->route('id');
        if ($video = VideoPost::find($id)) {
            $video->delete();
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
