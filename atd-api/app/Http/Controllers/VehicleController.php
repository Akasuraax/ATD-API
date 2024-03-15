<?php

namespace App\Http\Controllers;

use App\Models\Annexe;
use App\Services\DeleteService;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        $exist = Vehicle::where('license_plate', strtoupper($validateData['license_plate']))->first();
        if($exist)
            return response()->json(['message' => 'This product already exist !'], 409);

        $annexe = Annexe::findOrFail($validateData['id_annexe']);
        if($annexe->archive)
            return response()->json(['message' => 'The annexe you selected is archived.'], 405);

        $vehicle = Vehicle::create([
            'name' => $validateData['name'],
            'license_plate' => strtoupper($validateData['license_plate']),
            'average_consumption' => $validateData['average_consumption'],
            'fuel_type' => $validateData['fuel_type'],
            'id_annexe' => $validateData['id_annexe']
        ]);

        return Response(['vehicle' => $vehicle], 201);
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
        $vehicle = Vehicle::where('id', $id)
            ->with('annexe')
            ->first();
        if ($vehicle) {
            return $vehicle;
        } else {
            return response()->json(['message' => 'Element doesn\'t exist'], 404);
        }
    }

    public function deleteVehicle($id){
        $vehicle = Vehicle::find($id);

        if ($vehicle) {
            $vehicle->archive();
            $deletedVehicle = Vehicle::with('annexe')->find($id);
            $respons = [
                'vehicle' => $deletedVehicle,
                'message' => "Deleted !"
            ];
            return response()->json($respons, 200);
        } else {
            return response()->json(['message' => 'Element doesn\'t exist'], 404);
        }
    }

    public function updateVehicle($id, Request $request){
        try{
            $vehicle = Vehicle::findOrFail($id);
            try{
                $requestData = $request->validate([
                    'name' => 'required','string|max:255',
                    'license_plate' => 'required',
                    'string',
                    'email',
                    'max:9',
                     Rule::unique('users')->ignore($id),
                    'average_consumption' => 'required','numeric',
                    'fuel_type' => 'required','string',
                    'annexe.id' => 'required','int',
                    'archive' => 'required','boolean'
                ]);
            }catch(ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }

            try {
                $annexe = Annexe::where('id', $requestData['annexe']['id'])->where('archive', false)->firstOrFail();
                $vehicle->update($requestData);
                $vehicle->annexe()->associate($annexe->id);
                $vehicle->save();
                $vehicle->load('annexe:id,name');
            } catch (ModelNotFoundException $e) {
                return response()->json(['error' => 'The vehicle you selected is not found'], 404);
            }

            return response()->json(['vehicle' => $vehicle], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}
