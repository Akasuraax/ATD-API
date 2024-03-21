<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Make;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RecipeController extends Controller
{
    public function createRecipe(Request $request)
    {
        try{
            $validateData = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'products' => 'required|array'
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $exist = Recipe::where('name', ucfirst(strtolower($validateData['name'])))->first();
        if($exist)
            return response()->json(['message' => 'This recipe already exist !'], 409);

        foreach($validateData['products'] as $product) {
            $id = $product['id'];
            $measure = $product['measure'];
            $count = $product['count'];
            if(!Product::find($id) || Product::find($id)->archive)
                return response()->json(['message' => 'The product with the id ' . $id .' doesn\'t exist or is archived.'], 404);
            if(!isset($count))
                return response()->json(['message' => 'You have to put the count of your product'], 422);

            if(!is_numeric($count))
                return response()->json(['message' => 'You have to put an numeric value !'], 422);

            if(isset($measure) && !is_string($measure))
                return response()->json(['message' => 'You have to put an string value !'], 422);
        }

        $recipe = Recipe::create([
            'name' => ucfirst(strtolower($validateData['name'])),
            'description' => $validateData['description'],
        ]);

        foreach ($validateData['products'] as $productData) {
            $productId = $productData['id'];
            $count = $productData['count'];
            $measure = $productData['measure'];
            $recipe->products()->attach($productId, [
                'archive' => false,
                'count' => $count,
                'measure' => $measure,
            ]);
        }

        $recipe->load('products');

        return Response(['recipe' => $recipe], 201);
    }
    public function getRecipes(Request $request)
    {
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 0);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "recipes." . $field;

        $recipes = Recipe::select('recipes.id', 'recipes.name', 'recipes.description', 'recipes.archive')
            ->with('products')
            ->where('recipes.archive', false)
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
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($recipes);
    }
    public function getRecipe($id)
    {
        $recipe = Recipe::with('products')->findOrFail($id);

        if ($recipe->archive) {
            return response()->json(['message' => 'Element is already archived.'], 405);
        }

        $formattedRecipe = [
            'id' => $recipe->id,
            'name' => $recipe->name,
            'description' => $recipe->description,
            'archive' => $recipe->archive,
            'created_at' => $recipe->created_at,
            'updated_at' => $recipe->updated_at,
            'products' => $recipe->products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'measure' => $product->measure,
                    'archive' => $product->archive,
                    'count' => $product->pivot->count,
                ];
            }),
        ];

        return response()->json($formattedRecipe);
    }
    public function deleteRecipe($id)
    {
        try{
            $recipe = Recipe::findOrFail($id);
            if($recipe->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);

            $recipe->archive();
            $recipe = Recipe::findOrFail($id);

            return response()->json(['recipe' => $recipe,  'message' => "Deleted !"], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
    public function updateRecipe($id, Request $request)
    {
        try{
            $recipe = Recipe::findOrFail($id);
            try {
                $requestData = $request->validate([
                    'name' => 'required|string|max:255',
                    'description' => 'required|string',
                    'products' => 'required|array',
                    'archive' => 'required|boolean'
                ]);
            } catch (ValidationException $e) {
                return response()->json(['errors' => $e->errors()], 422);
            }

            $exist = Recipe::where('name', ucfirst(strtolower($requestData['name'])))
                ->whereNotIn('id', [$id])
                ->where('archive', false)
                ->first();

            if ($exist)
                return response()->json(['message' => 'This recipe already exist !'], 409);

            $requestData['name'] = ucfirst(strtolower($requestData['name']));

            try {
                $recipe->update($requestData);
                $recipe->products()->detach();

                if (isset($requestData['products'])) {
                    foreach ($requestData['products'] as $product) {
                        $productId = $product['id'];
                        $measure = $product['measure'];
                        $count = $product['count'];
                        if (!Product::find($productId) || Product::find($productId)->archive)
                            return response()->json(['message' => 'The product with the id ' . $productId . ' doesn\'t exist or is archived.'], 404);
                        if (!isset($count))
                            return response()->json(['message' => 'You have to put the count of your product'], 422);
                        if (!is_numeric($count))
                            return response()->json(['message' => 'You have to put an numeric value !'], 422);
                        if (isset($measure) && !is_string($measure))
                            return response()->json(['message' => 'You have to put an string value !'], 422);
                    }
                }

                foreach ($requestData['products'] as $productData) {
                    $productId = $productData['id'];
                    $count = $productData['count'];
                    $measure = $productData['measure'];
                    $recipe->products()->attach($productId, [
                        'archive' => false,
                        'count' => $count,
                        'measure' => $measure,
                    ]);
                }

                $recipe->load('products');
            }catch (ModelNotFoundException $e) {
                return response()->json(['error' => 'The element you selected is not found'], 404);
            }

            return response()->json(['element' => $recipe], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}
