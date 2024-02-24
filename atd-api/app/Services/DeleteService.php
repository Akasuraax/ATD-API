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
            return response()->json(['message' => 'Deleted successfully, everything linked to the vehicle was also deleted.'], 200);
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
                    $service->deleteStepService($step->id);
                }
            }
            $journey->save();
            return response()->json(['message' => 'Deleted successfully, everything linked to the journey was also deleted.'], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function deleteStepService($id){
        try {
            $step = Step::find($id);
            if(!$step || $step->archive)
                return response()->json(['message' => 'Element doesn\'t exist'], 404);
            $step->archive = true;
            $step->save();

            return response()->json(['message' => 'Deleted successfully'], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function deletePieceService($id){
        try{
            $piece = Piece::find($id);
            if(!$piece || $piece->archive)
                return response()->json(['message' => 'Element doesn\'t exist'], 404);
            $piece->archive = true;
            $piece->save();

            return response()->json(['message' => 'Deleted successfully'], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function deleteDemandService($id)
    {
        try {
            $demand = Demand::find($id);
            if (!$demand || $demand->archive)
                return response()->json(['message' => 'Element doesn\'t exist'], 404);
            $demand->archive = true;
            $demand->save();
            return response()->json(['message' => 'Deleted successfully.'], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}

