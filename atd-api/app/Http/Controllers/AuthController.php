<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private Carbon $now;

    public function __construct(){
        $this->now = Carbon::now();
    }

    public function register(Request $request, int $role)
    {
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'forname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone_country' => 'nullable|string|max:4',
            'phone_number' => 'nullable|string|max:15',
            'gender' => 'required|string|max:1',
            'birth_date' => 'required|date',
            'address' => 'required|string',
            'zipcode' => 'required|string|max:5',
            'siret_number' => 'nullable|string|max:14',
            'compagny' => 'nullable|string'
        ]);

        if ($role == 2 || $role == 3) {
            $user = User::create([
                'name' => $fields['name'],
                'forname' => $fields['forname'],
                'email' => $fields['email'],
                'password' => $fields['password'],
                'phone_country' => $fields['phone_country'],
                'phone_number' => $fields['phone_number'],
                'gender' => $fields['gender'],
                'birth_date' => $fields['birth_date'],
                'address' => $fields['address'],
                'zipcode' => $fields['zipcode'],
                'status' => '0',
                'ban' => false,
                'notification' => true,
                'archive' => false
            ]);
        } else {
            $user = User::create([
                'name' => $fields['name'],
                'forname' => $fields['forname'],
                'email' => $fields['email'],
                'password' => $fields['password'],
                'phone_country' => $fields['phone_country'],
                'phone_number' => $fields['phone_number'],
                'gender' => $fields['gender'],
                'birth_date' => $fields['birth_date'],
                'address' => $fields['address'],
                'zipcode' => $fields['zipcode'],
                'siret_number' => $fields['siret_number'],
                'compagny' => $fields['compagny'],
                'status' => '0',
                'ban' => false,
                'notification' => true,
                'archive' => false
            ]);
        }
        DB::table('have_roles')->insert([
            'id_user' => $user->id,
            'id_role' => $role,
            'archive' => false,
            'created_at' => $this->now,
            'updated_at' => $this->now
        ]);

        $token = $user->createToken('myapptoken')->plainTextToken;
        $response = [
            'user' => $user,
            'token' => $token
        ];

        return Response($response, 201);
    }
}

