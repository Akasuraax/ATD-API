<?php

namespace App\Http\Controllers;

use App\Models\HaveRole;
use App\Models\User;
use App\Models\Visit;
use Exception;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use function PHPUnit\Framework\isEmpty;

class VisitController extends Controller
{
    public function createVisit(Request $request): JsonResponse
    {
        try {
            $fields = $request->validate([
                'checking' => 'required|integer|max:1',
                'id_volunteer' => 'integer',
                'id_beneficiary' => 'required|integer',
            ]);

            User::findOrFail($fields['id_volunteer']);
            User::findOrFail($fields['id_beneficiary']);

            $volunteer = User::where('id', $fields['id_volunteer'])->get()->first();
            $beneficiary = User::where('id', $fields['id_beneficiary'])->get()->first();

            if(!HaveRole::where('id_user', $fields['id_beneficiary'])->where('id_role', 3)->get()->first()){
                $error['id_beneficiary'] = [$beneficiary->forname . ' ' . $beneficiary->name . ' isn\'t a beneficiary'];
                throw ValidationException::withMessages($error);
            }

            if(!HaveRole::where('id_user', $fields['id_volunteer'])->where('id_role', 2)->get()->first()){
                $error['id_beneficiary'] = [$beneficiary->forname . ' ' . $beneficiary->name . ' isn\'t a volunteer'];
                throw ValidationException::withMessages($error);
            }

            if(Visit::where('id_beneficiary', $fields['id_beneficiary'])->where('archive', false)->get()->first()){
                $error['id_beneficiary'] = [$beneficiary->forname . ' ' . $beneficiary->name . ' already has a visit'];
                throw ValidationException::withMessages($error);
            }

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $visit = Visit::create([
            'checking' => $fields['checking'],
            'id_volunteer' => $fields['id_volunteer'],
            'id_beneficiary' => $fields['id_beneficiary'],
        ]);

        return response()->json([
            'visit' => [
                'id' => $visit->id,
                'checking' => $visit->checking,
                'volunteer' => [
                    'name' => $volunteer->name,
                    'forname' => $volunteer->forname
                ],
                'id_beneficiary' => [
                    'name' => $beneficiary->name,
                    'forname' => $beneficiary->forname
                ],
            'created_at' => $visit->created_at
            ]
        ], 201);
    }

    public function getVisits(Request $request){
        $user_id = TokenController::decodeToken($request->header('Authorization'))->id;
        $beneficiary = HaveRole::where('id_user', $user_id)->where('id_role', 3)->get()->first();
        $admin = HaveRole::where('id_user', $user_id)->where('id_role', 1)->get()->first();

        if($beneficiary){
            $visit = Visit::where('id_beneficiary', $user_id)->get()->first();
            return response()->json([
                "visit" => [
                    'checking' => $visit->checking,
                    'created_at' => $visit->created_at,
                    'update_at' => $visit->updated_at
                ]
            ]);
        }
        $perPage = $request->input('pageSize', 10);
        if($perPage > 50){
            $perPage = 50;
        }
        $page = $request->input('page', 1);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $visit = Visit::select('*')
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
            } )
            ->orderBy($field, $sort)
            ->paginate($perPage, ['*'], 'page', $page + 1);

        if($admin){
            return response()->json([
                'visits' => $visit
            ]);
        }else{
            foreach ($visit as $v) {
                $beneficiary = User::where('id', $v['id_beneficiary'])->get()->first();
                $filteredVisit = [];
                if(!$v['archive']){
                    $filteredVisit = [
                        'id' => $v['id'],
                        'checking' => $v['checking'],
                        'updated_at' => $v['updated_at'],
                        'beneficiary' => [
                            'address' => $beneficiary->address,
                            'zipcode' => $beneficiary->zipcode
                        ]
                    ];
                }

                if($filteredVisit != [])
                    $filteredVisits[] = $filteredVisit;
            }

            return response()->json([
                'visits' => $filteredVisits
            ]);
        }


    }

    public function getVisit(int $visit_id, Request $request){

        $visit = Visit::findOrFail($visit_id);
        $user_id = TokenController::decodeToken($request->header('Authorization'))->id;
        $admin = HaveRole::where('id_user', $user_id)->where('id_role', 1)->get()->first();
        $volunteer_id = HaveRole::where('id_user', $user_id)->where('id_role', 2)->get()->first();

        if(($visit->archive && $admin == NULL)){
                return response()->json([
                    'message' => 'Ressource not found'
                ], 404);
        }

        if($visit->id_beneficiary != $user_id && $volunteer_id == NULL && $admin == NULL){
            return response()->json([
                'message' => 'Ressource not found'
            ], 404);
        }

        $beneficiary = User::where('id', $visit->id_beneficiary)->get()->first();
        $volunteer = User::where('id', $visit->id_volunteer)->get()->first();

        //If it's the administrator
        if($admin != NULL){
            return response()->json([
                "visit" => [
                    'id' => $visit->id,
                    'checking' => $visit->checking,
                    'archive' => $visit->archive,
                    'created_at' => $visit->created_at,
                    'update_at' => $visit->updated_at,
                    'volunteer' => [
                        'forname' => $volunteer->forname,
                        'name' => $volunteer->name
                    ],
                    'beneficiary' => [
                        'forname' => $beneficiary->forname,
                        'name' => $beneficiary->name,
                        'address' => $beneficiary->address,
                        'zipcode' => $beneficiary->zipcode
                    ]
                ]
            ]);
        }else
            return response()->json([
                "visit" => [
                    'checking' => $visit->checking,
                    'created_at' => $visit->created_at,
                    'update_at' => $visit->updated_at,
                    'volunteer' => [
                        'forname' => $volunteer->forname,
                        'name' => $volunteer->name
                    ],
                    'beneficiary' => [
                        'forname' => $beneficiary->forname,
                        'name' => $beneficiary->name
                    ]
                ]
            ]);

    }

    public function updateVisit(int $visit_id, Request $request)
    {
        $visit = Visit::findOrFail($visit_id);

        try {
            $fields = $request->validate([
                'checking' => 'integer|max:1',
                'id_volunteer' => 'integer',
                'id_beneficiary' => 'integer',
                'archive' => 'nullable|boolean'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        //if it's an administrator

        if(HaveRole::where('id_user', TokenController::decodeToken($request->header('Authorization'))->id)->where('id_role', 1)->get()->first()){
            if(isset($fields['id_volunteer']))
                $visit->id_volunteer = $fields['id_volunteer'];
            if(isset($fields['id_beneficiary']))
                $visit->id_beneficiary = $fields['id_beneficiary'];
            if(isset($fields['checking']))
                $visit->checking = $fields['checking'];
            if(isset($fields['archive']))
                $visit->checking = $fields['archive'];

            $visit->save();
            $visit->touch();

            $beneficiary = User::where('id', $visit->id_beneficiary)->get()->first();
            $volunteer = User::where('id', $visit->id_volunteer)->get()->first();

            return response()->json([
                'visit' => [
                    'id' => $visit->id,
                    'checking' => $visit->checking,
                    'archive' => $visit->archive,
                    'updated_at' => $visit->updated_at,
                    'volunteer' => [
                        'forname' => $volunteer->forname,
                        'name' => $volunteer->name
                    ],
                    'beneficiary' => [
                        'forname' => $beneficiary->forname,
                        'name' => $beneficiary->name
                    ]
                ]
            ]);

        }else{
            $user_id = TokenController::decodeToken($request->header('Authorization'))->id;
            $visit->id_volunteer = $user_id;
            if($visit->checking == 0)
                $visit->checking = 1;
            else
                $visit->checking = 0;

            $visit->save();
            $visit->touch();
            $beneficiary = User::where('id', $visit->id_beneficiary)->get()->first();
            $volunteer = User::where('id', $visit->id_volunteer)->get()->first();

            return response()->json([
                'visit' => [
                    'updated_at' => $visit->updated_at,
                    'checking' => $visit->checking,
                    'volunteer' => [
                        'forname' => $volunteer->forname,
                        'name' => $volunteer->name
                    ],
                    'beneficiary' => [
                        'forname' => $beneficiary->forname,
                        'name' => $beneficiary->name
                    ]
                ]
            ]);

        }
    }

    /**
     * @throws Exception
     */
    public function deleteVisit(int $visit_id, Request $request): JsonResponse
    {
        $visit = Visit::findOrFail($visit_id);

        if ($visit->archive) {
            return response()->json([
                'message' => 'Visit is already archived'
            ], 400);
        }

        $visit->archive = true;
        $visit->save();

        return response()->json(['message' => 'Visit deleted successfully']);
    }
}
