<?php

namespace App\Http\Controllers\Volunteer;

use App\Models\User;
use Illuminate\Http\Request;

class UserController
{
    public function createUser(Request $request){

        $userData = $request->json()->all();

        return User::create([
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
    }
}
