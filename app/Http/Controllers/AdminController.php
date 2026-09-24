<?php

namespace App\Http\Controllers;

use App\Models\AudioPost;
use App\Models\Church;
use App\Models\Event;
use App\Models\User;
use App\Models\VideoPost;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function stats()
    {
        return response()->json(['data' => [
            'users' => User::count(),
            'ministers' => User::where('is_minister', 1)->count(),
            'verified' => User::where('is_verified', 1)->count(),
            'churches' => Church::count(),
            'audio_posts' => AudioPost::count(),
            'video_posts' => VideoPost::count(),
            'events' => Event::count(),
            'pending_lyrics' => AudioPost::where('lyrics_status', '!=', 'ready')->count()
                + VideoPost::where('lyrics_status', '!=', 'ready')->count(),
            'processing_media' => AudioPost::where('media_status', 'processing')->count()
                + VideoPost::where('media_status', 'processing')->count(),
        ]]);
    }

    public function users(Request $request)
    {
        $q = User::query()->withCount(['followers', 'following']);
        if ($s = $request->input('q')) {
            $q->where(fn($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
        }
        return response()->json($q->orderByDesc('created_at')->paginate($request->input('perPage', 20)));
    }

    public function updateUser(Request $request, int $id)
    {
        $data = $request->validate([
            'is_minister' => 'sometimes|boolean',
            'is_verified' => 'sometimes|boolean',
            'is_editor' => 'sometimes|boolean',
            'is_admin' => 'sometimes|boolean',
            'name' => 'sometimes|string|max:255',
        ]);
        $user = User::findOrFail($id);
        // Never allow removing your own admin flag by accident.
        if ($request->user()->id === $user->id && array_key_exists('is_admin', $data) && !$data['is_admin']) {
            return response()->json(['error' => 'Cannot remove your own admin flag'], 422);
        }
        $user->update($data);
        return response()->json(['data' => $user]);
    }

    public function churches(Request $request)
    {
        $q = Church::query();
        if ($s = $request->input('q')) {
            $q->where(fn($w) => $w->where('name', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%"));
        }
        return response()->json($q->orderByDesc('created_at')->paginate($request->input('perPage', 20)));
    }

    /** Trending = most viewed/liked audio+video in the last 30 days (lightweight). */
    public function trending()
    {
        $audios = AudioPost::with('user')->withCount(['views', 'likes'])
            ->orderByDesc('views_count')->limit(10)->get();
        $videos = VideoPost::with('user')->withCount(['views', 'likes'])
            ->orderByDesc('views_count')->limit(10)->get();
        return response()->json(['data' => ['audios' => $audios, 'videos' => $videos]]);
    }
}
