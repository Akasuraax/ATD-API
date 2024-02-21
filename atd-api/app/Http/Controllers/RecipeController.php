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

        foreach($validateData['listProduct'] as $id => $count){
            if(!Product::find($id) || Product::find($id)->archive)
                return response()->json(['message' => 'The product with the id ' . $id .' doesn\'t exist'], 404);
        }

        $recipe = Recipe::create([
            'name' => $validateData['name'],
            'description' => $validateData['description'],
        ]);

        foreach($validateData['listProduct'] as $id => $count)
            $recipe->products()->attach($id, ['archive' => false, 'count' => $count]);

        $response = [
            'recipe' => $recipe
        ];

        return Response($response, 201);
    }

    public function getRecipes()
    {
        $recipes = Recipe::where('archive', false)->get();

        $recipesWithProductNames = $recipes->map(function ($recipe) {
            $recipe->load('products');
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
        if(!$recipe->archive) {
            $recipe->load('products');
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
        $recipe = Recipe::find($id);

        if($recipe && !$recipe->archive){
            $recipe->archive = true;
            $recipe->save();

            $makes = Make::where('id_recipe', $id)->get();
            $response = ['message'=>'Deleted !'];

            if(!$makes->isEmpty()){
                foreach ($makes as $make)
                    Make::where('id_recipe', $make->id_recipe)->update(['archive' => true]);
            }
            $status = 200;
        }else{
            $response = ['message'=>'Your element doesn\'t exists'];
            $status = 404;
        }
        return Response($response, $status);
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
