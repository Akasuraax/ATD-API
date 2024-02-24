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
            if(!is_int($tab[0]))
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

    public function getRecipes()
    {
        $recipes = Recipe::where('archive', false)->get();

        $recipesWithProductNames = $recipes->map(function ($recipe) {
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

        return $recipesWithProductNames;
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
            return Response(['message'=>'The recipe doesn\'t exist'], 404);
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

        try {
            $validateData = $request->validate([
                'listProducts' => 'array|required'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        if ($recipe && !$recipe->archive) {
            $makes = Make::where('id_recipe', $id)->get();
            if (!$makes->isEmpty()) {
                foreach ($makes as $make) {
                    foreach ($validateData['listProducts'] as $productId) {
                        if ($make->id_product == $productId && $make->id_recipe == $id) {
                            $recipe->products()->detach($productId);
                        }
                    }
                }
                $response = ['message' => 'Deleted successfully'];
                $status = 200;
            }else {
                $response = ['message' => 'Product not found or archived'];
                $status = 404;
            }
        } else {
            $response = ['message' => 'Product not found or archived'];
            $status = 404;
        }

        return response()->json($response, $status);
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
                foreach ($requestData['listProduct'] as $productId => $count) {
                    if (!Product::find($productId) || Product::find($productId)->archive) {
                        return response()->json(['message' => 'The product with the id ' . $productId . ' doesn\'t exist'], 404);
                    }
                    $existingMake = Make::where('id_product', $productId)->where('id_recipe', $id)->first();
                    if ($existingMake) Make::where('id_recipe', $id)->where('id_product', $productId)->update(['count' => $count]);
                    else $recipe->products()->attach($productId, ['archive' => false, 'count' => $count]);
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
