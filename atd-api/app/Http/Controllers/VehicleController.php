<?php

namespace App\Http\Controllers;

use App\Models\Annexe;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VehicleController extends Controller
{
    public function createVehicle(Request $request)
    {
        try {
            $validateData = $request->validate([
                'name' => 'string|required|max:255',
                'license_plate' => 'string|required|max:9',
                'average_consumption' => 'required',
                'fuel_type' => 'string|required',
                'id_annexe' => 'required|int'
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        if(!Annexe::find($validateData['id_annexe']) || Annexe::find($validateData['id_annexe'])->archive)
            return Response(['message'=>'The annex you selected doesn\'t exist!'], 404);

        $vehicle = Vehicle::create([
            'name' => $validateData['name'],
            'license_plate' => $validateData['license_plate'],
            'average_consumption' => $validateData['average_consumption'],
            'fuel_type' => $validateData['fuel_type'],
            'id_annexe' => $validateData['id_annexe']
        ]);

        $response = [
            'vehicle' => $vehicle
        ];

        return Response($response, 201);
    }


    public function getVehicles(){
        return Vehicle::select('name', 'license_plate', 'average_consumption', 'fuel_type', 'id_annexe', 'archive')->where('archive', false)->get();
    }

    public function deleteVehicle($id){
        $vehicle = Vehicle::find($id);

        if($vehicle && !$vehicle->archive){
            $vehicle->archive = true;
            $vehicle->save();
            $response = ['message'=>'Deleted !'];
            $status = 200;
        }else{
            $response = ['message'=>'Your element doesn\'t exists'];
            $status = 404;
        }

        return Response($response, $status);
    }

    public function updateVehicle($id, Request $request){
        $vehicle = Vehicle::find($id);

        if($vehicle && !$vehicle->archive){
            $requestData = $request->all();
            foreach($requestData as $key => $value){
                if(in_array($key, $vehicle->getFillable())){
                    $vehicle->$key = $value;
                }
            }
            if(!Annexe::find($vehicle->id_annexe) || Annexe::find($vehicle->id_annexe)->archive)
                return Response(['message'=>'The annex you selected doesn\'t exist!'], 404);

            $vehicle->save();
            $response = [
                'type' => $vehicle
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
