<?php

namespace App\Http\Controllers;

use App\Models\Journey;
use App\Models\Vehicle;
use App\Services\DeleteService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class JourneyController extends Controller
{
    public function createJourney(Request $request)
    {
        try{
            $validateData = $request->validate([
                'name' => 'string|required|max:255',
                'duration' => 'int|required',
                'distance' => 'int|required',
                'cost' => 'int|required',
                'fuel_cost' => 'int|required',
                'id_vehicle' => 'int|required'
             ]);
        }catch (ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        if(!Vehicle::find($validateData['id_vehicle']) || Vehicle::find($validateData['id_vehicle'])->archive)
            return Response(['message'=>'The vehicle you selected doesn\'t exist!'], 404);

        $journey = Journey::create([
            'name' => $validateData['name'],
            'duration' => $validateData['duration'],
            'distance' => $validateData['distance'],
            'cost' => $validateData['cost'],
            'fuel_cost' => $validateData['fuel_cost']
        ]);

        $journey->vehicles()->attach($validateData['id_vehicle'], ['archive' => false]);

        $response = [
            'journey' => $journey
        ];

        return Response($response, 201);
    }

    public function getJourneys(){
        return Journey::all();
    }

    public function deleteJourney($id){
        $service = new DeleteService();
        return $service->deleteJourneyService($id);
    }

    public function updateJourney($id, Request $request){
        $journey = Journey::find($id);

        if($journey && !$journey->archive) {
            try{
                $requestData = $request->validate([
                    'name' => 'string|max:255',
                    'duration' => 'int',
                    'distance' => 'int',
                    'cost' => 'int',
                    'fuel_cost' => 'int',
                    'id_vehicle' => 'int'
                ]);
            }catch (ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }

            foreach ($requestData as $key => $value) {
                if (in_array($key, $journey->getFillable()) || $key === 'id_vehicle') {
                    if ($key === 'id_vehicle') {
                        $vehicle = Vehicle::find($value);
                        if (!$vehicle || $vehicle->archive) {
                            return response()->json(['message' => 'The vehicle doesn\'t exist'], 404);
                        }
                        $journey->vehicles()->sync($value, ['archive' => false]);
                    } else {
                        $journey->$key = $value;
                    }
                }
            }
            $journey->save();
            $response = [
                'journey' => $journey
            ];

            $status = 200;
        } else {
            $response = [
                'message' => 'Your element doesn\'t exist'
            ];
            $status = 404;
        }

        return response()->json($response, $status);
    }

}
