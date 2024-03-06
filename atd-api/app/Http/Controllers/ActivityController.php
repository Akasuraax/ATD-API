<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Journey;
use App\Models\Role;
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
                'files' => 'nullable|array'
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $type = Type::findOrFail($validateData['id_type']);
        if($type->archive)
            return Response(['message'=>'The type you selected is archived.'], 404);

        foreach($validateData['role_limits'] as $role => $qte){
            if(count($qte) == 1 || count($qte) == 0)
                return response()->json(['message' => 'The max and min is required.'], 422);
            if($qte[1] < $qte[0])
                return response()->json(['message' => 'The max should be greater than the min !'], 422);
            if(!is_int($qte[1]) || !is_int($qte[0]))
                return response()->json(['message' => 'The max and min should be integer!'], 422);
            if(!Role::find($role))
                return response()->json(['message' => 'The role with id ' . $role . ' doesn\'t exist !'], 404);
        }

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

        foreach($validateData['role_limits'] as $role => $qte){
            $activity->roles()->attach($role, ['archive' => false, 'min' => $qte[0], 'max' => $qte[1], 'count' => $qte[1]]);
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
