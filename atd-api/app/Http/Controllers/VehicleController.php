<?php

namespace App\Http\Controllers;

use App\Models\Annexe;
use App\Services\DeleteService;
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
                'average_consumption' => 'required|numeric',
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


    public function getVehicles(Request $request){
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 1);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "vehicles." . $field;

        $vehicle = Vehicle::select('vehicles.id', 'vehicles.name', 'vehicles.license_plate', 'vehicles.average_consumption', 'vehicles.fuel_type', 'annexes.name as annexe_name','vehicles.archive')
            ->join('annexes', 'vehicles.id_annexe', '=', 'annexes.id')
            ->where(function ($query) use ($fieldFilter, $operator, $value) {
                if ($fieldFilter && $operator && $value !== '*') {
                    switch ($operator) {
                        case 'contains':
                            $query->where($fieldFilter, 'LIKE', '%' . $value . '%');
                            break;
                        case 'equals':
                            $query->where($fieldFilter, '=', $value);
                            break;
                        case 'startsWith':
                            $query->where($fieldFilter, 'LIKE', $value . '%');
                            break;
                        case 'endsWith':
                            $query->where($fieldFilter, 'LIKE', '%' . $value);
                            break;
                        case 'isEmpty':
                            $query->whereNull($fieldFilter);
                            break;
                        case 'isNotEmpty':
                            $query->whereNotNull($fieldFilter);
                            break;
                        case 'isAnyOf':
                            $values = explode(',', $value);
                            $query->whereIn($fieldFilter, $values);
                            break;
                    }
                }
            })
            ->orderBy($field, $sort)
            ->paginate($perPage, ['*'], 'page', $page + 1);

        return response()->json($vehicle);
    }

    public function getVehicle($id){
        return Vehicle::find($id) ? Vehicle::select('vehicles.id', 'vehicles.name', 'vehicles.license_plate', 'vehicles.average_consumption', 'vehicles.fuel_type', 'annexes.name as annexe_name','vehicles.archive')->join('annexes', 'vehicles.id_annexe', '=', 'annexes.id')->where('vehicles.id', $id)->get() : response()->json(['message' => 'Element doesn\'t exist'], 404);
    }

    public function deleteVehicle($id){
        $service = new DeleteService();
        return $service->deleteVehicleService($id);
    }

    public function updateVehicle($id, Request $request){
        $vehicle = Vehicle::find($id);

        if($vehicle && !$vehicle->archive){
            try{
                $requestData = $request->validate([
                'name' => 'string|max:255',
                'license_plate' => 'string|max:9',
                'average_consumption' => 'numeric',
                'fuel_type' => 'string',
                'id_annexe' => 'int'
            ]);
            }catch(ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }
            foreach($requestData as $key => $value){
                if(in_array($key, $vehicle->getFillable()))
                    $vehicle->$key = $value;
            }
            if(!Annexe::find($vehicle->id_annexe) || Annexe::find($vehicle->id_annexe)->archive)
                return Response(['message'=>'The annex you selected doesn\'t exist!'], 404);

            $vehicle->save();
            $response = [
                'vehicle' => $vehicle
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
