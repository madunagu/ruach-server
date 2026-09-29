<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

use App\Models\Hierarchy;

class HierarchyController extends Controller
{
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rank' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'user_id' => 'nullable|integer|exists:users,id',
            'hierarchyable_type' => 'nullable|string|in:post,event,audio,video,devotional',
            'hierarchyable_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $data = collect($request->all())->toArray();

        // Auto-assign rank to the end of the list when not provided.
        if (empty($data['rank'])) {
            $data['rank'] = (int) Hierarchy::max('rank') + 1;
        }

        $result = Hierarchy::create($data);

        // Link to the parent object when provided.
        if ($result && !empty($data['hierarchyable_type']) && !empty($data['hierarchyable_id'])) {
            $this->linkToObject($result->id, $data['hierarchyable_type'], (int) $data['hierarchyable_id']);
        }

        if ($result) {
            return response()->json(['data' => $result], 201);
        } else {
            return response()->json(['data' => false, 'errors' => 'unknown error occurred'], 400);
        }
    }

    /**
     * Create (or replace) multiple hierarchies linked to a parent object.
     *
     * Accepts:
     *   hierarchyable_type  — post|event|audio|video|devotional
     *   hierarchyable_id    — the ID of the parent object
     *   hierarchies         — array of { name, rank?, user_id? }
     *
     * Any previous hierarchies linked to the same object are deleted first,
     * making this an "updateMulti" operation.
     */
    public function createMulti(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hierarchyable_type' => 'required|string|in:post,event,audio,video,devotional',
            'hierarchyable_id' => 'required|integer',
            'hierarchies' => 'required|array|min:1',
            'hierarchies.*.rank' => 'nullable|integer',
            'hierarchies.*.name' => 'required|string|max:255',
            'hierarchies.*.user_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $type = $request['hierarchyable_type'];
        $id = (int) $request['hierarchyable_id'];
        $items = $request['hierarchies'];

        return DB::transaction(function () use ($type, $id, $items) {
            // 1. Delete previous hierarchy links for this object.
            $oldHierarchyIds = DB::table('hierarchyables')
                ->where('hierarchyable_type', $type)
                ->where('hierarchyable_id', $id)
                ->pluck('hierarchy_id');

            DB::table('hierarchyables')
                ->where('hierarchyable_type', $type)
                ->where('hierarchyable_id', $id)
                ->delete();

            // Detach the old hierarchy rows themselves (they are no longer linked).
            if ($oldHierarchyIds->isNotEmpty()) {
                Hierarchy::whereIn('id', $oldHierarchyIds)->delete();
            }

            // 2. Create new hierarchies and link them.
            $nextRank = (int) Hierarchy::max('rank') + 1;
            $created = [];

            foreach ($items as $item) {
                if (empty($item['rank'])) {
                    $item['rank'] = $nextRank++;
                }

                $hierarchy = Hierarchy::create($item);
                $this->linkToObject($hierarchy->id, $type, $id);
                $created[] = $hierarchy;
            }

            $hierarchies = Hierarchy::with('user')->find(collect($created)->pluck('id'));

            return response()->json(['data' => $hierarchies], 201);
        });
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'integer|required|exists:hierarchies,id',
            'rank' => 'nullable|integer',
            'name' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer|exists:users,id',
            'hierarchyable_type' => 'nullable|string|in:post,event,audio,video,devotional',
            'hierarchyable_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $data = collect($request->all())->toArray();
        $id = $request->route('id');
        $result = Hierarchy::find($id);

        if (!$result) {
            return response()->json(['data' => false, 'errors' => 'hierarchy not found'], 404);
        }

        $result->update($data);

        // Update the parent-object link when provided.
        if (!empty($data['hierarchyable_type']) && !empty($data['hierarchyable_id'])) {
            // Remove old links for this hierarchy.
            DB::table('hierarchyables')
                ->where('hierarchy_id', $id)
                ->delete();

            $this->linkToObject($id, $data['hierarchyable_type'], (int) $data['hierarchyable_id']);
        }

        return response()->json(['data' => true], 200);
    }

    /**
     * Reorder hierarchies by accepting an ordered list of IDs.
     * The first ID gets rank 1, the second rank 2, etc.
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:hierarchies,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $rank = 1;
        foreach ($request['ids'] as $id) {
            Hierarchy::where('id', $id)->update(['rank' => $rank++]);
        }

        return response()->json(['data' => true], 200);
    }

    public function get(Request $request)
    {
        $id = (int)$request->route('id');
        if ($Hierarchy = Hierarchy::with('user')->find($id)) {
            return response()->json([
                'data' => $Hierarchy
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
            'q' => 'nullable|string|min:3',
            'hierarchyable_type' => 'nullable|string|in:post,event,audio,video,devotional',
            'hierarchyable_id' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $query = $request['q'];
        $hierarchies = Hierarchy::with('user')->where('hierarchies.id', '>', '0');

        // Filter by parent object when provided.
        if (!empty($request['hierarchyable_type']) && !empty($request['hierarchyable_id'])) {
            $hierarchies->whereIn('hierarchies.id', function ($q) use ($request) {
                $q->select('hierarchy_id')
                  ->from('hierarchyables')
                  ->where('hierarchyable_type', $request['hierarchyable_type'])
                  ->where('hierarchyable_id', (int) $request['hierarchyable_id']);
            });
        }

        if ($query) {
            $hierarchies = $hierarchies->search($query);
        }
        $hierarchies = $hierarchies->orderBy('rank', 'ASC');
        $length = (int)(empty($request['perPage']) ? 15 : $request['perPage']);
        $data = $hierarchies->paginate($length);
        return response()->json($data);
    }

    public function delete(Request $request)
    {
        $id = (int)$request->route('id');
        if ($hierarchy = Hierarchy::find($id)) {
            // Clean up pivot links first.
            DB::table('hierarchyables')
                ->where('hierarchy_id', $id)
                ->delete();

            $hierarchy->delete();
            return response()->json([
                'data' => true
            ], 200);
        } else {
            return response()->json([
                'data' => false
            ], 404);
        }
    }

    /**
     * Link a hierarchy to a parent object via the hierarchyables pivot.
     */
    private function linkToObject(int $hierarchyId, string $type, int $id): void
    {
        DB::table('hierarchyables')->insert([
            'hierarchy_id' => $hierarchyId,
            'hierarchyable_type' => $type,
            'hierarchyable_id' => $id,
        ]);
    }
}
