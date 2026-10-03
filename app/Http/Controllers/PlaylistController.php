<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Models\Playable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PlaylistController extends Controller
{
    /**
     * The authenticated user's playlists, each with its members in order.
     *
     * `playables_count` is loaded alongside so a list of playlists does not
     * need one extra query per row to render a count.
     */
    public function list(Request $request)
    {
        $perPage = (int) ($request['perPage'] ?? 30);

        $playlists = Playlist::where('user_id', Auth::id())
            ->with(['playables.audioPost', 'playables.videoPost'])
            ->withCount('playables')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json($playlists);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $playlist = Playlist::create([
            'name' => $request['name'],
            'description' => $request['description'] ?? null,
            'user_id' => Auth::id(),
        ]);

        return response()->json(
            ['data' => $playlist->loadCount('playables')],
            201
        );
    }

    public function get(Request $request)
    {
        $playlist = $this->findForUser((int) $request->route('id'));

        if ($playlist === null) {
            return response()->json(['data' => false], 404);
        }

        return response()->json([
            'data' => $playlist->load([
                'playables.audioPost',
                'playables.videoPost',
            ]),
        ]);
    }

    public function update(Request $request)
    {
        $playlist = $this->findForUser((int) $request->route('id'));

        if ($playlist === null) {
            return response()->json(['data' => false], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $playlist->update($request->only(['name', 'description']));

        return response()->json(['data' => $playlist->fresh()]);
    }

    public function delete(Request $request)
    {
        $playlist = $this->findForUser((int) $request->route('id'));

        if ($playlist === null) {
            return response()->json(['data' => false], 404);
        }

        $playlist->playables()->detach();
        $playlist->delete();

        return response()->json(['data' => true]);
    }

    /**
     * Appends a playable, or moves it to the end when already present.
     *
     * Idempotent by design: a retried request should not duplicate the member
     * or fail on the pivot's unique index.
     */
    public function addPlayable(Request $request)
    {
        $playlist = $this->findForUser((int) $request->route('id'));

        if ($playlist === null) {
            return response()->json(['data' => false], 404);
        }

        $validator = Validator::make($request->all(), [
            'playable_id' => 'required|integer|exists:playables,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $playable = Playable::find((int) $request['playable_id']);
        $playlist->addPlayable($playable);

        return response()->json([
            'data' => $playlist->load([
                'playables.audioPost',
                'playables.videoPost',
            ]),
        ]);
    }

    public function removePlayable(Request $request)
    {
        $playlist = $this->findForUser((int) $request->route('id'));

        if ($playlist === null) {
            return response()->json(['data' => false], 404);
        }

        // Read the id from the route: the client has no reason to repeat it in
        // the body, and a DELETE with an empty body is the natural request.
        $playable = Playable::find((int) $request->route('playableId'));
        if ($playable === null) {
            return response()->json(['data' => false], 404);
        }

        $playlist->removePlayable($playable);

        return response()->json([
            'data' => $playlist->load([
                'playables.audioPost',
                'playables.videoPost',
            ]),
        ]);
    }

    /**
     * Rewrites the member order.
     *
     * Accepts the full ordered id list and writes dense ranks, so repeated
     * reorders cannot accumulate gaps or collide on a rank.
     */
    public function reorder(Request $request)
    {
        $playlist = $this->findForUser((int) $request->route('id'));

        if ($playlist === null) {
            return response()->json(['data' => false], 404);
        }

        $validator = Validator::make($request->all(), [
            'playable_ids' => 'required|array',
            'playable_ids.*' => 'integer|exists:playables,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        // Only reorder members the playlist actually has, so a crafted list
        // cannot pull an unrelated playable into it.
        $memberIds = $playlist->playables()->pluck('playables.id')->all();
        $requested = array_map('intval', $request['playable_ids']);
        $ordered = array_values(array_intersect($requested, $memberIds));

        // Anything the client omitted keeps its relative position at the end
        // rather than being silently dropped.
        foreach ($memberIds as $id) {
            if (!in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }

        DB::transaction(function () use ($playlist, $ordered) {
            $playlist->reorder($ordered);
        });

        return response()->json([
            'data' => $playlist->load([
                'playables.audioPost',
                'playables.videoPost',
            ]),
        ]);
    }

    /**
     * Playables the given user owns, for picking something to add.
     *
     * Deliberately ignores a client-supplied owner so one user cannot list
     * another's library.
     */
    public function library(Request $request)
    {
        $perPage = (int) ($request['perPage'] ?? 50);

        $playables = Playable::ownedBy(Auth::id())
            ->with(['audioPost', 'videoPost'])
            ->paginate($perPage);

        return response()->json($playables);
    }

    /**
     * Scopes a lookup to the authenticated user.
     *
     * Without this an id could read, mutate or delete another user's playlist.
     */
    private function findForUser(int $id): ?Playlist
    {
        return Playlist::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();
    }
}