<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;

use App\Event;
use App\Http\Resources\EventCollection;
use App\Models\HierarchyTree;

class HierarchyTreeController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'string|max:255',
            'user_id' => 'nullable|integer|exists:users,id' //TODO: add required if to this
        ]);


        $data = collect($request->all())->toArray();
        $result = HierarchyTree::create($data);
        //create event emitter or reminder or notifications for those who may be interested

        if ($result) {
            return response()->json(['data' => true], 201);
        } else {
            return response()->json(['data' => false, 'errors' => 'unknown error occurred'], 400);
        }
    }
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'integer|required|exists:heirarchies,id',
            'name' => 'string|max:255',
            'user_id' => 'nullable|integer|exists:users,id' //TODO: add required if to this
        ]);


        $data = collect($request->all())->toArray();
        $id = $request->route('id');
        $result = HierarchyTree::find($id);
        //update result
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
        if ($Hierarchy = HierarchyTree::find($id)) {
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
        $request->validate([
            'q' => 'nullable|string|min:3',
        ]);
        $query = $request['q'];
        $heirarchies = HierarchyTree::where('hierarchy_trees.id', '>', '0');
        if ($query) {
            $heirarchies = $heirarchies->search($query);
        }

        //here insert search parameters and stuff
        $length = (int)(empty($request['perPage']) ? 15 : $request['perPage']);
        $data = $heirarchies->paginate($length);
        // $data = new EventCollection($events);
        return response()->json($data);
    }


    public function delete(Request $request)
    {
        $id = (int)$request->route('id');
        if ($hierarchy = HierarchyTree::find($id)) {
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
}
