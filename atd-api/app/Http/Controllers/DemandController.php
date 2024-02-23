<?php

namespace App\Http\Controllers;

use App\Models\Demand;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DemandController extends Controller
{
    public function createDemand(Request $request){
        try{
            $validateData = $request->validate([
                'description' => 'required|string',
                'id_user' => 'required|int',
                'id_type' => 'required|int'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $demand = Demand::create([
            'description' => $validateData['description'],
            'id_user' => $validateData['id_user'],
            'id_type' => $validateData['id_type']
        ]);

        $response = [
            'demand' => $demand
        ];

        return response()->json($response, 201);
    }

    public function getDemand($id){
        return Demand::find($id);
    }

    public function getDemands(){
        return Demand::all();
    }

    public function deleteDemand($id){
        try{
            $demand = Demand::find($id);
            if(!$demand || $demand->archive)
                return response()->json(['message' => 'Element doesn\'t exist'], 404);
            $demand->archive = true;
            $demand->save();
            return response()->json(['message' => 'Deleted successfully, everything linked to the annexe was also deleted.'], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateDemand($id, Request $request){
        try{
            $demand = Demand::find($id);

            if(!$demand || $demand->archive)
                return response()->json(['message' => 'Element doesn\'t exist'], 404);

            try{
                $requestData = $request->validate([
                    'description' => 'string',
                    'id_user' => 'int',
                    'id_type' => 'int'
                ]);
            }catch(ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }

            foreach($requestData as $key => $value){
                if(in_array($key, $demand->getFillable()))
                    $demand->$key = $value;
            }
            $demand->save();
            return response()->json(['message' => 'Deleted successfully, everything linked to the annexe was also deleted.'], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}
