<?php

namespace App\Http\Controllers;

use App\Models\Piece;
use App\Models\Warehouse;
use App\Services\DeleteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Product;

class WarehouseController extends Controller
{
    public function createWarehouse(Request $request){

        try {
            $validateData = $request->validate([
                'name' => 'required|string|max:255',
                'address' => 'required|string',
                'zipcode' => 'required',
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
            'zipcode' => intval($validateData['zipcode']),
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

    public function getAllWarehouses(): \Illuminate\Http\JsonResponse
    {
        $warehouse = Warehouse::select('id', 'name', 'address', 'zipcode')
            ->where('archive', false)
            ->get();

        return response()->json($warehouse);

    }
    public function getWarehouse($id){
        return Warehouse::find($id) ?  Warehouse::select('id', 'name', 'address', 'zipcode', 'capacity', 'archive', 'created_at')->where('id', $id)->first() : response()->json(['message' => 'Element doesn\'t exist'], 404);
    }

    public function getWarehousesStock($id){
        $product = Product::findOrFail($id);
        $piecesData = [];

        $pieces = $product->pieces()->get();
        foreach ($pieces as $piece) {
            $piecesData[] = [
                'id' => $piece->id_warehouse,
                'name' => $piece->warehouse->name,
                'count' => $piece->count,
                'measure' => $piece->measure,
                'expired_date' => $piece->expired_date
            ];
        }
        return response()->json($piecesData);
    }
    public function deleteWarehouse($id){
        try{
            $warehouse = Warehouse::findOrFail($id);
            if($warehouse->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);

            $warehouse->archive();
            $warehouse = Warehouse::findOrFail($id);

            return response()->json(['warehouse' => $warehouse,  'message' => "Deleted !"], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateWarehouse($id, Request $request){
        try{
            $warehouse = Warehouse::findOrFail($id);
            try{
                $requestData = $request->validate([
                    'name' => 'required|string|max:255',
                    'address' => 'required|string',
                    'zipcode' => 'required|digits:5|integer',
                    'capacity' => 'required|integer',
                    'archive' => 'required|boolean'
                ]);
            }catch(ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }

            $exist = Warehouse::where('address', $requestData['address'])->where('zipcode', $requestData['zipcode'])->whereNotIn('id', [$id])->first();
            if ($exist)
                return response()->json(['message' => 'This warehouse with this address already exist !'], 409);

            try{
                $warehouse->update($requestData);
                $warehouse->save();
            }catch (ModelNotFoundException $e) {
                return response()->json(['error' => 'The element you selected is not found'], 404);
            }
            return response()->json(['warehouse' => $warehouse], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}
