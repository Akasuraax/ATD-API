<?php

namespace App\Http\Controllers;

use App\Models\Demand;
use App\Models\Type;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DeleteService;
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

        if(!User::find($validateData['id_user']) || User::find($validateData['id_user'])->archive)
            return response()->json(['message' => 'The user you put doesn\'t exist'], 404);

        if(!Type::find($validateData['id_type']) || Type::find($validateData['id_type'])->archive)
            return response()->json(['message' => 'The type you put doesn\'t exist'], 404);

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
        return Demand::find($id) ? Demand::select('demands.id', 'demands.description', 'demands.status', 'users.name as user_name', 'users.forname as user_forname', 'users.email as user_email', 'types.name as type_name', 'demands.archive')->join('users', 'users.id', '=', 'demands.id_user')->join('types', 'types.id', '=', 'demands.id_type')->where('demands.id', $id)->get() : response()->json(['message' => 'Element doesn\'t exist'], 404);
    }

    public function getDemands(Request $request){
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 1);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "demands." . $field;

        $demand = Demand::select('demands.id', 'demands.description', 'demands.status', 'users.name as user_name', 'users.forname as user_forname', 'users.email as user_email', 'types.name as type_name', 'demands.archive')
            ->join('users', 'users.id', '=', 'demands.id_user')
            ->join('types', 'types.id', '=', 'demands.id_type')
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

        return response()->json($demand);
    }

    public function deleteDemand($id){
        $service = new DeleteService();
        return $service->deleteService($id, 'App\Models\Demand');
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
            return response()->json(['demand' => $demand], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}
