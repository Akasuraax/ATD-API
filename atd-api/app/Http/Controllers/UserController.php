<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function register(Request $request, int $role) : JsonResponse
    {
        $verifBan = User::where('email', $request->email)->where('ban', true)->get()->first();
        if(isset($verifBan))
            return response()->json(['message' => "This email is banned"], 403);

        try {
            $fields = $request->validate([
                'name' => 'required|string|max:255',
                'forname' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'phone_number' => 'nullable|string|max:15',
                'gender' => 'nullable|integer',
                'birth_date' => 'nullable|date',
                'address' => 'required|string',
                'zipcode' => 'required|string|max:5',
                'siret_number' => 'nullable|string|max:14',
                'compagny' => 'nullable|string'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }


        if ($role == 2 || $role == 3) {
            $user = User::create([
                'name' => $fields['name'],
                'forname' => $fields['forname'],
                'email' => $fields['email'],
                'password' => $fields['password'],
                'phone_number' => $fields['phone_number'],
                'gender' => $fields['gender'],
                'birth_date' => $fields['birth_date'],
                'address' => $fields['address'],
                'zipcode' => $fields['zipcode'],
            ]);
        } else {
            $user = User::create([
                'name' => $fields['name'],
                'forname' => $fields['forname'],
                'email' => $fields['email'],
                'password' => $fields['password'],
                'phone_number' => $fields['phone_number'],
                'gender' => 2,
                'birth_date' => Carbon::now(),
                'address' => $fields['address'],
                'zipcode' => $fields['zipcode'],
                'siret_number' => $fields['siret_number'],
                'compagny' => $fields['compagny'],
            ]);
        }
        //add MtM in have_roles
        $user->roles()->attach($role);

        $response = [
            'user' => $user,
        ];

        return response()->json($response, 201);
    }
}
