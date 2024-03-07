<?php

namespace App\Http\Controllers;

use App\Models\Piece;
use App\Models\Warehouse;
use App\Services\DeleteService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;


class WarehouseController extends Controller
{
    public function createWarehouse(Request $request){

        try {
            $validateData = $request->validate([
                'name' => 'required|string|max:255',
                'address' => 'required|string',
                'zipcode' => 'required|digits:5|integer',
                'capacity' => 'required|integer'
            ]);
        }catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $exist = Warehouse::where('address', $validateData['address'])->where('zipcode', $validateData['zipcode'])->first();
        if($exist)
            return response()->json(['message' => 'This annexe with this address already exist !'], 409);

        $warehouse = Warehouse::create([
            'name' => $validateData['name'],
            'address' => $validateData['address'],
            'zipcode' => $validateData['zipcode'],
            'capacity' => $validateData['capacity'],
        ]);

        return Response(['warehouse' => $warehouse], 201);
    }

    public function getWarehouses(Request $request){
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 0);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "warehouses." . $field;

        $warehouse = Warehouse::select('id', 'name', 'address', 'zipcode', 'capacity', 'archive')
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

        return response()->json($warehouse);
    }

    public function getWarehouse($id){
        return Warehouse::find($id) ?  Warehouse::select('id', 'name', 'address', 'zipcode', 'capacity', 'archive', 'created_at')->where('id', $id)->first() : response()->json(['message' => 'Element doesn\'t exist'], 404);
    }

    public function deleteWarehouse($id){
        try{
            $warehouse = Warehouse::findOrFail($id);
            if($warehouse->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);
            $warehouse->archive = true;

            $pieces = Piece::where('id_warehouse', $id)->where('archive', false)->get();
            if(!$pieces->isEmpty()){
                foreach($pieces as $piece) {
                    $service = new DeleteService();
                    $service->deleteService($piece->id, 'App\Models\Piece');
                }
            }
            $warehouse->save();
            return response()->json(['warehouse' => $warehouse], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateWarehouse($id, Request $request){
        try{
            $warehouse = Warehouse::findOrFail($id);
            try{
                $requestData = $request->validate([
                    'name' => 'string|max:255',
                    'address' => 'string',
                    'zipcode' => 'digits:5|integer',
                    'capacity' => 'integer',
                    'archive' => 'boolean'
                ]);
            }catch(ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }

            if(isset($requestData['address'])){
                if(isset($requestData['zipcode']))
                    $exist = Warehouse::where('address', $requestData['address'])->where('zipcode', $requestData['zipcode'])->first();
                else
                    $exist = Warehouse::where('address', $requestData['address'])->where('zipcode', $warehouse->zipcode)->first();
                if ($exist)
                    return response()->json(['message' => 'This warehouse with this address already exist !'], 409);
            }else if(isset($requestData['zipcode'])) {
                $exist = Warehouse::where('address', $warehouse->address)->where('zipcode', $requestData['zipcode'])->first();
                if ($exist)
                    return response()->json(['message' => 'This warehouse with this address already exist !'], 409);
            }

            foreach($requestData as $key => $value){
                if(in_array($key, $warehouse->getFillable()))
                    $warehouse->$key = $value;
            }
            $warehouse->save();

            return response()->json(['warehouse' => $warehouse], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}
