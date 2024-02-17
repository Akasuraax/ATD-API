<?php

namespace App\Http\Controllers;

use App\Models\Annexe;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VehicleController extends Controller
{
    public function createVehichle(Request $request)
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

        if(!Annexe::select($validateData['id_annexe']))return Response('The annex you selected doesn\'t exist!', 404);

        $vehicle = Vehicle::create([
            'name' => $validateData['name'],
            'license_plate' => $validateData['license_plate'],
            'average_consumption' => $validateData['average_consumption'],
            'fuel_type' => $validateData['fuel_type'],
        ]);
        
    }


    public function getVehicle(Request $request){

    }

    public function deleteVehicle(Request $request){

    }

    public function updateVehicle(Request $request){

    }
}
