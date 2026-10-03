<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Validator;

use App\Models\AudioPost;
use App\Models\AudioSrc;
use App\Models\Image;
use App\Traits\Interactable;
use App\Models\Feed;
use App\Http\Resources\AudioPostCollection;
use App\Jobs\ProcessMediaVariants;
use App\Services\LyricsService;
use App\Services\PlayableFactory;
use App\Services\MediaVariantService;
use wapmorgan\MediaFile\MediaFile;

class AudioPostController extends Controller
{
    use Interactable;

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|required|max:255',
            'full_text' => 'nullable|string',
            'description' => 'nullable|string|max:255',
            'church_id' => 'nullable|integer|exists:churches,id',
            'size' => 'nullable|integer',
            'length' => 'nullable|integer',
            'language' => 'nullable|string',
            'address_id' => 'nullable|integer|exists:addresses,id',
            'audio' => 'required|file|mimes:mp3,wav,m4a,wma,aac,flac,amr,ogg|max:51200',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $data = collect($request->all())->toArray();

        $userId = Auth::user()->id;
        $data['user_id'] = $userId;
        $data['poster_id'] = $userId;
        $data['poster_type'] = 'user';

        $file = $request->file('audio');
        $extension = strtolower($file->getClientMimeType()) === 'audio/mpeg' ? 'mp3' : $file->extension();
        $extension = $extension ?: 'mp3';

        $name = time() . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $path = 'audio/full/' . $name;

        $fileMoved = Storage::disk('public')->putFileAs('audio/full', $file, $name);
        if (!$fileMoved) {
            return response()->json(['data' => false, 'errors' => 'Failed to store audio file.'], 500);
        }

        $data['src_url'] = Storage::disk('public')->url($path);
        $data['size'] = Storage::disk('public')->size($path);

        $res = $this->getTrackDetails($path);
        $data['length'] = $res['length'] ?? null;

        $audio = AudioPost::create($data);

        // Adopt any relations the client saved against this draft's uuid.
        $audio->claimDraftRelations();

        // Best-effort embedded-lyrics extraction (fast, no ext deps).
        try {
            $lyrics = app(LyricsService::class)->extractFromDisk($path);
            if ($lyrics && empty($audio->full_text)) {
                $audio->update([
                    'full_text' => app(LyricsService::class)->normalize($lyrics, (int) $data['length']),
                    'lyrics_status' => 'ready',
                ]);
            } elseif (!empty($audio->full_text)) {
                $audio->update(['lyrics_status' => 'ready']);
            }
        } catch (\Throwable $e) {
        }

        // Extract album art (ID3 APIC frame) and attach to the post's images.
        try {
            $albumArt = app(MediaVariantService::class)->extractAlbumArt($path);
            if ($albumArt !== null) {
                $artPath = 'images/album-art/' . $name . '.jpg';
                Storage::disk('public')->put($artPath, $albumArt);
                $image = Image::create([
                    'full' => Storage::disk('public')->url($artPath),
                    'large' => Storage::disk('public')->url($artPath),
                    'medium' => Storage::disk('public')->url($artPath),
                    'small' => Storage::disk('public')->url($artPath),
                    'user_id' => $userId,
                ]);
                $audio->images()->attach($image->id);
            }
        } catch (\Throwable $e) {
        }

        // Heavy ffmpeg variants run async so uploads stay fast.
        try {
            ProcessMediaVariants::dispatch('audio', $audio->id, $path);
            $audio->update(['media_status' => 'processing']);
        } catch (\Throwable $e) {
        }

        $interacted = $this->saveRelated($data, $audio);

        // Every upload gets a playable so playlists and the player can refer to
        // one thing rather than to audio and video separately.
        try {
            $playable = app(PlayableFactory::class)->forAudio($audio);
            $data['playable_id'] = $playable->id;
        } catch (\Throwable $e) {
            report($e);
        }

        Feed::create(['parentable_type' => 'audio', 'postable_type' => 'user', 'postable_id' => $userId, 'parentable_id' => $audio->id]);

        if ($res) {
            AudioSrc::create([
                'length' => $res['length'],
                'refresh_rate' => $res['refresh_rate'],
                'bitrate' => $res['bitrate'],
                'src' => $data['src_url'],
                'size' => $data['size'],
                'format' => $extension,
                'variant' => 'original',
                'mime' => $file->getClientMimeType() ?? 'audio/mpeg',
                'status' => 'ready',
                'audio_post_id' => $audio->id,
            ]);
        }

        $audio = AudioPost::with(['srcs', 'poster', 'tags', 'images', 'user', 'churches', 'addresses'])
            ->with(['hierarchies' => [
                'user',
            ]])->withCount([
                'comments',
                'likes',
                'likes as liked' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
                'views',
                'views as viewed' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])->find($audio->id);

        if ($audio) {
            return response()->json(['data' => $audio], 201);
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
            if ($media->isAudio()) {
                $audio = $media->getAudio();
                return [
                    'length' => $audio->getLength(),
                    'bitrate' => $audio->getBitRate(),
                    'refresh_rate' => $audio->getSampleRate(),
                    'channels' => $audio->getChannels(),
                ];
            }
        } catch (\Throwable $e) {
            // File is not a detectable media type — return null so callers
            // can fall back gracefully instead of crashing.
        }

        return null;
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'integer|required|exists:audio_posts,id',
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

        $data = collect($request->all())->toArray();
        $data['user_id'] = Auth::user()->id;
        $userId = Auth::id();
        $id = $request->route('id');
        $audio = AudioPost::find($id);

        if (!$audio) {
            return response()->json(['data' => false, 'errors' => 'audio post not found'], 404);
        }

        $result = $audio->update($data);
        $interacted = $this->saveRelated($data, $audio);

        $result = AudioPost::with(['srcs', 'poster', 'tags', 'images', 'user', 'churches', 'addresses'])
            ->with(['hierarchies' => [
                'user',
            ]])->withCount([
                'comments',
                'likes',
                'likes as liked' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
                'views',
                'views as viewed' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])->find($audio->id);


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
        if ($audio = AudioPost::with(['srcs', 'poster', 'user'])
            ->with('addresses', 'tags', 'images', 'churches')
            ->with(['hierarchies' => [
                'user',
            ]])
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
            ])->find($id)
        ) {
            $audio->views()->create([
                'user_id' => $userId,
                'viewable_id' => $id,
                'viewable_type' => 'audio'
            ]);
            return response()->json([
                'data' => $audio
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

        $audia = AudioPost::with('user', 'srcs', 'poster')
            ->with(['hierarchies' => [
                'user',
            ]])
            ->with('addresses', 'tags', 'images', 'churches');

        $query = $request['q'];
        $tag = $request['tag'];
        $tags = $request['tag_ids'];
        if ($tag || $tags) {
            if ($tag) {
                $audia = $audia->whereHas('tags', function ($query) use ($tag) {
                    $query->where('tag_id', $tag);
                });
            }
        }

        if ($query) {
            $audia = $audia->search($query);
        }

        $audia->orderBy('audio_posts.created_at', 'DESC');

        $length = (int)(empty($request['perPage']) ? 15 : $request['perPage']);
        $audia = $audia->paginate($length);
        $data = new AudioPostCollection($audia);
        return response()->json($data);
    }

    public function related(Request $request)
    {
        $audio = AudioPost::find((int)$request['id']);
        if (!$audio) {
            return response()->json(['data' => false], 404);
        }
        $names = explode(' ', $audio->name);
        $audia = AudioPost::where('name', 'like', $audio->name);
        foreach ($names as $key => $name) {
            $audia->orWhere('name', 'like', "%$name%");
            $audia->orWhere('description', 'like', "%$name%");
        }
        $data = $audia->with('hierarchies')
            ->whereNot('audio_posts.id', $audio->id)->paginate();

        return response()->json($data);
    }


    public function delete(Request $request)
    {
        $id = (int)$request->route('id');
        if ($audio = AudioPost::find($id)) {
            $audio->delete();
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
