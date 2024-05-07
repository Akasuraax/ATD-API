<?php

namespace App\Http\Controllers;

use App\Models\Demand;
use App\Models\Type;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DeleteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DemandController extends Controller
{
    public function createDemand(Request $request){
        try{
            $validateData = $request->validate([
                'demand.description' => 'required|string',
                'demand.id_type' => 'required|int'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }


        $type = Type::findOrFail($validateData['demand']['id_type']);
        $user_id = TokenController::decodeToken($request->header('Authorization'))->id;

        $user_status = User::select('status')->where('id', $user_id)->first();

        if($user_status['status'] == 0)
            return response()->json(['message' => 'You can\'t perform this action yet.'], 401);

        if($type->archive)
            return response()->json(['message' => 'The type of activity doesn\'t exist.'], 405);

        $demand = Demand::create([
            'description' => $validateData['demand']['description'],
            'id_user' => $user_id,
            'id_type' => $validateData['demand']['id_type']
        ]);

        $response = [
            'demand' => $demand
        ];

        return response()->json($response, 201);
    }

    public function getDemand($id){
        return Demand::find($id) ? Demand::select('*')->with('type:id,name')->with('user:id,email')->where('id', $id)->get()  : response()->json(['message' => 'Element doesn\'t exist'], 404);
    }

    public function getDemands(Request $request){
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 0);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "demands." . $field;

        $demand =  Demand::select('*')
            ->with('type:id,name')
            ->with('user:id,email')
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
        try{
            $demand = Demand::findOrFail($id);
            if($demand->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);

            $demand->archive();
            $demand = Demand::select('*')->with('type:id,name')->with('user:id,email')->where('id', $id)->first();

            return response()->json(['demand' => $demand], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateDemand($id, Request $request){
        try{
            $demand = Demand::findOrFail($id);
            try{
                $requestData = $request->validate([
                    'description' => 'required|string',
                    'user.id' => 'required|int',
                    'type.id' => 'required|int',
                    'archive' => 'required|boolean'
                ]);
            }catch(ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }

            try{
                $user = User::where('id', $requestData['user']['id'])->where('archive', false)->firstOrFail();
                $type = Type::where('id', $requestData['type']['id'])->where('archive', false)->firstOrFail();
                $demand->update($requestData);
                $demand->user()->associate($user->id);
                $demand->type()->associate($type->id);
                $demand->load('type:id,name');
            }catch (ModelNotFoundException $e) {
                return response()->json(['error' => 'The demand you selected is not found'], 404);
            }

            $demand->save();
            return response()->json(['demand' => $demand], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}
