<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

use App\Models\Society;
use App\Http\Resources\SocietyCollection;
use App\Traits\Interactable;

class SocietyController extends Controller
{
    use Interactable;
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|required|max:255',
            'church_id' => 'integer|exists:churches,id',
            'parent_id' => 'nullable|integer',
            'closed' => 'nullable|boolean',
            'hierarchy_group_id'=> 'nullable|integer|exists:hierarchy_groups,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $data = collect($request->all())->toArray();

        $data['user_id'] = Auth::user()->id;
        $data['poster_id'] = Auth::user()->id;
        $data['poster_type'] = 'user';

        $result = Society::create($data);
        $saved = $this->saveRelated($data,$result);
        //here add current user as member of society


        if ($result) {
            return response()->json(['data'=>true], 201);
        } else {
            return response()->json(['data'=>false,'errors'=>'unknown error occured'], 400);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'=> 'integer|required|exists:societies,id',
            'name' => 'string|required|max:255',
            'church_id' => 'integer|exists:churches,id',
            'parent_id' => 'nullable|integer',
            'closed' => 'nullable|boolean',
            'hierarchy_group_id'=> 'nullable|integer|exists:hierarchy_groups,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $id = (int) $request->route('id');

        $data = collect($request->all())->toArray();
        $data['user_id'] = Auth::user()->id;
        $result = Society::find($id);
        $result->update($data);

        if ($result) {
            return response()->json(['data'=>true], 201);
        } else {
            return response()->json(['data'=>false,'errors'=>'unknown error occured'], 400);
        }
    }

    public function get(Request $request)
    {
        $id = (int)$request->route('id');
        if ($society = Society::find($id)) {
            return response()->json([
                'data' => $society
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

        $query = $request['q'];
        $societies = Society::with('churches');
        if($query){
            $societies = $societies->search($query);
        }
        //here insert search parameters and stuff
        $length = (int)(empty($request['perPage']) ? 15 : $request['perPage']);
        $societies = $societies->paginate($length);
        $data = new SocietyCollection($societies);
        return response()->json($data);
    }

    public function delete(Request $request)
    {
        $id = (int)$request->route('id');
        if ($society = Society::find($id)) {
            $society->delete();
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
