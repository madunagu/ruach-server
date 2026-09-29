<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

use App\Models\Address;
use App\Services\GeocodingService;

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
            'longitude' => 'nullable|numeric|between:-180,180',
            'latitude' => 'nullable|numeric|between:-90,90'
        ]);

        $data = collect($request->all())->toArray();
        $data['user_id'] = Auth::user()->id;

        $result = Address::create($data);

        // Obtain longitude and latitude if they weren't provided.
        if (!$result->longitude || !$result->latitude) {
            $this->find_address_geolocation($result);
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
            'postal_code' => 'nullable|string|max:20',
            'default_address' => 'nullable|boolean',
            'name' =>  'nullable|string|max:255',
            'longitude' => 'nullable|numeric|between:-180,180',
            'latitude' => 'nullable|numeric|between:-90,90'
        ]);

        $id = $request->route('id');

        $data = collect($request->all())->toArray();
        $data['user_id'] = Auth::user()->id;
        $result = Address::find($id);

        if (!$result) {
            return response()->json(['data' => false, 'errors' => 'address not found'], 404);
        }

        // Obtain longitude and latitude if they weren't provided.
        if (!$result->longitude || !$result->latitude) {
            $this->find_address_geolocation($result);
        }

        $result = $result->update($data);
        if ($result) {
            return response()->json(['data' => true], 200);
        } else {
            return response()->json(['data' => false, 'errors' => 'unknown error occured'], 400);
        }
    }

    /**
     * Geocode the address via Nominatim (OpenStreetMap) and persist lat/lng.
     * Best-effort: failures are logged but never thrown.
     */
    public function find_address_geolocation(Address $address): void
    {
        $coords = app(GeocodingService::class)->geocode($address->toString());
        if ($coords) {
            $address->update([
                'latitude' => $coords['latitude'],
                'longitude' => $coords['longitude'],
            ]);
        }
    }

    public function get(Request $request)
    {
        $id = (int)$request->route('id');
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
        $addresses = Address::where('id', '>', '1');
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
