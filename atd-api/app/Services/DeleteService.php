<?php
namespace App\Services;

use App\Models\Demand;
use Illuminate\Validation\ValidationException;

class DeleteService
{
    public function deleteDemandService($id){
        try{
            $demand = Demand::find($id);
            if(!$demand || $demand->archive)
                return response()->json(['message' => 'Element doesn\'t exist'], 404);
            $demand->archive = true;
            $demand->save();
            return response()->json(['message' => 'Deleted successfully.'], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}
