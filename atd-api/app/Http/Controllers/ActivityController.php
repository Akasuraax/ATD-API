<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\File;
use App\Models\Journey;
use App\Models\Recipe;
use App\Models\Role;
use App\Models\Product;
use App\Models\User;
use App\Services\DeleteService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Type;
use function PHPUnit\Framework\isEmpty;

class ActivityController extends Controller
{

    protected RecipeController $recipeController;
    protected ProductController $productController;


    public function __construct(RecipeController $recipeController, ProductController $productController)
    {
        $this->recipeController = $recipeController;
        $this->productController = $productController;
    }


    public function createActivity(Request $request)
    {
        try {
            $validateData = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'address' => 'nullable|string',
                'zipcode' => 'nullable|numeric:5',
                'start_date' => 'required|date|after_or_equal:today|date_format:Y-m-d\TH:i:sP',
                'end_date' => 'required|date|after:start_date|date_format:Y-m-d\TH:i:sP',
                'donation' => 'nullable|int',
                'type' => 'required|int',
                'list_products' => 'nullable|array',
                'recipes' => 'nullable|array',
                'roles' => 'required|array',
                'activity_files' => 'nullable',
                'activity_files.*' => 'mimes:pdf,jpg,png,jpeg|max:20000'
            ]);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $type = Type::findOrFail($validateData['type']);
        if ($type->archive)
            return Response(['message' => 'The type you selected is archived.'], 404);

        //vérifications de rôle
        $validateRole = $this->validateRoles($validateData['roles']);

        if ($validateRole['status'] === 'error')
            return response()->json(['message' => $validateRole['message']], 422);

        //création de l'activité
        $activity = Activity::create([
            'title' => $validateData['title'],
            'description' => $validateData['description'],
            'address' => $validateData['address'] ?? null,
            'zipcode' => $validateData['zipcode'] ?? null,
            'start_date' => $validateData['start_date'],
            'end_date' => $validateData['end_date'],
            'donation' => $validateData['donation'] ?? null,
            'id_type' => $validateData['type']
        ]);
        //enregistrement des roles (id min max)
        try {
            foreach ($validateData['roles'] as $limits) {
                $activity->roles()->attach($limits['id'], ['archive' => false, 'min' => $limits["limits"]["min"], 'max' => $limits["limits"]["max"], 'count' => 0]);
            }
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }

        //enregistrement des produits
        if (isset($validateData['list_products'])) {
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
        if (isset($validateData['recipes'])) {
            try {
                foreach ($validateData['recipes'] as $recipe) {
                    $activity->recipes()->attach($recipe['id'], ['archive' => false, 'count' => $recipe['count']]);
                }
            } catch (ValidationException $e) {
                return response()->json(['message' => $e->getMessage()], $e->getCode());
            }
        }

        return response()->json(["activity" => $activity]);
    }

    public function participate(Request $request, $id)
    {
        try {
            $activity = Activity::where("id",$id)
                ->with('roles')
                ->with('files')
                ->where('archive',false)
                ->first();

            if(!$activity) {
                return response()->json(['message' => 'Element doesn\'t exist'], 404);
            }
            try {
                $validateData = $request->validate([
                    'user' => 'required',
                    'count' =>'required',
                    'role' => 'required'
                ]);
            } catch (ValidationException $e) {
                return response()->json(['errors' => $e->errors()], 422);
            }

            try {
                $user = User::findOrFail($request['user.id']);
            }
            catch (ValidationException $e) {
                return response()->json(['message' => $e->getMessage()], $e->getCode());
            }

            $alreadyParticipates = $activity->users()->where('id_user', $user->id)->exists();

            if ($alreadyParticipates) {
                return response()->json(['message' => 'User already participates in this activity'], 400);
            }

            $hasRequiredRole = $user->roles()->where('id', $request['role'])->exists();
            if (!$hasRequiredRole) {
                return response()->json(['message' => 'User does not have the required role to participate in this activity'], 400);
            }

            $activityHasRole = $activity->roles()->where('id', $request['role'])->exists();
            if (!$activityHasRole) {
                return response()->json(['message' => 'Activity does not have the required role'], 400);
            }

            $role = $user->roles()->where('id', $request['role'])->first();

            $pivot = $activity->roles()->where('id_role', $role->id)->first()->pivot;

            if ($pivot->count + $request["count"] > $pivot->max) {
                return response()->json(['message' => 'Maximum number of participants for this role has been reached'], 400);
            }

            $pivot->count += $request["count"];
            $pivot->save();

            $activity->participates()->attach([$user->id => ['count' => $request["count"], 'role' => $request['role.id']]]);

            $activity = Activity::find($id);

            $participation = $activity->users()->where('id_user', $user->id)->withPivot('role')->first();
            $isSubscribe = $participation !== null;
            $roleSubscribe = $isSubscribe ? $participation->pivot->role : null;


            // Prepare the updated activity data to be returned
            $renamedActivity = [
                'id' => $activity->id,
                'title' => $activity->title,
                'description' => $activity->description,
                'address' => $activity->address,
                'zipcode' => $activity->zipcode,
                'start_date' => $activity->start_date,
                'end_date' => $activity->end_date,
                'donation' => $activity->donation,
                'type' => $activity->type,
                'isSubscribe' => $isSubscribe,
                'roleSubscribe' => $roleSubscribe,
                'files' => $activity->files->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'name' => $file->name,
                        'link' => $file->link,
                    ];
                }),
                'roles' => $activity->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'limits' => [
                            'min' => $role->pivot->min,
                            'max' => $role->pivot->max,
                        ],
                        'count' => $role->pivot->count,
                    ];
                }),
            ];

            return response()->json(['message' => "subscribe", 'activity' => $renamedActivity]);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function deleteParticipate(Request $request, $id) {
        try {
            $validateData = $request->validate([
                'user' => 'required',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
        $user = User::where('id', $request["user"])->first();
        $activity = Activity::where("id",$id)
            ->with('roles')
            ->where('archive',false)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Element doesn\'t exist'], 404);
        }

        $alreadyParticipates = $activity->users()->where('id_user', $user->id)->exists();

        if (!$alreadyParticipates) {
            return response()->json(['message' => 'User does not participate in this activity'], 400);
        }

        $pivot = $activity->users()->where('id_user', $user->id)->first()->pivot;
        $pivotRole = $activity->roles()->where('id_role', $pivot->role)->first()->pivot;
        $pivotRole->count -= $pivot->count;
        $pivotRole->save();

        $activity->users()->detach($user->id);
        $activity->users()->detach($user->id);

        $updatedActivity = Activity::where("id",$id)
            ->with('roles')
            ->where('archive',false)
            ->first();

        $renamedActivity = [
            'id' => $updatedActivity->id,
            'title' => $updatedActivity->title,
            'description' => $updatedActivity->description,
            'address' => $updatedActivity->address,
            'zipcode' => $updatedActivity->zipcode,
            'start_date' => $updatedActivity->start_date,
            'end_date' => $updatedActivity->end_date,
            'donation' => $updatedActivity->donation,
            'type' => $updatedActivity->type,
            'isSubscribe' => false,
            'roleSubscribe' => null,
            'files' => [],
            'roles' => $updatedActivity->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'limits' => [
                        'min' => $role->pivot->min,
                        'max' => $role->pivot->max,
                    ],
                    'count' => $role->pivot->count,
                ];
            }),
        ];

        return response()->json(['message' => "Unsubscribed successfully", 'activity' => $renamedActivity]);
    }

    public function isUserRegisteredToActivity(Request $request, $activityId)
    {
        try {
            $validateData = $request->validate([
                'user' => 'required',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
        $user = User::where('id', $request["user.id"])->first();

        if (!$user) {
            return response()->json(['message' => 'Element doesn\'t exist'], 404);
        }

        $activity = Activity::where('id', $activityId)
            ->where('archive',false)
            ->first();

        $isRegistered = $activity->users()->where('id_user', $user->id)->exists();

        if ($isRegistered) {
            $role = $activity->participates()->where('id_user', $user->id)->withPivot('role')->first()->pivot->role;
            return response()->json(['message' => 'User is registered', 'role' => $role]);
        } else {
            return response()->json(['message' => 'User is not registered']);
        }
    }

    public function getActivities(Request $request)
    {
        // Obtenir la date et l'heure actuelles
        $now = Carbon::now();

        $activities = Activity::select('activities.id', 'activities.title', 'activities.description', 'activities.address', 'activities.zipcode', 'activities.start_date', 'activities.end_date', 'activities.donation', 'types.name as type_name')
            ->join('types', 'types.id', '=', 'activities.id_type')
            ->where('activities.start_date', '>', $now)
            ->where('activities.archive', false)
            ->limit(3)
            ->get();

        return response()->json($activities);
    }

    public function getActivitiesBetween(Request $request)
    {

        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');


        $activities = Activity::select('activities.id', 'activities.title', 'activities.description', 'activities.address', 'activities.zipcode', 'activities.start_date', 'activities.end_date', 'activities.donation', 'activities.id_type')
            ->with('type')
            ->with('roles')
            ->where('activities.start_date', '<=', $endDate)
            ->where('activities.end_date', '>=', $startDate)
            ->where('activities.archive', false)
            ->get();

        $renamedActivities = $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'title' => $activity->title,
                'description' => $activity->description,
                'address' => $activity->address,
                'start' => $activity->start_date,
                'end' => $activity->end_date,
                'type_name' => $activity->type->name,
                'roles' => $activity->roles,
                'color' => $activity->type->color
            ];
        });

        return response()->json($renamedActivities);
    }

    public function getFuturUserActivities(Request $request)
    {
        $userId = $request->input('userId');

        $activities = Activity::select('activities.id', 'activities.title', 'activities.description', 'activities.address', 'activities.zipcode', 'activities.start_date', 'activities.end_date', 'activities.donation', 'activities.id_type')
            ->with('type')
            ->with('roles')
            ->with('users')
            ->where('activities.archive', false)
            ->whereHas('users', function ($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->where('activities.end_date', '>', now()) // This line filters activities where end_date is in the future
            ->get();

        $renamedActivities = $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'title' => $activity->title,
                'description' => $activity->description,
                'address' => $activity->address,
                'start' => $activity->start_date,
                'end' => $activity->end_date,
                'type_name' => $activity->type->name,
                'roles' => $activity->roles,
                'color' => $activity->type->color
            ];
        });

        return response()->json($renamedActivities);
    }

    public function getActivity($id)
    {
        $activity = Activity::select('activities.id', 'activities.title', 'activities.description', 'activities.address', 'activities.zipcode', 'activities.start_date', 'activities.end_date', 'activities.donation', "activities.id_type")
            ->with('type')
            ->with('files')
            ->with('users')
            ->with('roles')
            ->with('products')
            ->with('recipes')
            ->with(['journeys' => function ($query) {
                $query->with('steps');
            }])
            ->where('activities.id', $id)
            ->where('activities.archive', false)
            ->first();

        if (!$activity) {
            return response()->json(['message' => 'Element doesn\'t exist'], 404);
        }

        $renamedActivity = [
            'id' => $activity->id,
            'title' => $activity->title,
            'description' => $activity->description,
            'address' => $activity->address,
            'zipcode' => $activity->zipcode,
            'start_date' => $activity->start_date,
            'end_date' => $activity->end_date,
            'donation' => $activity->donation,
            'type' => $activity->type,
            'journeys' => $activity->journeys->map(function ($journey) {
                return [
                    'id' => $journey->id,
                    'name' => $journey->name,
                    'duration' => $journey->duration,
                    'distance' => $journey->distance,
                    'steps' => $journey->steps->map(function ($step) {
                        return [
                            'id' => $step->id,
                            'address' => $step->address,
                        ];
                    }),
                ];
            }),
            'files' => $activity->files->map(function ($file) {
                return [
                    'id' => $file->id,
                    'name' => $file->name,
                    'link' => $file->link,
                ];
            }),
            'roles' => $activity->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'limits' => [
                        'min' => $role->pivot->min,
                        'max' => $role->pivot->max,
                    ],
                    'count' => $role->pivot->count,
                ];
            }),
            'recipes' => $activity->recipes->map(function ($recipe) {
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'description' => $recipe->description,
                    'count' => $recipe->pivot->count,
                    'max' => $this->recipeController->getNbPiecesRecipe($recipe->id),
                ];
            }),
            'products' => $activity->products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'measure' => $product->measure,
                    'count' => $product->pivot->count,
                    'max' => $this->productController->getNbProductProduct($product->id),
                ];
            }),
        ];

        return response()->json(['activity' => $renamedActivity]);
    }

    public function getActivityForUser(Request $request, $id)
    {
        $validateData = $request->validate([
            'user' => 'required',
        ]);

        // Retrieve the user from the request
        $user = User::select("*")
        ->where('id', $request['user'])
        ->first();

        if (!$user) {
            return response()->json(['message' => 'User does not exist'], 404);
        }

        $activity = Activity::select('activities.id', 'activities.title', 'activities.description', 'activities.address', 'activities.zipcode', 'activities.start_date', 'activities.end_date', 'activities.donation', "activities.id_type")
            ->with('type')
            ->with('files')
            ->with('roles')
            ->with('journeys')
            ->where('activities.id', $id)
            ->where('activities.archive', false)
            ->first();

        if (!$activity) {
            return response()->json(['message' => 'Element doesn\'t exist'], 404);
        }

        $participation = $activity->users()->where('id_user', $user->id)->withPivot('role')->first();
        $isSubscribe = $participation !== null;
        $roleSubscribe = $isSubscribe ? $participation->pivot->role : null;

        $start = now()->greaterThanOrEqualTo($activity->start_date);


        $renamedActivity = [
            'id' => $activity->id,
            'title' => $activity->title,
            'description' => $activity->description,
            'address' => $activity->address,
            'zipcode' => $activity->zipcode,
            'start_date' => $activity->start_date,
            'end_date' => $activity->end_date,
            'donation' => $activity->donation,
            'start' => $start,
            'type' => $activity->type,
            'isSubscribe' => $isSubscribe,
            'roleSubscribe' => $roleSubscribe,
            'files' => [],
            'journeys' => $activity->journeys->map(function ($journey) {
                return [
                    'id' => $journey->id,
                    'name' => $journey->name,
                    'duration' => $journey->duration,
                    'distance' => $journey->distance,
                    'steps' => $journey->steps->map(function ($step) {
                        return [
                            'id' => $step->id,
                            'address' => $step->address,
                        ];
                    }),
                ];
            }),
            'roles' => $activity->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'limits' => [
                        'min' => $role->pivot->min,
                        'max' => $role->pivot->max,
                    ],
                    'count' => $role->pivot->count,
                ];
            }),
        ];

        if ($isSubscribe) {
            $renamedActivity['files'] = $activity->files->map(function ($file) {
                return [
                    'id' => $file->id,
                    'name' => $file->name,
                    'link' => $file->link,
                ];
            });
        }

        return response()->json(['activity' => $renamedActivity]);
    }

    public function deleteActivity($id)
    {
        try {
            $activity = Activity::findOrFail($id);
            if ($activity->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);
            $activity->archive($id);

            $activity->save();
            return response()->json(['activity' => $activity, "status" => 204], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateActivity($id, Request $request)
    {
        try {
            $activity = Activity::findOrFail($id);

            try {
                $validateData = $request->validate([
                    'title' => 'string|max:255',
                    'description' => 'string',
                    'address' => 'string',
                    'start_date' => 'date|after_or_equal:today',
                    'end_date' => 'date|after:start_date',
                    'donation' => 'int',
                    'archive' => 'boolean'
                ]);
            } catch (ValidationException $e) {
                return response()->json(['errors' => $e->errors()], 422);
            }

            $activity->update($validateData);

            return response()->json(['activity' => $activity], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateActivityRecipe(Request $request, $id)
    {
        try {
            $activity = Activity::findOrFail($id);

            try {
                $validateData = $request->validate([
                    'recipes' => 'array',
                ]);
            } catch (ValidationException $e) {
                return response()->json(['errors' => $e->errors()], 422);
            }

            $activity->recipes()->detach();

            try {
                foreach ($validateData['recipes'] as $recipe)
                    $activity->recipes()->attach($recipe['id'], ['archive' => false, 'count' => $recipe['count']]);

            } catch (ValidationException $e) {
                return response()->json(['message' => $e->getMessage()], $e->getCode());
            }

            return response()->json(['activity' => $activity], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateActivityProduct(Request $request, $id)
    {
        try {
            $activity = Activity::findOrFail($id);

            try {
                $validateData = $request->validate([
                    'list_products' => 'array',
                ]);
            } catch (ValidationException $e) {
                return response()->json(['errors' => $e->errors()], 422);
            }

            $validationResult = $this->validateProducts($validateData['list_products']);

            if ($validationResult['status'] === 'error')
                return response()->json(['message' => $validationResult['message']], 422);
            $activity->products()->detach();

            try {
                foreach ($validateData['list_products'] as $product) {
                    $activity->products()->attach($product['id'], ['archive' => false, 'count' => $product['count']]);
                }
            } catch (ValidationException $e) {
                return response()->json(['message' => $e->getMessage()], $e->getCode());
            }

            return response()->json(['activity' => $activity], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateActivityRole(Request $request, $id)
    {
        try {
            $activity = Activity::findOrFail($id);

            try {
                $validateData = $request->validate([
                    'role_limits' => 'required|array',
                ]);
            } catch (ValidationException $e) {
                return response()->json(['errors' => $e->errors()], 422);
            }

            $validationResult = $this->validateRoles($validateData['role_limits']);

            if ($validationResult['status'] === 'error')
                return response()->json(['message' => $validationResult['message']], 422);

            // Obtenir les ID des rôles déjà attachés
            $existingRoleIds = $activity->roles()->pluck('roles.id')->toArray();

            // Préparation des données pour l'attachement
            $newRoles = [];
            $updateRoles = [];
            foreach ($validateData['role_limits'] as $limits) {
                if (!in_array($limits['id'], $existingRoleIds)) {
                    // Nouveau rôle, attacher avec count à 0
                    $newRoles[$limits['id']] = [
                        'archive' => false,
                        'min' => $limits["limits"]["min"],
                        'max' => $limits["limits"]["max"],
                        'count' => 0,
                    ];
                } else {
                    // Rôle existant, mettre à jour sans affecter count
                    $updateRoles[$limits['id']] = [
                        'archive' => false,
                        'min' => $limits["limits"]["min"],
                        'max' => $limits["limits"]["max"],
                    ];
                }
            }

            // Attacher les nouveaux rôles
            if (!empty($newRoles)) {
                $activity->roles()->attach($newRoles);
            }

            // Mettre à jour les rôles existants
            if (!empty($updateRoles)) {
                $activity->roles()->syncWithoutDetaching($updateRoles);
            }

            return response()->json(['activity' => $activity], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function validateRoles($roles)
    {
        $attachedRoleIds = [];

        foreach ($roles as $limits) {
            if (!is_array($limits))
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

            if (!isset($product['id']) || !isset($product['count']))
                return ['status' => 'error', 'message' => 'idProduct or count is missing.'];

            if (in_array($product['id'], $attachedProductsIds))
                return ['status' => 'error', 'message' => 'You can\'t put 2 same products.'];

            $attachedProductsIds[] = $product['id'];

            $productModel = Product::find($product['id']);

            if (!$productModel)
                return ['status' => 'error', 'message' => 'Product with ID ' . $product['id'] . ' does not exist.'];

        }

        return ['status' => 'success'];
    }
}
