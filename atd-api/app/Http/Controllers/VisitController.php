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

    }

    public function getVisit(int $visit_id){

    }

    public function updateVisit(int $visit_id, Request $request)
    {
        $visit = Visit::findOrFail($visit_id);

        try {
            $fields = $request->validate([
                'checking' => 'integer|max:1',
                'id_volunteer' => 'integer',
                'id_beneficiary' => 'integer',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        if(HaveRole::where('id_user', TokenController::decodeToken($request->header('Authorization'))->id)->where('id_role', 1)->get()->first()){
            if(isset($fields['id_volunteer']))
                $visit->id_volunteer = $fields['id_volunteer'];
            if(isset($fields['id_beneficiary']))
                $visit->id_beneficiary = $fields['id_beneficiary'];
            if(isset($fields['checking']))
                $visit->checking = $fields['checking'];
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
            if($visit->checking)
                $visit->checking = false;
            else
                $visit->checking = true;

            $visit->save();
            $visit->touch();
            $beneficiary = User::where('id', $visit->id_beneficiary)->get()->first();
            $volunteer = User::where('id', $visit->id_volunteer)->get()->first();

            return response()->json([
                'visit' => [
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
