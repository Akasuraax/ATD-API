<?php

namespace App\Http\Controllers;

use App\Models\Piece;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\DeleteService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PieceController extends Controller
{
    public function createPiece(Request $request)
    {
        try{
            $validateData = $request->validate([
                'expired_date' => 'required|date_format:Y-m-d H:i|after:today',
                'count' => 'required|numeric',
                'measure' => 'string',
                'id_warehouse' => 'required|int',
                'id_product' => 'required|int'
            ]);

        }catch (ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $warehouse = Warehouse::findOrFail($validateData['id_warehouse']);
        $product = Product::findOrFail($validateData['id_product']);

        if($warehouse->archive)
            return response()->json(['message' => 'The warehouse you selected is archived.'], 404);

        if($product->archive)
            return response()->json(['message' => 'The product you selected is archived.'], 404);

        $piece = Piece::create([
            'expired_date' => $validateData['expired_date'],
            'count' => $validateData['count'],
            'id_warehouse' => $validateData['id_warehouse'],
            'id_product' => $validateData['id_product']
        ]);

        if (isset($validateData['measure'])) {
            $piece['measure'] = $validateData['measure'];
        }

        return Response(['piece' => $piece], 201);
    }

    public function getPieces(Request $request)
    {
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 1);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "pieces." . $field;

        $pieces = Piece::select('pieces.id', 'pieces.expired_date', 'pieces.count', 'pieces.measure', 'products.name as product_name', 'warehouses.name as warehouse_name', 'pieces.archive')
            ->join('products','products.id', '=', 'pieces.id_product')
            ->join('warehouses', 'warehouses.id', '=', 'pieces.id_warehouse')
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

        return response()->json($pieces);
    }

    public function getPiece($id)
    {
        return Piece::find($id) ? Piece::select('pieces.id', 'pieces.expired_date', 'pieces.count', 'pieces.measure', 'products.name as product_name', 'warehouses.name as warehouse_name', 'pieces.archive')->join('products','products.id', '=', 'pieces.id_product')->join('warehouses', 'warehouses.id', '=', 'pieces.id_warehouse')->where('pieces.id', $id)->get() : response()->json(['message' => 'Element doesn\'t exist'], 404);
    }

    public function deletePiece($id)
    {
        $service = new DeleteService();
        return $service->deleteService($id, 'App\Models\Piece');
    }

    public function updatePiece($id, Request $request)
    {
        try{
            $piece = Piece::findOrFail($id);
            try{
                $requestData = $request->validate([
                    'expired_date' => 'date_format:Y-m-d H:i|date|after:today',
                    'count' => 'numeric',
                    'measure' => 'string',
                    'id_warehouse' => 'int',
                    'id_product' => 'int',
                    'archive' => 'boolean'
                ]);
            }catch(ValidationException $e) {
                return response()->json(['errors' => $e->errors()], 422);
            }

            foreach($requestData as $key => $value){
                if(in_array($key, $piece->getFillable())) {
                    if($key == 'id_warehouse'){
                        if(!Warehouse::find($value) || Warehouse::find($value)->archive)
                            return response()->json(['message' => 'The warehouse you put doesn\'t exist'], 404);
                    }else if($key == 'id_product'){
                        if(!Warehouse::find($value) || Product::find($value)->archive)
                            return response()->json(['message' => 'The product you put doesn\'t exist'], 404);
                    }else {
                        $piece->$key = $value;
                    }
                }
            }
            $piece->save();

            return response()->json(['piece' => $piece], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }


}
