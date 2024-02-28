<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Visit;
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

            $volunteerExists = User::where('id', $fields['id_volunteer'])->exists();
            $beneficiaryExists = User::where('id', $fields['id_beneficiary'])->exists();

            if (!$volunteerExists || !$beneficiaryExists) {
                $errors = [];
                if (!$volunteerExists) {
                    $errors['id_volunteer'] = ['ID volunteer not found'];
                }
                if (!$beneficiaryExists) {
                    $errors['id_beneficiary'] = ['ID beneficiary not found'];
                }
                throw ValidationException::withMessages($errors);
            }

            $volunteer = User::where('id', $fields['id_volunteer'])->get()->first();
            $beneficiary = User::where('id', $fields['id_beneficiary'])->get()->first();

            if(Visit::where('id_beneficiary', $fields['id_beneficiary'])->get()){
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

    public function deleteVisit(Request $request){

    }
}
