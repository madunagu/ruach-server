<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

// use Validator;
// use Spatie\Geocoder;
use App\Models\Address;

class AddressController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'address1' => 'string|required|max:255',
            'address2' => 'nullable|string|max:255',
            'country' => 'string|required|max:255',
            'state' => 'string|required|max:255',
            'city' => 'string|required|max:255',
            'postal_code' => 'nullable|string|max:20',
            'default_address' => 'nullable|boolean',
            'name' =>  'nullable|string|max:255',
            'longitude' => 'nullable|numeric|max:255',
            'latitude' => 'nullable|numeric|max:255'
        ]);

        $data = collect($request->all())->toArray();
        $data['user_id'] = Auth::user()->id;

        $result = Address::create($data);
        //obtain longitude and latitude if they werent set
        if (!$result->longitude || !$result->latitude) {
            // queue set latitude and longitude event
            // $coor = $this->find_address_geolocation($result);

            // $result->longitude = $coor[0];
            // $result->latitude = $coor[1];
            // $result->update();
        }

        if ($result) {
            return response()->json(['data' => $result], 201);
        } else {
            return response()->json(['data' => false, 'errors' => 'unknown error occured'], 400);
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'integer|required|exists:addresses,id',
            'address1' => 'string|required|max:255',
            'address2' => 'nullable|string|max:255',
            'country' => 'string|required|max:255',
            'state' => 'string|required|max:255',
            'city' => 'string|required|max:255',
            'postal_code' => 'nullable|integer|max:20',
            'default_address' => 'nullable|boolean',
            'name' =>  'nullable|string|max:255',
            'longitude' => 'nullable|float|max:255',
            'latitude' => 'nullable|float|max:255'
        ]);

        $id = $request->route('id');

        // TODO: find the neccessity of checking the user id
        $data = collect($request->all())->toArray();
        $data['user_id'] = Auth::user()->id;
        $result = Address::find($id);
        //obtain longitude and latitude if they werent set
        if (!$result->longitude || !$result->latitude) {
            //que set latitude and longitude event
            $this->find_address_geolocation($result);
        }
        $result = $result->update($data);
        if ($result) {
            return response()->json(['data' => true], 201);
        } else {
            return response()->json(['data' => false, 'errors' => 'unknown error occured'], 400);
        }
    }

    public function find_address_geolocation(Address $address)
    {
        // $client = new \GuzzleHttp\Client();

        // $geocoder = new Geocoder($client);

        // $geocoder->setApiKey(config('geocoder.key'));

        // $geocoder->setCountry(config('geocoder.country', 'US'));

        // $res = $geocoder->getCoordinatesForAddress($address->toString());
        // $address->lattitude = $res->lat;
        // $address->longitude = $res->lng;

        // return [$res->lat, $res->lng];
    }

    public function get(Request $request)
    {
        $id = (int)$request->route('id');
        // $address = Address::find($id);
        // return response()->json([
        //         'data' => $address
        //     ], 200);

        if ($address = Address::find($id)) {
            return response()->json([
                'data' => $address
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
            'q' => 'nullable|string|min:1'
        ]);

        $query = $request['q'];
        $addresses = Address::where('id', '>', '1'); //TODO: check if this is a valid condition
        if ($query) {
            $addresses = $addresses->search($query);
        }
        $length = (int) (empty($request['perPage']) ? 15 : $request['perPage']);
        $data = $addresses->paginate($length);

        return response()->json(compact('data'));
    }

    public function delete(Request $request)
    {
        $id = (int)$request->route('id');
        if ($address = Address::find($id)) {
            $address->delete();
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
