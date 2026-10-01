<?php

namespace App\Http\Controllers;

use App\Models\Devotional;
use App\Models\Feed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Validator;

use App\Http\Resources\DevotionalCollection;
use App\Traits\Interactable;


class DevotionalController extends Controller
{
    use Interactable;

    public function create(Request $request)
    {
        $request->validate([
            'title' => 'string|required|max:255',
            'opening_prayer' => 'string|nullable',
            'closing_prayer' => 'string|nullable',
            'body' => 'string|required',
            'memory_verse' => 'string|nullable|max:255',
            'day' => 'nullable|date',
        ]);


        $data = collect($request->all())->toArray();
        $userId = Auth::id();
        $data['user_id'] = $userId;
        $data['poster_id'] = $userId;
        $data['poster_type'] = 'user';
        $result = Devotional::create($data);

        // Adopt any relations the client saved against this draft's uuid.
        $result->claimDraftRelations();

        // Add to feed so followers see devotionals.
        Feed::create(['parentable_type' => 'devotional', 'postable_type' => 'user', 'postable_id' => $userId, 'parentable_id' => $result->id]);

        if ($result) {
            return response()->json(['data' => $result], 201);
        } else {
            return response()->json(['data' => false, 'errors' => 'unknown error occured'], 400);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'integer|required|exists:devotionals,id',
            'title' => 'string|required|max:255',
            'opening_prayer' => 'string|nullable',
            'closing_prayer' => 'string|nullable',
            'body' => 'string|required',
            'memory_verse' => 'string|nullable|max:255',
            'day' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }
        $data = collect($request->all())->toArray();
        $data['poster_id'] = Auth::user()->id;
        $data['poster_type'] = 'user';
        $id = $request->route('id');
        $result = Devotional::find($id);

        if (!$result) {
            return response()->json(['data' => false, 'errors' => 'devotional not found'], 404);
        }

        $interacted = $this->saveRelated($data, $result);
        $result = $result->update($data);


        if ($result) {
            return response()->json(['data' => true], 200);
        } else {
            return response()->json(['data' => false, 'errors' => 'unknown error occured'], 400);
        }
    }

    public function get(Request $request)
    {
        $id = (int) $request->route('id');
        $userId = Auth::user()->id;
        if ($devotional = Devotional::withCount('comments')
            ->with(['poster', 'churches'])
            ->with(['devotees' => function ($query) {
                $query->limit(7);
            }])
            ->withCount([
                'devotees',
                'devotees as devoted' => function (Builder $query) use ($userId) {
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
                'data' => $devotional
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
        $devotionals = Devotional::with('poster')->with(['devotees' => function ($query) {
            $query->limit(7);
        }])->withCount([
            'devotees',
            'devotees as devoted' => function (Builder $query) use ($userId) {
                $query->where('user_id', $userId);
            },
        ])
            ->orderBy('devotionals.created_at', 'DESC');
        if (!empty($query)) {
            $devotionals = $devotionals->search($query);
        }
        $length = (int) (empty($request['perPage']) ? 15 : $request['perPage']);
        $devotionals = $devotionals->paginate($length);
        $data = new DevotionalCollection($devotionals);
        return response()->json($data);
    }

    public function devote(Request $request)
    {
        $id = $request->route('id');
        $tog = $request['value'];
        $userId = Auth::id();
        $devotional = Devotional::find((int)$id);
        if (!$devotional) {
            return response()->json(['data' => false], 404);
        }
        if ($tog) {
            $devotional->devotees()->attach($userId);
            return response()->json(['data' => true]);
        }
        $devotional->devotees()->detach($userId);
        return response()->json(['data' => false]);
    }


    public function delete(Request $request)
    {
        $id = (int) $request->route('id');
        if ($devotional = Devotional::find($id)) {
            $devotional->delete();
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
