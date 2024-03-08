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
        $attachedRoleIds = [];
        foreach ($validateData['role_limits'] as $limits) {
            $limits = json_decode($limits, true);

            if (!isset($limits['id']) || !isset($limits['limits']) || !isset($limits['limits']['min']) || !isset($limits['limits']['max']))
                return response()->json(['message' => 'id, limits, min or max is missing in one or more roles.'], 400);

            if (in_array($limits['id'], $attachedRoleIds))
                return response()->json(['message' => 'The role with id ' . $limits['id'] . ' has already been attached to the activity.'], 422);

            $attachedRoleIds[] = $limits['id'];

            if ($limits === null)
                return response()->json(['message' => 'Invalid JSON string for role limits'], 422);

            if (!is_array($limits))
                return response()->json(['message' => 'You should have an array of min and max value'], 422);

            $max = $limits['limits']['max'];
            $min = $limits['limits']['min'];

            if ($max < $min)
                return response()->json(['message' => 'The max should be greater than the min!'], 422);

            if (!is_int($min) || !is_int($max))
                return response()->json(['message' => 'The max and min should be integers!'], 422);

            if (!Role::find($limits["id"]))
                return response()->json(['message' => 'The role with id ' . $limits["id"] . ' doesn\'t exist!'], 404);
        }

        //recettes et vérification stock

        if ($request->list_recipes) {
            foreach ($validateData['list_recipes'] as $recipe) {
                $recipe = json_decode($recipe, true);

                if (!isset($recipe['idRecipe']) || !isset($recipe['count']))
                    return response()->json(['message' => 'idRecipe or count is missing in one or more recipes.'], 400);

                $recipeModel = Recipe::findOrFail($recipe["idRecipe"]);

                $makes = $recipeModel->makes()->get();
                foreach ($makes as $make) {
                    $product = Product::findOrFail($make->id_product);
                    $pieces = $product->pieces()->get();

                    $recipeCount = $this->makesToKg($make, $recipe["count"]);
                    $piecesCount = $this->calculateToKg($pieces);

                    if($recipeCount > $piecesCount)
                        return response()->json(['message' => 'The quantity of ' .  $product->name . ' you ask for the recipe : ' . $recipeModel->name . ', is higher than the stock ! You are asking for ' . $recipeCount . ' kg and we have ' . $piecesCount . ' kg in stock.' ], 422);
                }
            }
        }


        //creéation de l'activité
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
                $activity->roles()->attach($limits["id"], ['archive' => false, 'min' => $limits["limits"]["min"], 'max' => $limits["limits"]["max"], 'count' => 0]);
            }
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
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


        return Response(['activity' => 'cc'], 200);
    }

    public function makesToKg($asset, $count){
        $totalCount = 0;
        switch ($asset->measure){
            case 'kg':
                $totalCount += $asset->count * $count;
                break;
            case 'g':
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

    public function calculateToKg($assets){
        $totalCount = 0;
        foreach ($assets as $asset) {
            switch ($asset->measure) {
                case 'kg':
                    $totalCount += $asset->count;
                    break;
                case 'g':
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

        $activities = Activity::select('activities.id','activities.title', 'activities.description', 'activities.address', 'activities.zipcode', 'activities.start_date', 'activities.end_date', 'activities.donation', 'types.name as type_name')
            ->join('types', 'types.id', '=', 'activities.id_type')
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
            $activity->archive = true;

            $journeys = Journey::where('id_activity', $id)->where('archive', false)->get();
            if(!$journeys->isEmpty()){
                foreach($journeys as $journey){
                    $service = new DeleteService();
                    $service->deleteJourneyService($journey->id);
                }
            }

            $activity->save();
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
}
