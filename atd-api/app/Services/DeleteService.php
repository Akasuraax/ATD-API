<?php

namespace App\Services;

use App\Models\Journey;
use App\Models\Step;
use App\Models\Vehicle;
use App\Models\Drives;
use http\Env\Response;

Class DeleteService{
    public function deleteVehicleService($id){
        $vehicle = Vehicle::find($id);

        if($vehicle && !$vehicle->archive){
            $vehicle->archive = true;
            $vehicle->save();

            $drives = Drives::where('id_vehicle', $id)->get();
            $response = ['message'=>'Deleted !'];

            if(!$drives->isEmpty()){
                foreach ($drives as $drive) {
                    Drives::where('id_vehicle', $drive->id_vehicle)->update(['archive' => true]);
                    $journeys = Journey::where('id', $drive->id_journey)->get();
                    foreach($journeys as $journey){
                        $service = new DeleteService();
                        $service->deleteJourneyService($journey->id);
                    }

                    $response[] = ['notice' => 'The vehicle you had still have journeys, they have been deleted'];
                }
            }
            $status = 200;
        }else{
            $response = ['message'=>'Your element doesn\'t exists'];
            $status = 404;
        }
        return Response($response, $status);
    }

    public function deleteJourneyService($id){
        $journey = Journey::find($id);

        if($journey && !$journey->archive){
            $journey->archive = true;
            $journey->save();

            $steps = Step::where('id_journey', $id)->get();
            $response = ['message'=>'Deleted !'];

            if(!$steps->isEmpty()){
                foreach($steps as $step){
                    $service = new DeleteService();
                    $service->deleteStepService($step->id);
                }
                $response[] = ['notice' => 'The journey you had still have steps, they have been deleted'];
            }
            $status = 200;
        }else{
            $response = ['message'=>'Your element doesn\'t exists'];
            $status = 404;
        }

        return Response($response, $status);
    }

    public function deleteStepService($id){
        $step = Step::find($id);
        if($step && !$step->archive) {
            $step->archive = true;
            $step->save();

            $response = ['message' => 'Deleted!'];
            $status = 200;
        } else
        {
            $response = ['message' => 'Your element doesn\'t exist'];
            $status = 404;
        }

        return Response($response, $status);
    }


}
