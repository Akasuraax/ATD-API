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
                'id_volunteer' => 'required|integer',
                'id_beneficiary' => 'required|integer',
            ]);

            User::findOrFail($fields['id_volunteer']);
            User::findOrFail($fields['id_beneficiary']);

            $volunteer = User::where('id', $fields['id_volunteer'])->get()->first();
            $beneficiary = User::where('id', $fields['id_beneficiary'])->get()->first();

            if(HaveRole::where('id_user', $fields['id_beneficiary'])->where('id_role', 3)->get()->first()){
                $error['id_beneficiary'] = [$beneficiary->forname . ' ' . $beneficiary->name . ' isn\'t a beneficiary'];
                throw ValidationException::withMessages($error);
            }

            if(HaveRole::where('id_user', $fields['id_volunteer'])->where('id_role', 2)->get()->first()){
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

    public function validateVisit(Request $request){

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
