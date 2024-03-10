<?php

namespace App\Http\Controllers;

use App\Models\Make;
use App\Models\Piece;
use App\Models\Product;
use App\Services\DeleteService;
use Illuminate\Contracts\Support\Responsable;
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

    public function getProduct($id)
    {
        return Product::find($id) ? Product::select('id', 'name', 'measure', 'archive')->where('id', $id)->get() : response()->json(['message' => 'Element doesn\'t exist'], 404);
    }

    public function deleteProduct($id){
        try{
            $product = Product::findOrFail($id);
            $service = new DeleteService();
            if($product->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);
            $product->archive = true;

            $pieces = Piece::where('id_product', $id)->where('archive', false)->get();
            if(!$pieces->isEmpty()){
                foreach($pieces as $piece)
                    $service->deleteService($piece->id, 'App\Models\Piece');
            }

            $makes = Make::where('id_product', $id)->where('archive', false)->get();
            if(!$makes->isEmpty()) {
                foreach ($makes as $make)
                    Make::where('id_product', $make->id_product)->update(['archive' => true]);
            }
            $product->save();
            return response()->json(['product' => $product], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateProduct($id, Request $request){
        try{
            $product = Product::findOrFail($id);
            try{
                $requestData = $request->validate([
                    'name' => 'string|max:255',
                    'measure' => 'string',
                    'archive' => 'boolean'
                ]);
            }catch(ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }

            if(isset($requestData['name'])) {
                $exist = Product::where('name', ucfirst(strtolower($requestData['name'])))->first();
                if ($exist)
                    return response()->json(['message' => 'This product already exist !'], 409);
            }

            foreach($requestData as $key => $value){
                if(in_array($key, $product->getFillable()))
                    if($key == 'name')
                        $product->$key = ucfirst(strtolower($value));
                    else if($key == 'measure')
                        $product->$key = strtolower($value);
                    else
                        $product->$key = $value;
            }
            $product->save();

            return response()->json(['product' => $product], 200);
        }catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}
