<?php

namespace App\Http\Controllers;

use App\Models\Make;
use App\Models\Piece;
use App\Models\Product;
use App\Services\DeleteService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function createProduct(Request $request){
        try{
            $validateData = $request->validate([
                'name' => 'required|string|max:255',
                'measure' => 'nullable|string'
            ]);
        }catch (ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $exist = Product::where('name', ucfirst(strtolower($validateData['name'])))->first();
        if($exist)
            return response()->json(['message' => 'This product already exist !'], 409);

        $product = Product::create([
            'name' => ucfirst(strtolower($validateData['name'])),
            'measure' => isset($validateData['measure']) ? strtolower($validateData['measure']) : null
        ]);

        return Response(['product' => $product], 201);
    }

    public function getProducts(Request $request){
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 1);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "products." . $field;

        $product = Product::select('id','name', 'measure', 'archive')
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

        return response()->json($product);
    }

    public function getProductsFilter(Request $request){

        $filter = $request->input('filter', '%');
        $product = Product::select('id','name', 'measure', 'archive')
            ->where('name', 'ilike', '%' . strtolower($filter) . '%')
            ->take(10)
            ->get();

        return response()->json($product);
    }

    public function getProduct($id)
    {
        return Product::find($id) ? Product::select('id', 'name', 'measure', 'archive')->where('id', $id)->first() : response()->json(['message' => 'Element doesn\'t exist'], 404);
    }

    public function getNbProductProduct($id)
    {
        $product = Product::where('id', $id)
            ->with('pieces')
            ->where('archive', false)
            ->first();
        
        if (!$product) {
            return 0;
        }

        if ($product->pieces->isNotEmpty()) {
            $totalPiecesCount = $product->pieces->sum('count');
        } else {
            return 0;
        }


        return $totalPiecesCount;
    }
    public function deleteProduct($id){
        try{
            $product = Product::findOrFail($id);
            if($product->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);

            $product->archive();
            $product = Product::findOrFail($id);
            return response()->json(['product' => $product,  'message' => "Deleted !"], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateProduct($id, Request $request){
        try{
            $product = Product::findOrFail($id);
            try{
                $requestData = $request->validate([
                    'name' => 'required|string|max:255',
                    'measure' => 'required|string',
                    'archive' => 'required|boolean'
                ]);
            }catch(ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }

            $exist = Product::where('name', ucfirst(strtolower($requestData['name'])))->whereNotIn('id', [$id])->first();
            if ($exist)
                return response()->json(['message' => 'This product already exist !'], 409);

            $requestData['name'] = ucfirst(strtolower($requestData['name']));
            $requestData['measure'] = strtolower($requestData['measure']);

            try{
                $product->update($requestData);
                $product->save();
            }catch (ModelNotFoundException $e) {
                return response()->json(['error' => 'The element you selected is not found'], 404);
            }

            return response()->json(['product' => $product], 200);
        }catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}
