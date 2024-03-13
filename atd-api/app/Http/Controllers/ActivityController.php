<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\File;
use App\Models\Journey;
use App\Models\Recipe;
use App\Models\Role;
use App\Models\Product;
use App\Services\DeleteService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Type;
use function PHPUnit\Framework\isEmpty;

class ActivityController extends Controller
{
    public function createActivity(Request $request){
        try{
            $validateData = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string|max:255',
                'address' => 'nullable|string',
                'zipcode' => 'nullable|numeric:5',
                'start_date' => 'required|date|after_or_equal:today|date_format:Y-m-d H:i',
                'end_date' => 'required|date|after:start_date|date_format:Y-m-d H:i',
                'donation' => 'nullable|int',
                'id_type' => 'required|int',
                'list_products' => 'nullable|array',
                'list_recipes' => 'nullable|array',
                'role_limits' => 'required|array',
                'activity_files' => 'nullable',
                'activity_files.*' => 'mimes:pdf,jpg,png,jpeg'
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $type = Type::findOrFail($validateData['id_type']);
        if($type->archive)
            return Response(['message'=>'The type you selected is archived.'], 404);

        //vérifications de rôle
        $validateRole = $this->validateRoles($validateData['role_limits']);

        if ($validateRole['status'] === 'error')
            return response()->json(['message' => $validateRole['message']], 422);

        //vérification produits
        if(isset($validateData['list_products'])){
            $validateProduct = $this->validateProducts($validateData['list_products']);

            if($validateProduct['status'] === 'error')
                return response()->json(['message' => $validateProduct['message']], 422);
        }

        //recettes et vérification stock
        if(isset($validateData['list_recipes'])) {
            $validateRecipe = $this->validateRecipes($validateData['list_recipes']);

            if ($validateRecipe['status'] === 'error')
                return response()->json(['message' => $validateRecipe['message']], 422);
        }

        //création de l'activité
        $activity = Activity::create([
            'title' => $validateData['title'],
            'description' => $validateData['description'],
            'address' => $validateData['address'] ?? null,
            'zipcode' => $validateData['zipcode'] ?? null,
            'start_date' => $validateData['start_date'],
            'end_date' => $validateData['end_date'],
            'donation' => $validateData['donation'] ?? null,
            'id_type' => $validateData['id_type']
        ]);

        //enregistrement des roles (id min max)
        try {
            foreach ($validateData['role_limits'] as $limits) {
                $limits = json_decode($limits, true);
                $activity->roles()->attach($limits['id'], ['archive' => false, 'min' => $limits["limits"]["min"], 'max' => $limits["limits"]["max"], 'count' => 0]);
            }
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }

        //enregistrement des produits
        if(isset($validateData['list_products'])) {
            try {
                foreach ($validateData['list_products'] as $product) {
                    $product = json_decode($product, true);
                    $activity->products()->attach($product['idProduct'], ['archive' => false, 'count' => $product['count']]);
                }
            } catch (ValidationException $e) {
                return response()->json(['message' => $e->getMessage()], $e->getCode());
            }
        }

        //enregistrement des recettes
        if(isset($validateData['list_recipes'])) {
            try {
                foreach ($validateData['list_recipes'] as $recipe) {
                    $recipe = json_decode($recipe, true);
                    $activity->recipes()->attach($recipe['idRecipe'], ['archive' => false, 'count' => $recipe['count']]);
                }
            } catch (ValidationException $e) {
                return response()->json(['message' => $e->getMessage()], $e->getCode());
            }
        }

        //enregistrement des fichiers
        try{
            if ($request->activity_files) {

                foreach ($request->activity_files as $file) {
                    $name = $activity->id . '-' . time() . rand(1, 99) . '.' . $file->extension();
                    $file->move(public_path() . '/storage/activities/' . $activity->id . '/', $name);

                    $newFile = File::create([
                        'name' => $name,
                        'link' => '/storage/activities/' . $activity->id . '/' . $name,
                    ]);

                    $newFile->activities()->attach($activity->id, ['archive' => false]);
                }
            }
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }

        return Response(['activity' => $activity], 200);
    }

    public function participate($idActivity, $idUser){

    }

    public function getActivities(Request $request){
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 1);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "activities." . $field;

        $activities = Activity::select('id','title', 'description', 'address', 'zipcode', 'start_date', 'end_date', 'donation')
            ->with('type')
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
        return response()->json($activities);
    }

    public function getActivity($id){
        return Activity::find($id) ? Activity::select('activities.id', 'activities.title', 'activities.description', 'activities.address', 'activities.zipcode', 'activities.start_date', 'activities.end_date', 'activities.donation', 'types.name as type_name')->join('types', 'types.id', '=', 'activities.id_type')->where('activities.id', $id)->get() : response()->json(['message' => 'Element doesn\'t exist'], 404);
    }

    public function deleteActivity($id){
        try{
            $activity = Activity::findOrFail($id);
            if($activity->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);

            $activity->archive();

            return response()->json(['activity' => $activity], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateActivity($id, Request $request){
        try{
            $activity = Activity::findOrFail($id);

            try{
                $validateData = $request->validate([
                    'title' => 'string|max:255',
                    'description' => 'string|max:255',
                    'address' => 'string',
                    'zipcode' => 'numeric:5',
                    'start_date' => 'date|after_or_equal:today',
                    'end_date' => 'date|after:start_date',
                    'donation' => 'int',
                    'id_type' => 'int',
                    'archive' => 'boolean'
                ]);
            }catch (ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }

            $type = Type::findOrFail($validateData['id_type']);
            if($type->archive)
                return Response(['message'=>'The type you selected is archived.'], 404);

            foreach($validateData as $key => $value){
                if(in_array($key, $activity->isFillable()))
                    $activity->$key = $value;
            }

            return response()->json(['activity' => $activity], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateActivityRecipe(Request $request, $id)
    {
        try {
            $activity = Activity::findOrFail($id);

            try{
                $validateData = $request->validate([
                    'list_recipes' => 'required|array',
                ]);
            }catch (ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }

            $validationResult = $this->validateRecipes($validateData['list_recipes']);

            if ($validationResult['status'] === 'error')
                return response()->json(['message' => $validationResult['message']], 422);

            $activity->recipes()->detach();

            try{
                foreach ($validateData['list_recipes'] as $recipe)
                    $activity->recipes()->attach($recipe['idRecipe'], ['archive' => false, 'count' => $recipe['count']]);

            }catch(ValidationException $e){
                return response()->json(['message' => $e->getMessage()], $e->getCode());
            }

            return response()->json(['activity' => $activity], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateActivityProduct(Request $request, $id){
        try {
            $activity = Activity::findOrFail($id);

            try{
                $validateData = $request->validate([
                    'list_products' => 'required|array',
                ]);
            }catch (ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }

            $validationResult = $this->validateProducts($validateData['list_products']);

            if ($validationResult['status'] === 'error')
                return response()->json(['message' => $validationResult['message']], 422);

            $activity->products()->detach();

            try {
                foreach ($validateData['list_products'] as $product) {
                    $activity->products()->attach($product['idProduct'], ['archive' => false, 'count' => $product['count']]);
                }
            } catch (ValidationException $e) {
                return response()->json(['message' => $e->getMessage()], $e->getCode());
            }

            return response()->json(['activity' => $activity], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateActivityRole(Request $request, $id){
        try {
            $activity = Activity::findOrFail($id);

            try{
                $validateData = $request->validate([
                    'role_limits' => 'required|array',
                ]);
            }catch (ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }

            $validationResult = $this->validateRoles($validateData['role_limits']);

            if ($validationResult['status'] === 'error')
                return response()->json(['message' => $validationResult['message']], 422);

            $activity->roles()->detach();

            try {
                foreach ($validateData['role_limits'] as $limits) {
                    $activity->roles()->attach($limits['id'], ['archive' => false, 'min' => $limits["limits"]["min"], 'max' => $limits["limits"]["max"], 'count' => 0]);
                }
            }catch(ValidationException $e){
                return response()->json(['message' => $e->getMessage()], $e->getCode());
            }

            return response()->json(['activity' => $activity], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function validateRecipes($recipes)
    {
        $attachedRecipeIds = [];

        foreach ($recipes as $recipe) {
            if (!is_array($recipe))
                $recipe = json_decode($recipe, true);

            if (!isset($recipe['idRecipe']) || !isset($recipe['count']))
                return ['status' => 'error', 'message' => 'idRecipe or count is missing in one or more recipes.'];

            if (in_array($recipe['idRecipe'], $attachedRecipeIds))
                return ['status' => 'error', 'message' => 'You can\'t put 2 same recipes.'];

            $attachedRecipeIds[] = $recipe['idRecipe'];

            $recipeModel = Recipe::findOrFail($recipe["idRecipe"]);

            $makes = $recipeModel->makes()->get();
            foreach ($makes as $make) {
                $product = Product::findOrFail($make->id_product);
                $pieces = $product->pieces()->get();

                $recipeCount = $this->makesToKgOrL($make, $recipe["count"]);
                $piecesCount = $this->calculateToKgOrL($pieces, $product->measure);

                if ($recipeCount > $piecesCount)
                    return ['status' => 'error', 'message' => 'The quantity of ' .  $product->name . ' you ask for the recipe : ' . $recipeModel->name . ', is higher than the stock ! You are asking for ' . $recipeCount . ' kg or l and we have ' . $piecesCount . ' kg or l in stock.' ];
            }
        }

        return ['status' => 'success'];
    }

    public function validateRoles($roles)
    {
        $attachedRoleIds = [];

        foreach ($roles as $limits) {
            if(!is_array($limits))
                $limits = json_decode($limits, true);

            if (!isset($limits['id']) || !isset($limits['limits']) || !isset($limits['limits']['min']) || !isset($limits['limits']['max']))
                return ['status' => 'error', 'message' => 'id, limits, min or max is missing in one or more roles.'];

            if (in_array($limits['id'], $attachedRoleIds))
                return ['status' => 'error', 'message' => 'You can\'t put 2 same roles.'];


            $attachedRoleIds[] = $limits['id'];

            if ($limits === null)
                return ['status' => 'error', 'message' => 'Invalid JSON string for role limits'];

            if (!is_array($limits))
                return ['status' => 'error', 'message' => 'You should have an array of min and max value'];

            $max = $limits['limits']['max'];
            $min = $limits['limits']['min'];

            if ($max < $min)
                return ['status' => 'error', 'message' => 'The max should be greater than the min!'];

            if (!is_int($min) || !is_int($max))
                return ['status' => 'error', 'message' => 'The max and min should be integers!'];

            if (!Role::find($limits["id"]))
                return ['status' => 'error', 'message' => 'The role with id ' . $limits["id"] . ' doesn\'t exist!'];
        }

        return ['status' => 'success'];
    }

    public function validateProducts($products)
    {
        $attachedProductsIds = [];

        foreach ($products as $product) {
            if (!is_array($product))
                $product = json_decode($product, true);

            if (!isset($product['idProduct']) || !isset($product['count']))
                return ['status' => 'error', 'message' => 'idProduct or count is missing.'];

            if (in_array($product['idProduct'], $attachedProductsIds))
                return ['status' => 'error', 'message' => 'You can\'t put 2 same products.'];

            $attachedProductsIds[] = $product['idProduct'];

            $productModel = Product::find($product['idProduct']);

            if (!$productModel)
                return ['status' => 'error', 'message' => 'Product with ID ' . $product['idProduct'] . ' does not exist.'];

            $pieces = $productModel->pieces()->get();

            if ($this->productsToKgOrL($productModel, $product['count']) > $this->calculateToKgOrL($pieces, $productModel->measure))
                return ['status' => 'error', 'message' => 'The quantity of ' .  $productModel->name . ' you ask is higher than the stock ! You are asking for ' . $this->productsToKgOrL($productModel, $product['count']) . 'kg or l and we have ' . $this->calculateToKgOrL($pieces, $productModel->measure) . ' kg or l in stock.' ];
        }

        return ['status' => 'success'];
    }

    public function productsToKgOrL($asset, $count){
        $totalCount = 0;
        switch ($asset->measure){
            case 'kg' : case 'l':
                $totalCount += $count;
                break;
            case 'g' : case 'ml':
                $totalCount += $count /1000;
                break;
            case 'mg':
                $totalCount += $count /(1000*1000);
                break;
            default:
                $totalCount +=  0;
                break;
        }
        return $totalCount;
    }

    public function calculateToKgOrL($assets, $measure){
        $totalCount = 0;
        foreach ($assets as $asset) {
            switch ($measure) {
                case 'kg':case 'l':
                    $totalCount += $asset->count;
                    break;
                case 'g':case 'ml':
                    $totalCount += $asset->count/1000;
                    break;
                case 'mg':
                    $totalCount += $asset->count/(1000*1000);
                    break;
                default:
                    $totalCount += $asset->count * 0;
                    break;
            }
        }
        return $totalCount;
    }

    public function makesToKgOrL($asset, $count){
        $totalCount = 0;
        switch ($asset->measure){
            case 'kg' : case 'l':
                $totalCount += $asset->count * $count;
                break;
            case 'g' : case 'ml':
                $totalCount += $asset->count * $count /1000;
                break;
            case 'mg':
                $totalCount += $asset->count * $count /(1000*1000);
                break;
            default:
                $totalCount += $asset->count * 0;
                break;
        }
        return $totalCount;
    }
}
