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
            // Numeric primary key of a saved row, or a draft uuid.
            'hierarchyable_id' => ['nullable', function ($attribute, $value, $fail) {
                if ($value === null || $value === '') {
                    return;
                }
                $value = (string) $value;
                if (ctype_digit($value) || \App\Models\Event::isUsableUuid($value)) {
                    return;
                }
                $fail('The ' . $attribute . ' must be an integer id or a valid uuid.');
            }],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $data = collect($request->all())->toArray();
        unset($data['hierarchyable_type'], $data['hierarchyable_id']);

        // Auto-assign rank to the end of the list when not provided.
        if (empty($data['rank'])) {
            $data['rank'] = (int) Hierarchy::max('rank') + 1;
        }

        $result = Hierarchy::create($data);

        // Link to the parent object when provided.
        if ($result && !empty($request['hierarchyable_type']) && !empty($request['hierarchyable_id'])) {
            $this->linkToObject(
                $result->id,
                $this->resolveReference(
                    $request['hierarchyable_type'],
                    (string) $request['hierarchyable_id']
                )
            );
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
            // Accepts the numeric primary key of a saved row or the uuid
            // minted for an unsaved draft. The draft path matters for a create
            // form: the hierarchy is saved before its parent row exists, and
            // the uuid is what links them.
            'hierarchyable_id' => ['required', function ($attribute, $value, $fail) {
                $value = (string) $value;
                if (ctype_digit($value)) {
                    return;
                }
                if (\App\Models\Event::isUsableUuid($value)) {
                    return;
                }
                $fail('The ' . $attribute . ' must be an integer id or a valid uuid.');
            }],
            'hierarchies' => 'required|array|min:1',
            'hierarchies.*.rank' => 'nullable|integer',
            'hierarchies.*.name' => 'required|string|max:255',
            'hierarchies.*.user_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $type = $request['hierarchyable_type'];
        $items = $request['hierarchies'];

        return DB::transaction(function () use ($type, $items, $request) {
            $reference = $this->resolveReference(
                $type,
                (string) $request['hierarchyable_id']
            );

            // 1. Replace any previous links for this object.
            $oldHierarchyIds = $this->pivotQuery($reference)
                ->pluck('hierarchy_id');

            $this->pivotQuery($reference)->delete();

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
                $this->linkToObject($hierarchy->id, $reference);
                $created[] = $hierarchy;
            }

            $hierarchies = Hierarchy::with('user')->find(collect($created)->pluck('id'));

            return response()->json(['data' => $hierarchies], 201);
        });
    }

    /**
     * Splits the client-supplied parent reference into the columns that link it.
     *
     * A numeric value is the primary key of a saved row. A uuid is a draft
     * whose row does not exist yet, so it is stored in `hierarchyable_uuid`
     * and claimed once the parent is created.
     *
     * @return array{type: string, id: int|null, uuid: string|null}
     */
    private function resolveReference(string $type, string $reference): array
    {
        if (ctype_digit($reference)) {
            return [
                'type' => $type,
                'id' => (int) $reference,
                'uuid' => null,
            ];
        }

        return [
            'type' => $type,
            'id' => null,
            'uuid' => $reference,
        ];
    }

    /** Query for existing pivot rows matching the resolved reference. */
    private function pivotQuery(array $reference)
    {
        $query = DB::table('hierarchyables')
            ->where('hierarchyable_type', $reference['type']);

        return $reference['uuid'] === null
            ? $query->where('hierarchyable_id', $reference['id'])
            : $query->where('hierarchyable_uuid', $reference['uuid']);
    }

    /**
     * Writes one hierarchy link, using whichever column the reference needs.
     */
    private function linkToObject(int $hierarchyId, array $reference): void
    {
        DB::table('hierarchyables')->insert([
            'hierarchy_id' => $hierarchyId,
            'hierarchyable_type' => $reference['type'],
            'hierarchyable_id' => $reference['id'] ?? 0,
            'hierarchyable_uuid' => $reference['uuid'],
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'integer|required|exists:hierarchies,id',
            'rank' => 'nullable|integer',
            'name' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer|exists:users,id',
            'hierarchyable_type' => 'nullable|string|in:post,event,audio,video,devotional',
            'hierarchyable_id' => ['nullable', function ($attribute, $value, $fail) {
                if ($value === null || $value === '') {
                    return;
                }
                $value = (string) $value;
                if (ctype_digit($value) || \App\Models\Event::isUsableUuid($value)) {
                    return;
                }
                $fail('The ' . $attribute . ' must be an integer id or a valid uuid.');
            }],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $data = collect($request->all())->toArray();
        unset($data['hierarchyable_type'], $data['hierarchyable_id']);
        $id = $request->route('id');
        $result = Hierarchy::find($id);

        if (!$result) {
            return response()->json(['data' => false, 'errors' => 'hierarchy not found'], 404);
        }

        $result->update($data);

        // Update the parent-object link when provided.
        if (!empty($request['hierarchyable_type']) && !empty($request['hierarchyable_id'])) {
            // Remove old links for this hierarchy.
            DB::table('hierarchyables')
                ->where('hierarchy_id', $id)
                ->delete();

            $this->linkToObject(
                $id,
                $this->resolveReference(
                    $request['hierarchyable_type'],
                    (string) $request['hierarchyable_id']
                )
            );
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
            'hierarchyable_id' => ['nullable', function ($attribute, $value, $fail) {
                if ($value === null || $value === '') {
                    return;
                }
                $value = (string) $value;
                if (ctype_digit($value) || \App\Models\Event::isUsableUuid($value)) {
                    return;
                }
                $fail('The ' . $attribute . ' must be an integer id or a valid uuid.');
            }],
        ]);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $query = $request['q'];
        $hierarchies = Hierarchy::with('user')->where('hierarchies.id', '>', '0');

        // Filter by parent object when provided.
        if (!empty($request['hierarchyable_type']) && !empty($request['hierarchyable_id'])) {
            $reference = $this->resolveReference(
                $request['hierarchyable_type'],
                (string) $request['hierarchyable_id']
            );
            $hierarchies->whereIn('hierarchies.id', function ($q) use ($reference) {
                $pivot = $q->select('hierarchy_id')
                  ->from('hierarchyables')
                  ->where('hierarchyable_type', $reference['type']);
                if ($reference['uuid'] === null) {
                    $pivot->where('hierarchyable_id', $reference['id']);
                } else {
                    $pivot->where('hierarchyable_uuid', $reference['uuid']);
                }
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
    private function linkToObjectLegacy(int $hierarchyId, string $type, int $id): void
    {
        DB::table('hierarchyables')->insert([
            'hierarchy_id' => $hierarchyId,
            'hierarchyable_type' => $type,
            'hierarchyable_id' => $id,
        ]);
    }
}
