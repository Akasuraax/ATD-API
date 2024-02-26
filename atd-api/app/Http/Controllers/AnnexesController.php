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

    public function getAnnexes(Request $request){
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 1);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "annexes." . $field;

        $users = Annexe::select('id', 'name', 'address', 'zipcode', 'archive')
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

        return response()->json($users);
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
