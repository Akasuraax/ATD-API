<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    private Carbon $now;


    public function __construct(){
        $this->now = Carbon::now();
    }
    public function createVolunteer(Request $request)
    {

        $userData = $request->json()->all();
        // Verifier les données

        $user = User::create([
            'name' => $userData['name'],
            'forname' => $userData['forname'],
            'email' => $userData['email'],
            'password' => $userData['password'],
            'phone_country' => $userData['phone_country'],
            'phone_number' => $userData['phone_number'],
            'gender' => $userData['gender'],
            'birth_date' => $userData['birth_date'],
            'address' => $userData['address'],
            'zipcode' => $userData['zipcode'],
            'status' => '0',
            'ban' => false,
            'notification' => true,
            'archive' => false
        ]);

        DB::table('have_roles')->insert([
            'id_user' => $user->id,
            'id_role' => 2,
            'archive' => false,
            'created_at' => $this->now,
            'updated_at' => $this->now
        ]);

        return $user;
    }

    public function createBeneficiary(Request $request)
    {

        $userData = $request->json()->all();
        // Verifier les données

        $user = User::create([
            'name' => $userData['name'],
            'forname' => $userData['forname'],
            'email' => $userData['email'],
            'password' => $userData['password'],
            'phone_country' => $userData['phone_country'],
            'phone_number' => $userData['phone_number'],
            'gender' => $userData['gender'],
            'birth_date' => $userData['birth_date'],
            'address' => $userData['address'],
            'zipcode' => $userData['zipcode'],
            'status' => '0',
            'ban' => false,
            'notification' => true,
            'archive' => false
        ]);

        DB::table('have_roles')->insert([
            'id_user' => $user->id,
            'id_role' => 3,
            'archive' => false,
            'created_at' => $this->now,
            'updated_at' => $this->now
        ]);

        return $user;
    }

    public function createPartner(Request $request)
    {

        $userData = $request->json()->all();
        // Verifier les données

        $user = User::create([
            'name' => $userData['name'],
            'forname' => $userData['forname'],
            'email' => $userData['email'],
            'password' => $userData['password'],
            'phone_country' => $userData['phone_country'],
            'phone_number' => $userData['phone_number'],
            'gender' => $userData['gender'],
            'birth_date' => $userData['birth_date'],
            'address' => $userData['address'],
            'zipcode' => $userData['zipcode'],
            'siret_number' => $userData['siret_number'],
            'compagny' => $userData['compagny'],
            'status' => '0',
            'ban' => false,
            'notification' => true,
            'archive' => false
        ]);

        DB::table('have_roles')->insert([
            'id_user' => $user->id,
            'id_role' => 4,
            'archive' => false,
            'created_at' => $this->now,
            'updated_at' => $this->now
        ]);

        return $user;
    }

    public function createAdmin(Request $request)
    {

        $userData = $request->json()->all();
        // Verifier les données

        $user = User::create([
            'name' => $userData['name'],
            'forname' => $userData['forname'],
            'email' => $userData['email'],
            'password' => $userData['password'],
            'phone_country' => $userData['phone_country'],
            'phone_number' => $userData['phone_number'],
            'gender' => $userData['gender'],
            'birth_date' => $userData['birth_date'],
            'address' => $userData['address'],
            'zipcode' => $userData['zipcode'],
            'status' => '0',
            'ban' => false,
            'notification' => true,
            'archive' => false
        ]);

        DB::table('have_roles')->insert([
            'id_user' => $user->id,
            'id_role' => 1,
            'archive' => false,
            'created_at' => $this->now,
            'updated_at' => $this->now
        ]);

        return $user;
    }

    public function getUsers(): Collection{
        return User::all();
    }
}
