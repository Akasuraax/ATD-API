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

        if(!Journey::find($validateData['id_journey']) || Journey::find($validateData['id_journey'])->archive)
            return Response(['message'=>'The vehicle you selected doesn\'t exist!'], 404);

        $defaultDate = date('Y-m-d'); // Use today's date
        $timeWithDefaultDate = $defaultDate . ' ' . $validateData['time'];

        $step = Step::create([
            'address' => $validateData['address'],
            'zipcode' =>  $validateData['zipcode'],
            'time' => $timeWithDefaultDate,
            'id_journey' =>  $validateData['id_journey']
        ]);

        $response = [
            'step' => $step
        ];

        return Response($response, 201);
    }

    public function getSteps(){
        return Step::select('steps.id', 'steps.address', 'steps.zipcode', 'steps.time', 'steps.id_journey', 'journeys.name as journey_name' ,'steps.archive')
            ->join('journeys', 'steps.id_journey', '=', 'journeys.id')
            ->where('steps.archive', false)
            ->get();
    }

    public function getJourneySteps($id){
        $journey = Journey::find($id);

        if($journey && !$journey->archive) {
            return Step::select('steps.id', 'steps.address', 'steps.zipcode', 'steps.time', 'steps.id_journey', 'journeys.name as journey_name' ,'steps.archive')
                ->join('journeys', 'steps.id_journey', '=', 'journeys.id')
                ->where('steps.id_journey', $id)
                ->where('steps.archive', false)
                ->get();
        } else
        {
            $response = ['message' => 'Your element doesn\'t exist'];
            $status = 404;
        }

        return Response($response, $status);
    }

    public function deleteStep($id){
        $service = new DeleteService();
        return $service->deleteStepService($id);
    }

    public function updateStep($id, Request $request){
        $step = Step::find($id);

        if($step && !$step->archive){
            try{
                $requestData = $request->validate([
                    'address' => 'string',
                    'zipcode' => 'int|digits:5',
                    'time' => 'date_format:H:i',
                    'id_journey' => 'int'
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
            $response = [
                'step' => $step
            ];

            $status = 200;
        }else{
            $response = [
                'message'=>'Your element doesn\'t exists'
            ];
            $status = 404;
        }

        return Response($response, $status);
    }
}
