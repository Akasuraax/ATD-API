<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Journey;
use App\Models\Step;
use App\Models\Vehicle;
use App\Http\Services\DistanceMatrixService;
use DateTime;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class JourneyController extends Controller
{
    protected DistanceMatrixService $distanceMatrixService;

    public function __construct()
    {
        $this->distanceMatrixService = new DistanceMatrixService();
    }
    public function createJourney(Request $request)
    {
        $array = json_decode($request->getContent(), true);

        //check if the vehicle exists
        $vehicle = Vehicle::findOrFail($array['vehicle']['id']);
        if($vehicle->archive)
            return Response(['message'=>'The vehicle you selected is archived.'], 404);

        $activity = Activity::findOrfail($array['activity']['id']);
        if($activity->archive)
            return Response(['message'=>'The activity you selected is archived.'], 404);

        $result = $this->callGoogleApi($array["steps"]);
        $json_string = trim($result->getOriginalContent()["output"], '"');
        $json_string = str_replace("'", '"', $json_string);

        $steps = json_decode($json_string);

        $total_distance = 0;
        $total_time = 0;

        for ($i = 0; $i < count($steps) - 1; $i++) {
            $node = $steps[$i];
            $next_node = $steps[$i + 1];

            $travel = $this->distanceMatrixService->getDistance($node, $next_node);
            $time = $travel['rows'][0]['elements'][0]['duration_in_traffic']['value'];
            $distance = $travel['rows'][0]['elements'][0]['distance']['value'];
            $total_distance += $distance;
            $total_time += $time;
        }

        $journey = Journey::create([
            'name' => $array['journey']['name'],
            'duration' => $total_time,
            'distance' => $total_distance,
            'id_activity' => $activity->id ?? null
        ]);

        $stepsArray = [];
        $dateTime = new DateTime($activity->start_date);
        $date = $dateTime->format('Y-m-d');

        foreach ($steps as $node) {
            foreach ($array["steps"] as $stepData) {
                if ($node === $stepData['address'] . ' ' . $stepData['zipcode']) {
                    $dateTimeString = $date . ' ' . $stepData['time'];

                    $step = Step::create([
                        'address' => $stepData['address'],
                        'zipcode' =>  $stepData['zipcode'],
                        'time' => $dateTimeString,
                        'id_journey' =>  $journey->id
                    ]);

                    $stepsArray[] = $step;
                    break;
                }
            }
        }

        $journey->vehicles()->attach($vehicle->id, ['archive' => false]);
        $total_hours = floor($journey->duration / 3600);
        $total_minutes = floor(($journey->duration % 3600) / 60);

        return Response(['journey' => [
            'id' => $journey->id,
            'name' => $journey->name,
            'distance' => $journey->distance/1000,
            'duration' => $total_hours . "h " . $total_minutes ."min",
            'activity' => $activity,
            'vehicle' => $vehicle,
            'steps' => $stepsArray
        ]], 201);
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
        $journey = Journey::findOrFail($id);
        $steps = Step::where('id_journey', $id)->get()->toArray();
        $activity = Activity::where('id', $journey->id_activity)->first();
        $total_hours = floor($journey->duration / 3600);
        $total_minutes = floor(($journey->duration % 3600) / 60);

        return response()->json([
           "journey" => [
               'id' => $journey->id,
               'name' => $journey->name,
               'duration' => $total_hours . "h " . $total_minutes ."min",
               'distance' => $journey->distance/1000,
               'archive' => $journey->archive,
               'created_at' => $journey->created_at,
               'updated_at' => $journey->update_at,
               'activity' => $activity,
               'steps' => $steps,
           ]
        ]);
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

    public function callGoogleApi(array $steps){


        $nodes = [];
        foreach ($steps as $step) {
            $nodes[] = $step["address"] . " " . $step["zipcode"];
        }

        $graph = [];
        foreach ($nodes as $i => $node) {
            foreach ($nodes as $j => $otherNode) {
                if ($i < $j && !isset($graph[$node][$otherNode])) {
                    $weight = $this->distanceMatrixService->getDistance($node, $otherNode)['rows'][0]['elements'][0]['duration_in_traffic']['value'];

                    $graph[$node][$otherNode] = $weight;
                    $graph[$otherNode][$node] = $weight;
                }
            }
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

        $pythonScript = base_path('public/main.py');

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
