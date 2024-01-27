<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Feed;
use App\Traits\Interactable;
use App\Traits\Orderable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Http\Request;

class PostController extends Controller
{
    use Interactable, Orderable;
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'string|nullable',
            'body' => 'string|required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $userId = Auth::id();
        $data = collect($request->all())->toArray();
        $data['user_id'] = $userId;
        $data['poster_id'] =$userId;
        $data['poster_type'] = 'user';

        $post = Post::create($data);
        //TODO: notify relevant users of activity
        //for quick use adding feed here, can be removed later
        $feedCreated = Feed::create(['parentable_type' => 'post', 'postable_type' => 'user', 'postable_id' => $userId, 'parentable_id' => $post->id]);


        $result = Post::withCount('comments')
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
            ])->find($post->id);

        if ($result) {
            return response()->json(['data' => $post], 201);
        } else {
            return response()->json(['data' => false, 'errors' => 'unknown error occured'], 400);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'string|nullable',
            'body' => 'string|required',

        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }
        $id = $request->route('id');

        $data = collect($request->all())->toArray();
        $data['user_id'] = Auth::user()->id;
        $result = Post::find($id);

        $result = $result->update($data);
        if ($result) {
            return response()->json(['data' => true], 201);
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
        if ($post = Post::with(['user', 'poster'])
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
                'data' => $post
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

        $comments = Post::with('user', 'poster')->withCount('likes')->withCount('comments')->withCount('views')
            ->orderBy('posts.' . $orderParams->order,  $orderParams->direction)
            ->paginate($length);

        return response()->json($comments);
    }

    public function delete(Request $request)
    {
        $id = (int)$request->route('id');
        if ($post = Post::find($id)) {
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
