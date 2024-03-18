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
        $recipe = Recipe::findOrFail($id);
        $recipe->load('products');
            $productNames = $recipe->products->map(function ($product) {
                return [
                    'name' => $product->name,
                    'archive' => $product->archive,
                    'pivot' => [
                        'count' => $product->pivot->count,
                        'measure' => $product->pivot->measure,
                    ]
                ];
            });

            return [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'description' => $recipe->description,
                'archive' => $recipe->archive,
                'product_names' => $productNames,
            ];
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
                    'list_product' => 'required|array',
                    'archive' => 'required|boolean'
                ]);
            } catch (ValidationException $e) {
                return response()->json(['errors' => $e->errors()], 422);
            }

            $exist = Recipe::where('name', ucfirst(strtolower($requestData['name'])))->whereNotIn('id', [$id])->first();
            if ($exist)
                return response()->json(['message' => 'This recipe already exist !'], 409);

            $requestData['name'] = ucfirst(strtolower($requestData['name']));

            try {
                $recipe->update($requestData);
                $recipe->products()->detach();

                if (isset($requestData['list_product'])) {
                    foreach ($requestData['list_product'] as $productId => $tab) {
                        if (!Product::find($productId) || Product::find($productId)->archive)
                            return response()->json(['message' => 'The product with the id ' . $productId . ' doesn\'t exist or is archived.'], 404);
                        if (!isset($tab[0]))
                            return response()->json(['message' => 'You have to put the count of your product'], 422);
                        if (!is_numeric($tab[0]))
                            return response()->json(['message' => 'You have to put an numeric value !'], 422);
                        if (isset($tab[1]) && !is_string($tab[1]))
                            return response()->json(['message' => 'You have to put an string value !'], 422);


                        $existingMake = Make::where('id_product', $productId)->where('id_recipe', $id)->first();
                        $recipe->products()->attach($productId, ['archive' => false, 'count' => $tab[0], 'measure' => $tab[1] ?? null]);
                    }
                }
                $recipe->save();
                $recipe->load('products:id,name');
            }catch (ModelNotFoundException $e) {
                return response()->json(['error' => 'The element you selected is not found'], 404);
            }

            return response()->json(['element' => $recipe], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}
