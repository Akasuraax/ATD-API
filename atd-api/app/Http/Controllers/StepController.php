<?php

namespace App\Http\Controllers;

use App\Models\Journey;
use App\Models\Step;
use App\Services\DeleteService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StepController extends Controller
{
    public function createStep(int $journey_id, Request $request)
    {

        $journey = Journey::findOrFail($journey_id);
        try{
            $validateData = $request->validate([
                'address' => 'string|required',
                'zipcode' => 'int|required|digits:5',
                'time' => 'date_format:H:i|required',
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        if($journey->archive)
            return response()->json(['message' => 'The journey selected is archived.'], 405);

        $defaultDate = date('Y-m-d'); // Use today's date
        $timeWithDefaultDate = $defaultDate . ' ' . $validateData['time'];

        $step = Step::create([
            'address' => $validateData['address'],
            'zipcode' =>  $validateData['zipcode'],
            'time' => $timeWithDefaultDate,
            'id_journey' =>  $journey_id
        ]);

        return Response(['step' => $step], 201);
    }

    public function getOneStep(int $journey_id, int $step_id, Request $request){
        Journey::findOrFail($journey_id);
        Step::findOrFail($step_id);
        $step = Step::where('id', $step_id)->get();
        return response()->json([
            'step' => $step
        ]);
    }
    public function getSteps(Request $request){
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 1);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "steps." . $field;

        $step = Step::select('steps.id', 'steps.address', 'steps.zipcode', 'steps.time', 'journeys.name as journey_name' ,'steps.archive')
            ->join('journeys', 'steps.id_journey', '=', 'journeys.id')
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

        return response()->json($step);
    }

    public function getJourneySteps($id_journey){
        return Journey::findOrFail($id_journey) ? Step::where('id_journey', $id_journey)->where('archive', false)->get() : response()->json(['message' => 'Element doesn\'t exist'], 404);
    }

    public function deleteStep(int $id_journey, int $id_step){
        Journey::findOrFail($id_journey);
        Step::findOrFail($id_step);
        $service = new DeleteService();
        return $service->deleteService($id_step, 'App\Models\Step');
    }

    public function updateStep(int $id_journey, int $id_step, Request $request){
        try{
            Journey::findOrFail($id_journey);
            $step = Step::findOrFail($id_step);
            try{
                $requestData = $request->validate([
                    'address' => 'string',
                    'zipcode' => 'int|digits:5',
                    'time' => 'date_format:H:i',
                    'id_journey' => 'int',
                    'archive' => 'boolean'
                ]);
            }catch(ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }

            foreach($requestData as $key => $value){
                if(in_array($key, $step->getFillable())) {
                    if($key == "time"){
                        $defaultDate = date('Y-m-d'); // Use today's date
                        $timeWithDefaultDate = $defaultDate . ' ' . $value;
                        $step->$key = $timeWithDefaultDate;
                    }else
                        $step->$key = $value;
                }
            }
            $step->save();

            return response()->json(['step' => $step], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}
