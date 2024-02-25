<?php
namespace App\Services;

use App\Models\Journey;
use App\Models\Piece;
use App\Models\Step;
use App\Models\Vehicle;
use App\Models\Drives;
use http\Env\Response;
use App\Models\Demand;
use Illuminate\Validation\ValidationException;

Class DeleteService{
    public function deleteVehicleService($id){
        try {
            $vehicle = Vehicle::find($id);
            if(!$vehicle || $vehicle->archive)
                return response()->json(['message' => 'Element doesn\'t exist'], 404);
            $vehicle->archive = true;

            $drives = Drives::where('id_vehicle', $id)->get();
            if(!$drives->isEmpty()){
                foreach ($drives as $drive) {
                    Drives::where('id_vehicle', $drive->id_vehicle)->update(['archive' => true]);
                    $journeys = Journey::where('id', $drive->id_journey)->get();
                    foreach($journeys as $journey){
                        $service = new DeleteService();
                        $service->deleteJourneyService($journey->id);
                    }
                }
            }
            $vehicle->save();
            return response()->json(['element' => $vehicle], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function deleteJourneyService($id){
        try{
            $journey = Journey::find($id);
            if(!$journey || $journey->archive)
                return response()->json(['message' => 'Element doesn\'t exist'], 404);
            $journey->archive = true;

            $steps = Step::where('id_journey', $id)->where('archive', false)->get();
            if(!$steps->isEmpty()){
                foreach($steps as $step){
                    $service = new DeleteService();
                    $service->deleteService($step->id, 'App\Models\Step');
                }
            }
            $journey->save();
            return response()->json(['element' => $journey], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function deleteService($id, $element){
        try{
            $toDelete = $element::find($id);
            if(!$toDelete || $toDelete->archive)
                return response()->json(['message' => 'Element doesn\'t exist'], 404);
            $toDelete->archive = true;
            $toDelete->save();

            return response()->json(['element' => $toDelete], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}

