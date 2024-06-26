<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Validator;
use App\Models\User;
use App\Models\Event;
use App\Models\Image;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class UserController extends Controller
{
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|required|max:255',
            'email' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }
        if ($request['unassigned']) {
            $request['email'] = Str::random(15);
            $request['password'] = Str::random(15);
        }

        $data = collect($request->all())->toArray();

        $data['avatar'] = "https://gravatar.com/avatar/" .   hash('sha256', strtolower(trim($data['email'])));
        $result = User::create($data);

        if ($result) {
            return
                response()->json(['data' => $result], 201);
        } else {
            return response()->json(['data' => false, 'errors' => 'unknown error occured'], 400);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'integer|required|exists:users,id',
            'name' => 'string|required|max:255',
            'email' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }
        $id = $request->route('id');
        $data = $request->only('name', 'phone', 'description');

        // $data = collect($request->all())->toArray();
        $user = User::find($id);

        if (!empty($data['image_ids'])) {
            $user->images()->attach($data['image_ids']);
            $data['avatar'] = Image::find($data['image_ids'][0])->small;
        }


        $result = $user->update($data);
        $result = User::with(['images'])->withCount([
            'following',
            'likes',
            'followers',
            'messages',
            'followers as is_following' => function (Builder $query) use ($id) {
                $query->where('user_id', $id);
            },
        ])->find($id);

        if ($result) {
            return response()->json(['data' => $result], 201);
        } else {
            return response()->json(['data' => false, 'errors' => 'unknown error occured'], 400);
        }
    }

    public function get(Request $request)
    {
        $id = (int)$request->route('id');

        if ($user = User::find($id)->with('images')
            ->withCount([
                'following',
                'likes',
                'followers',
                'messages',
                'followers as is_following' => function (Builder $query) use ($id) {
                    $query->where('user_id', $id);
                },
            ])->find($id)
        ) {
            return response()->json([
                'data' => $user
            ], 200);
        } else {
            return response()->json([
                'data' => false
            ], 404);
        }
    }

    public function user(Request $request)
    {
        $id = Auth::id();

        if ($user = User::with(['images'])
            ->withCount([
                'following',
                'likes',
                'followers',
                'messages',
                'followers as is_following' => function (Builder $query) use ($id) {
                    $query->where('user_id', $id);
                },
            ])
            ->find($id)
        ) {
            // $user['notification_count'] = Auth::user()->unreadNotifications()->count();
            return response()->json(
                $user,
                200
            );
        } else {
            return response()->json([
                'data' => false
            ], 404);
        }
    }

    public function list(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'nullable|string|min:1'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }
        $userId = Auth::id();

        $query = $request['q'];
        $order = $request['o'];
        $eventId = $request['event_id'];
        $followersForId = $request['followers_for'];
        $followingForId = $request['following_for'];
        $justMinisters = $request['just_ministers'];
        $users = User::where('id', '>', '0');

        if ($eventId) {
            $users = Event::find($eventId)->attendees();
        }
        if ($followersForId) {
            $users = User::find($followersForId)->followers();
        }
        if ($followingForId) {
            $users = User::find($followingForId)->following();
        }
        //TODO: check if this is a valid condition
        if ($query) {
            $users = $users->where('name', 'like', '%' . $query . '%');
        }
        $users = $users->with('images')
            ->withCount([
                'following',
                'followers',
                'followers as is_following' => function (Builder $query) use ($userId) {
                    $query->where('follower_id', $userId);
                },
            ]);
        if ($order == 'name') {
            $users = $users->orderBy('name');
        }
        if ($justMinisters == true) {
            $users = $users->where('is_minister','1');
        }
        $length = (int) (empty($request['perPage']) ? 15 : $request['perPage']);
        $data = $users->paginate($length);

        return response()->json($data);
    }

    public function delete(Request $request)
    {
        $id = (int)$request->route('id');
        if ($user = User::find($id)) {
            $user->delete();
            return response()->json([
                'data' => true
            ], 200);
        } else {
            return response()->json([
                'data' => false
            ], 404);
        }
    }

    public function follow(Request $request)
    {

        $userToFollowId = $request->route('id');
        $tog = $request['value'];
        $myUserId = Auth::id();
        $userToFollow = User::find((int)$userToFollowId);
        if ($tog) {
            $userToFollow->followers()->attach($myUserId);
            return response()->json(['data' => true]);
        }
        //IF the  request is to unfollow
        $userToFollow->followers()->detach($myUserId);
        return response()->json(['data' => false]);
    }
}
