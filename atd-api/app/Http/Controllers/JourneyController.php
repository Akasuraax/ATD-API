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
                'id_activity' => 'int|nullable',
                'id_vehicle' => 'int|required'
             ]);
        }catch (ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $vehicle = Vehicle::findOrFail($validateData['id_vehicle']);

        if($vehicle->archive)
            return Response(['message'=>'The vehicle you selected is archived.'], 404);

        $journey = Journey::create([
            'name' => $validateData['name'],
            'duration' => $validateData['duration'],
            'distance' => $validateData['distance'],
            'cost' => $validateData['cost'],
            'fuel_cost' => $validateData['fuel_cost'],
            'id_activity' => $validateData['id_activity']
        ]);

        $journey->vehicles()->attach($validateData['id_vehicle'], ['archive' => false]);

        return Response(['journey' => $journey], 201);
    }

    public function getJourneys(Request $request){
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 1);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "journeys." . $field;

        $journey = Journey::select('journeys.id', 'journeys.name', 'journeys.duration', 'journeys.distance', 'journeys.cost', 'journeys.fuel_cost', 'journeys.id_activity', 'vehicles.name as vehicle_name', 'vehicles.license_plate','journeys.archive')
            ->join('drives', 'drives.id_journey', '=', 'journeys.id')
            ->join('vehicles', 'drives.id_vehicle', '=', 'vehicles.id')
            ->where(function ($query) use ($fieldFilter, $operator, $value) {
                if ($fieldFilter && $operator && $value !== '*') {
                    switch ($operator) {
                        case 'contains':
                            $query->where($fieldFilter, 'LIKE', '%' . $value . '%');
                            break;
                        case 'equals':
                            $query->where($fieldFilter, '=', $value);
                            break;
                        case 'startsWith':
                            $query->where($fieldFilter, 'LIKE', $value . '%');
                            break;
                        case 'endsWith':
                            $query->where($fieldFilter, 'LIKE', '%' . $value);
                            break;
                        case 'isEmpty':
                            $query->whereNull($fieldFilter);
                            break;
                        case 'isNotEmpty':
                            $query->whereNotNull($fieldFilter);
                            break;
                        case 'isAnyOf':
                            $values = explode(',', $value);
                            $query->whereIn($fieldFilter, $values);
                            break;
                    }
                }
            })
            ->orderBy($field, $sort)
            ->paginate($perPage, ['*'], 'page', $page + 1);

        return response()->json($journey);
    }

    public function getJourney($id)
    {
        return Journey::find($id) ?  Journey::select('journeys.id', 'journeys.name', 'journeys.duration', 'journeys.distance', 'journeys.cost', 'journeys.fuel_cost', 'journeys.id_activity', 'vehicles.name as vehicle_name', 'vehicles.license_plate','journeys.archive')->join('drives', 'drives.id_journey', '=', 'journeys.id')->join('vehicles', 'drives.id_vehicle', '=', 'vehicles.id')->where('journeys.id', $id)->get() : response()->json(['message' => 'Element doesn\'t exist'], 404);
    }

    public function deleteJourney($id){
        $service = new DeleteService();
        return $service->deleteJourneyService($id);
    }

    public function updateJourney($id, Request $request){
        try{
            $journey = Journey::findOrFail($id);
            try{
                $requestData = $request->validate([
                    'name' => 'string|max:255',
                    'duration' => 'int|nullable',
                    'distance' => 'int|nullable',
                    'cost' => 'int|nullable',
                    'archive' => 'boolean|nullable',
                    'fuel_cost' => 'int|nullable',
                    'id_vehicle' => 'int|nullable',
                    'id_activity' => 'int|nullable'
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

            return response()->json(['journey' => $journey], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

}
