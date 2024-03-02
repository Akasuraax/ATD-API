<?php

namespace App\Http\Controllers;

use App\Models\Journey;
use App\Models\Step;
use App\Services\DeleteService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StepController extends Controller
{
    public function createStep(Request $request)
    {
        try{
            $validateData = $request->validate([
                'address' => 'string|required',
                'zipcode' => 'int|required|digits:5',
                'time' => 'date_format:H:i|required',
                'id_journey' => 'required|int'
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $journey = Journey::findOrFail($validateData['id_journey']);
        if($journey->archive)
            return response()->json(['message' => 'The journey selected is archived.'], 405);

        $defaultDate = date('Y-m-d'); // Use today's date
        $timeWithDefaultDate = $defaultDate . ' ' . $validateData['time'];

        $step = Step::create([
            'address' => $validateData['address'],
            'zipcode' =>  $validateData['zipcode'],
            'time' => $timeWithDefaultDate,
            'id_journey' =>  $validateData['id_journey']
        ]);

        return Response(['step' => $step], 201);
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

    public function getJourneySteps($id){
        return Journey::find($id) ? Step::select('steps.id', 'steps.address', 'steps.zipcode', 'steps.time', 'journeys.name as journey_name' ,'steps.archive')->join('journeys', 'steps.id_journey', '=', 'journeys.id')->where('steps.id', $id)->get() : response()->json(['message' => 'Element doesn\'t exist'], 404);
    }

    public function deleteStep($id){
        $service = new DeleteService();
        return $service->deleteService($id, 'App\Models\Step');
    }

    public function updateStep($id, Request $request){
        try{
            $step = Step::findOrFail($id);
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
