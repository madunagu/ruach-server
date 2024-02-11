<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Playlist;
use App\Models\Feed;
use App\Traits\Interactable;
use App\Traits\Orderable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class PlaylistController extends Controller
{
    use Interactable, Orderable;
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|required',
            'description' => 'string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $userId = Auth::id();
        $data = collect($request->all())->toArray();
        $data['user_id'] = $userId;
        $data['poster_id'] = $userId;
        $data['poster_type'] = 'user';

        $playlist = Playlist::create($data);
        $interacted = $this->saveRelated($data, $playlist);

        //TODO: notify relevant users of activity
        //for quick use adding feed here, can be removed later
        $feedCreated = Feed::create(['parentable_type' => 'playlist', 'postable_type' => 'user', 'postable_id' => $userId, 'parentable_id' => $playlist->id]);


        $result = Playlist::withCount('comments')
            ->with(['user', 'poster'])
            ->with('hierarchies', 'tags', 'images',  'churches')
            ->withCount([
                'comments',
                'likes',
                'likes as liked' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])
            ->withCount([
                'views',
                'views as viewed' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])->find($playlist->id);

        if ($result) {
            return response()->json(['data' => $playlist], 201);
        } else {
            return response()->json(['data' => false, 'errors' => 'unknown error occured'], 400);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|nullable',
            'description' => 'string|required',

        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }
        $id = $request->route('id');

        $userId = Auth::id();
        $data = collect($request->all())->toArray();
        $data['user_id'] = $userId;
        $playlist = Playlist::find($id);

        $playlist = $playlist->update($data);
        $interacted = $this->saveRelated($data, $playlist);
        $result = Playlist::withCount('comments')
            ->with(['user', 'poster'])
            ->with('hierarchies', 'tags', 'images',  'churches')
            ->withCount([
                'comments',
                'likes',
                'likes as liked' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])
            ->withCount([
                'views',
                'views as viewed' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])->find($playlist->id);

        if ($result) {
            return response()->json(['data' => $result], 201);
        } else {
            return response()->json(['data' => false, 'errors' => 'unknown error occured'], 400);
        }
    }


    public function get(Request $request)
    {
        $id = (int)$request->route('id');
        // $address = Address::find($id);
        // return response()->json([
        //         'data' => $address
        //     ], 200);

        $userId = Auth::user()->id;
        if ($playlist = Playlist::with(['user', 'poster'])
            ->with('hierarchies', 'tags', 'images',  'churches')
            ->withCount([
                'comments',
                'likes',
                'likes as liked' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])
            ->withCount([
                'views',
                'views as viewed' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])->find($id)
        ) {
            return response()->json([
                'data' => $playlist
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
            'q' => 'nullable|string|min:1',
            'o' => 'nullable|string|min:1',
            'd' => 'nullable|string|min:1'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $params = $data = collect($request->all())->toArray();
        $orderParams = $this->orderParams($params);
        $length = (int) (empty($request['perPage']) ? 15 : $request['perPage']);

        $comments = Playlist::with('user', 'poster')->withCount('likes')->withCount('comments')->withCount('views')
            ->orderBy('playlists.' . $orderParams->order,  $orderParams->direction)
            ->paginate($length);

        return response()->json($comments);
    }

    public function delete(Request $request)
    {
        $id = (int)$request->route('id');
        if ($post = Playlist::find($id)) {
            $post->delete();
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
