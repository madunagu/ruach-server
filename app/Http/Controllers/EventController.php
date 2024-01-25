<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Validator;

use App\Models\Event;
use App\Models\Feed;
use App\Http\Resources\EventCollection;
use App\Traits\Interactable;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    use Interactable;

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'string|required|max:255',
            'description' => 'nullable|string',
            'church_id' => 'nullable|integer|exists:churches,id',
            'starting_at' => 'nullable|date',
            'ending_at' => 'nullable|date',
            'address_id' => 'nullable|integer|exists:addresses,id',
            'hierarchy_group_id' => 'nullable|integer|exists:hierarchy_groups,id',
        ]);

        // if ($validator->fails()) {
        //     return response()->json($validator->messages(), 422);
        // }

        $data = collect($request->all())->toArray();
        $userId = Auth::id();
        $data['user_id'] = $userId;
        $data['poster_id'] = $userId;
        $data['poster_type'] = 'user';
        $event = Event::create($data);
        //for quick use adding feed here, can be removed later

        $feedCreated = Feed::create(['parentable_type' => 'event', 'postable_type' => 'user', 'postable_id' => $userId, 'parentable_id' => $event->id]);

        $saved = $this->saveRelated($data, $event);
        //create event emmiter or reminder or notifications for those who may be interested

        $event = Event::withCount('comments')
            ->with(['user', 'poster'])
            ->with('hierarchies', 'addresses', 'tags', 'images',  'churches')
            ->with(['attendees' => function ($query) {
                $query->limit(7);
            }])
            ->withCount([
                'attendees',
                'attendees as attending' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])
            ->withCount([
                'views',
                'views as viewed' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])->find($event->id);

        if ($event) {
            return response()->json(['data' => $event], 201);
        } else {
            return response()->json(['data' => false, 'errors' => 'unknown error occured'], 400);
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'integer|required|exists:events,id',
            'name' => 'string|required|max:255',
            'church_id' => 'nullable|integer|exists:churches,id',
            'starting_at' => 'nullable|date',
            'ending_at' => 'nullable|date',
            'address_id' => 'nullable|integer|exists:addresses,id',
            'hierarchy_group_id' => 'nullable|integer|exists:hierarchy_groups,id',
        ]);

        // if ($validator->fails()) {
        //     return response()->json($validator->messages(), 422);
        // }
        $data = collect($request->all())->toArray();
        $data['user_id'] = Auth::user()->id;
        $id = $request->route('id');
        $result = Event::find($id);
        //update result
        $result = $result->update($data);


        if ($result) {
            return response()->json(['data' => $result], 201);
        } else {
            return response()->json(['data' => false, 'errors' => 'unknown error occured'], 400);
        }
    }

    public function get(Request $request)
    {
        $id = (int) $request->route('id');
        $userId = Auth::user()->id;
        if ($event = Event::withCount('comments')
            ->with(['user', 'poster'])
            ->with('hierarchies', 'addresses', 'tags', 'images',  'churches')
            ->with(['attendees' => function ($query) {
                $query->limit(7);
            }])
            ->withCount([
                'attendees',
                'attendees as attending' => function (Builder $query) use ($userId) {
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
                'data' => $event
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
        $events = Event::with('user')->with(['attendees' => function ($query) {
            $query->limit(7);
        }])->withCount([
            'attendees',
            'attendees as attending' => function (Builder $query) use ($userId) {
                $query->where('user_id', $userId);
            },
        ])
            ->orderBy('created_at', 'DESC'); //TODO: add participants to the search using heirarchies
        if (!empty($query)) {
            $events = $events->search($query);
        }
        //here insert search parameters and stuff
        $length = (int) (empty($request['perPage']) ? 15 : $request['perPage']);
        $events = $events->paginate($length);
        $data = new EventCollection($events);
        return response()->json($data);
    }

    // this is a bool function
    public function attend(Request $request)
    {
        $id = $request->route('id');
        $tog = $request['value'];
        $userId = Auth::id();
        $event = Event::find((int)$id);
        if ($tog) {
            $event->attendees()->attach($userId);
            return response()->json(['data' => true]);
        }
        $event->attendees()->detach($userId);
        return response()->json(['data' => false]);
    }


    public function delete(Request $request)
    {
        $id = (int) $request->route('id');
        if ($event = Event::find($id)) {
            $event->delete();
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
