<?php

namespace App\Http\Controllers;

use App\Models\Piece;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\DeleteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;

class PieceController extends Controller
{
    public function createPiece(Request $request)
    {
        try{
            $validateData = $request->validate([
                'expired_date' => 'nullable|date|after:today',
                'count' => 'required|numeric',
                'location' => 'nullable|int',
                'warehouse.id' => 'required|int',
                'product.id' => 'required|int'
            ]);

        }catch (ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $warehouse = Warehouse::findOrFail($validateData['warehouse']['id']);
        $product = Product::findOrFail($validateData['product']['id']);

        if($validateData['count'] < "1")
            return response()->json(['message' => 'Count must be greater than 0'], 422);

        if($warehouse->archive)
            return response()->json(['message' => 'The warehouse you selected is archived.'], 404);

        if($product->archive)
            return response()->json(['message' => 'The product you selected is archived.'], 404);

        $warehousePiecesCount = $this->getPieceStockCount($warehouse->id);
        if($warehousePiecesCount + $validateData['count'] > $warehouse->capacity)
            return response()->json(['message' => 'You reached the warehouse maximum capacity'], 401);

        $piece = Piece::create([
            'expired_date' => $validateData['expired_date'] ?? null,
            'count' => $validateData['count'],
            'location' => $validateData['location'] ?? null,
            'id_warehouse' => $validateData['warehouse']['id'],
            'id_product' => $validateData['product']['id']
        ]);

        return Response(['piece' => $piece], 201);
    }

    public function getPieces(Request $request)
    {
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 0);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "pieces." . $field;

        $pieces = Piece::select('*')
            ->with('product:id,name,measure')
            ->with('warehouse:id,name')
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
        return Piece::find($id) ? Piece::select('*')->with('product:id,name,measure')->with('warehouse:id,name')->where('pieces.id', $id)->get() : response()->json(['message' => 'Element doesn\'t exist'], 404);
    }

    public function deletePiece($id)
    {
            try {
                $piece = Piece::findOrFail($id);
                if ($piece->archive)
                    return response()->json(['message' => 'Element is already archived.'], 405);

                $piece->archive();
                $piece = Piece::select('*')->with('product:id,name,measure')->with('warehouse:id,name')->where('pieces.id', $id)->first();
                return response()->json([$piece, 'message' => "Deleted !"], 200);
            } catch (ValidationException $e) {
                return response()->json(['message' => $e->getMessage()], $e->getCode());
            }
    }

    public function updatePiece($id, Request $request)
    {
        try{
            $piece = Piece::findOrFail($id);
            try{
                $requestData = $request->validate([
                    'expired_date' => 'nullable|date|after:today',
                    'count' => 'required|numeric',
                    'location' => 'nullable|int',
                    'warehouse.id' => 'required|int',
                    'product.id' => 'required|int',
                    'archive' => 'required|boolean'
                ]);
            }catch(ValidationException $e) {
                return response()->json(['errors' => $e->errors()], 422);
            }

            try{
                $warehouse = Warehouse::where('id', $requestData['warehouse']['id'])->where('archive', false)->firstOrFail();
                $product = Product::where('id', $requestData['product']['id'])->where('archive', false)->firstOrFail();
                $piece->update($requestData);
                $piece->warehouse()->associate($warehouse->id);
                $piece->product()->associate($product->id);
                $piece->load('warehouse:id,name');
                $piece->load('product:id,name,measure');
                $piece->save();
            }catch (ModelNotFoundException $e) {
                return response()->json(['error' => 'The element you selected is not found'], 404);
            }

            return response()->json(['piece' => $piece], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function getPieceStockCount($warehouseId){
        return Piece::where('id_warehouse', $warehouseId)->where('archive', false)->sum('count');
    }

    /*
    public function archivePassed(){
        $pieces = Piece::where('expired_date', '<', Carbon::today())->where('archive', false)->get();

        foreach($pieces as $piece)
            $piece->archive();
    }
    */

}
