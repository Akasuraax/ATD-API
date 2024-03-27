<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AddressController extends Controller
{

    public function address(Request $request) {
        $input = $request->input('input');
        $apiKey = env('GOOGLE_MAPS_API_KEY');

        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/place/autocomplete/json', [
                'input' => $input,
                'key' => $apiKey,
                'types' => '(cities)',
            ]);

            return $response->json();
        } catch (\Throwable $th) {
            return response()->json(['message' => $th], 500);
        }
    }

}
