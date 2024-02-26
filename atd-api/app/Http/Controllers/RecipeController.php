<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Make;
use App\Models\Recipe;
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
                'listProduct' => 'required|array'
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        foreach($validateData['listProduct'] as $id => $tab){
            if(!Product::find($id) || Product::find($id)->archive)
                return response()->json(['message' => 'The product with the id ' . $id .' doesn\'t exist'], 404);
            if(!isset($tab[0]))
                return response()->json(['message' => 'You have to put the count of your product'], 422);
            if(!is_numeric($tab[0]))
                return response()->json(['message' => 'You have to put an numeric value !'], 422);
            if(isset($tab[1]) && !is_string($tab[1]))
                return response()->json(['message' => 'You have to put an string value !'], 422);
        }

        $recipe = Recipe::create([
            'name' => $validateData['name'],
            'description' => $validateData['description'],
        ]);

        foreach($validateData['listProduct'] as $id => $tab) {
            $recipe->products()->attach($id, ['archive' => false, 'count' => $tab[0], 'measure' => $tab[1] ?? null]);
        }

        $response = [
            'recipe' => $recipe
        ];

        return Response($response, 201);
    }
    public function getRecipes(Request $request)
    {
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 1);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "recipes." . $field;

        $recipes = Recipe::select('recipes.id', 'recipes.name', 'recipes.description', 'recipes.archive')
            ->join('makes', 'makes.id_recipe', '=', 'recipes.id')
            ->join('products', 'makes.id_product', '=', 'products.id')
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

        $recipes->getCollection()->transform(function ($recipe) {
            $recipe->load(['products' => function ($query) {
                $query->where('products.archive', false);
            }]);
            $productNames = $recipe->products->pluck('name');

            return [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'description' => $recipe->description,
                'archive' => $recipe->archive,
                'product_names' => $productNames,
            ];
        });

        return response()->json($recipes);
    }


    public function getRecipe($id)
    {
        $recipe = Recipe::find($id);
        if($recipe && !$recipe->archive) {
            $recipe->load(['products' => function ($query) {
                $query->where('products.archive', false);
            }]);
            $productNames = $recipe->products->pluck('name');

            return [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'description' => $recipe->description,
                'archive' => $recipe->archive,
                'product_names' => $productNames,
            ];
        }else{
            return Response(['message'=>'Element doesn\'t exist'], 404);
        }
    }

    public function deleteRecipe($id)
    {
        try{
            $recipe = Recipe::find($id);
            if(!$recipe || $recipe->archive)
                return response()->json(['message' => 'Element doesn\'t exist'], 404);
            $recipe->archive = true;
            $makes = Make::where('id_recipe', $id)->get();

            if(!$makes->isEmpty()){
                foreach ($makes as $make)
                    Make::where('id_recipe', $make->id_recipe)->update(['archive' => true]);
            }
            $recipe->save();
            return response()->json(['message' => 'Deleted successfully, everything linked to the recipe was also deleted.'], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function deleteRecipeProduct($id, Request $request)
    {
        $recipe = Recipe::find($id);

        if (!$recipe || $recipe->archive) {
            return response()->json(['message' => 'Recipe not found or archived'], 404);
        }

        try {
            $validatedData = $request->validate([
                'listProducts' => 'array|required'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $makes = Make::where('id_recipe', $id)->whereIn('id_product', $validatedData['listProducts'])->get();

        if ($makes->isEmpty()) {
            return response()->json(['message' => 'Products not found or archived'], 404);
        }

        foreach ($makes as $make) {
            $recipe->products()->detach($make->id_product);
        }

        return response()->json(['element' => $recipe], 200);
    }

    public function updateRecipe($id, Request $request)
    {
        $recipe = Recipe::find($id);

        if ($recipe && !$recipe->archive) {
            try {
                $requestData = $request->validate([
                    'name' => 'string|max:255',
                    'description' => 'string',
                    'listProduct' => 'array'
                ]);
            } catch (ValidationException $e) {
                return response()->json(['errors' => $e->errors()], 422);
            }

            foreach ($requestData as $key => $value) {
                if (in_array($key, $recipe->getFillable())) {
                    $recipe->$key = $value;
                }
            }

            if (isset($requestData['listProduct'])) {
                foreach ($requestData['listProduct'] as $productId => $tab) {
                    if (!Product::find($productId) || Product::find($productId)->archive)
                        return response()->json(['message' => 'The product with the id ' . $productId . ' doesn\'t exist'], 404);
                    if(!isset($tab[0]))
                        return response()->json(['message' => 'You have to put the count of your product'], 422);
                    if(!is_numeric($tab[0]))
                        return response()->json(['message' => 'You have to put an numeric value !'], 422);
                    if(isset($tab[1]) && !is_string($tab[1]))
                        return response()->json(['message' => 'You have to put an string value !'], 422);


                    $existingMake = Make::where('id_product', $productId)->where('id_recipe', $id)->first();
                    if ($existingMake) Make::where('id_recipe', $id)->where('id_product', $productId)->update(['count' => $tab[0], 'measure' => $tab[1] ?? null]);
                    else $recipe->products()->attach($productId, ['archive' => false, 'count' => $tab[0], 'measure' => $tab[1] ?? null]);
                }
            }

            $recipe->save();
            $response = [
                'recipe' => $recipe
            ];

            $status = 200;
        } else {
            $response = [
                'message' => 'Your element doesn\'t exist'
            ];
            $status = 404;
        }

        return Response($response, $status);
    }
}
