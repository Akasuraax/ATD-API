<?php

namespace App\Http\Controllers;

use App\Models\Annexe;
use App\Models\Vehicle;
use App\Services\DeleteService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AnnexesController extends Controller
{
    public function createAnnexe(Request $request){
        try{
            $validateData = $request->validate([
                'name' => 'required|string|max:255',
                'address' => 'required|string',
                'zipcode' => 'required|digits:5|integer'
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $annexe = Annexe::create([
            'name' => $validateData['name'],
            'address' => $validateData['address'],
            'zipcode' => $validateData['zipcode']
        ]);

        $response = [
            'annexe' => $annexe
        ];

        return Response($response, 201);
    }

    public function getAnnexes(){
        return Annexe::select('id', 'name', 'address', 'zipcode', 'archive')->where('archive', false)->get();
    }

    public function deleteAnnexe($id){
        try{
            $annexe = Annexe::find($id);
            if(!$annexe || $annexe->archive)
                return response()->json(['message' => 'Element doesn\'t exist'], 404);
            $annexe->archive = true;

            $vehicles = Vehicle::where('id_annexe', $annexe->id)->where('archive', false)->get();
            if(!$vehicles->isEmpty()) {
                foreach ($vehicles as $vehicle) {
                    $service = new DeleteService();
                    $service->deleteVehicleService($vehicle->id);
                }
            }
            $annexe->save();
            return response()->json(['element' => $annexe], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateAnnexe($id, Request $request){
        $annexe = Annexe::find($id);

        if($annexe && !$annexe->archive){
            try{
                $requestData = $request->validate([
                    'name' => 'string|max:255',
                    'address' => 'string',
                    'zipcode' => 'digits:5|integer'
                ]);
            }catch(ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }
            foreach($requestData as $key => $value){
                if(in_array($key, $annexe->getFillable()))
                    $annexe->$key = $value;
            }
            $annexe->save();
            $response = [
                'type' => $annexe
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
