<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Journey;
use App\Models\Vehicle;
use App\Services\DeleteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
                'vehicle.id' => 'int|required',
                'activity.id' => 'nullable|int'
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
            'id_activity' => $validateData['activity']['id'] ?? null
        ]);

        $journey->vehicles()->attach($validateData['vehicle']['id'], ['archive' => false]);

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

        $journey = Journey::select('journeys.id', 'journeys.name', 'journeys.duration', 'journeys.distance', 'journeys.cost', 'journeys.fuel_cost', 'journeys.id_activity', 'vehicles.name as vehicle_name', 'vehicles.license_plate','journeys.archive', 'journeys.id_activity')
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
        try{
            $journey = Journey::findOrFail($id);
            if($journey->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);

            $journey->archive();
            $journey = Journey::findOrFail($id);
            return response()->json(['journey' => $journey,  'message' => "Deleted !"], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateJourney($id, Request $request){
        try{
            $journey = Journey::findOrFail($id);
            try{
                $requestData = $request->validate([
                    'name' => 'required|string|max:255',
                    'duration' => 'required|int',
                    'distance' => 'required|int',
                    'cost' => 'required|int',
                    'archive' => 'required|boolean',
                    'fuel_cost' => 'required|int',
                    'vehicle.id' => 'required|int',
                    'activity.id' => 'required|int'
                ]);
            }catch (ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }

            try{
                $vehicle = Vehicle::where('id', $requestData['vehicle']['id'])->where('archive', false)->firstOrFail();
                $activity = Activity::where('id', $requestData['activity']['id'])->where('archive', false)->firstOrFail();
                $journey->update($requestData);
                $journey->activity()->associate($activity->id);
                $journey->vehicles()->sync($vehicle->id, ['archive' => false]);
                $journey->save();
                $journey->load('vehicles:id,name,license_plate');
                $journey->load('activity:id,title');
            }catch (ModelNotFoundException $e) {
                return response()->json(['error' => 'The element you selected is not found'], 404);
            }

            return response()->json(['journey' => $journey], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
    public function callGoogleApi(Request $request, array $nodes){
        $graph = [];

        foreach ($nodes as $node) {
            $relations = [];
            foreach ($nodes as $otherNode) {
                if ($node !== $otherNode) {
                    $weight = rand(1, 10);

                    $relations[] = [$otherNode, $weight];
                    $graph[$otherNode][] = [$node, $weight];
                }
            }

            $graph[$node] = $relations;
        }
        return $this->executeScript($graph);

    }
    public function executeScript(array $graph)
    {
        $graph = json_encode($graph);

        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $pythonScript = base_path('python_scripts/main.py');

        $process = proc_open("python3 $pythonScript", $descriptorspec, $pipes);

        if (is_resource($process)) {
            fwrite($pipes[0], $graph);
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $return_value = proc_close($process);

            if ($return_value !== 0) {
                return response()->json(['error' => 'Une erreur est survenue lors de l\'exécution du script.']);
            } else {
                return response()->json(['output' => trim($output)]);
            }
        } else {
            return response()->json(['error' => 'Impossible de démarrer le processus.']);
        }
    }


}
