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
            $vehicle = Vehicle::findOrFail($id);
            if($vehicle->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);
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
            return response()->json(['vehicle' => $vehicle], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function deleteJourneyService($id){
        try{
            $journey = Journey::findOrFail($id);
            if($journey->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);
            $journey->archive = true;

            $steps = Step::where('id_journey', $id)->where('archive', false)->get();
            if(!$steps->isEmpty()){
                foreach($steps as $step){
                    $service = new DeleteService();
                    $service->deleteService($step->id, 'App\Models\Step');
                }
            }
            $journey->save();
            return response()->json(['journey' => $journey], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function deleteService($id, $element){
        try{
            $toDelete = $element::findOrFail($id);
            if($toDelete->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);
            $toDelete->archive = true;
            $toDelete->save();

            return response()->json(['element' => $toDelete], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}

