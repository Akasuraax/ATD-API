<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //Create partner
        for ($i = 0; $i < 10; $i++) {
            $user = User::create([
                'name' => 'John' . $i,
                'forname' => 'Doe' . $i,
                'email' => 'user' . $i . '@example.com',
                'password' => 'password123', // Utilisation de Hash::make pour hasher le mot de passe
                'phone_country' => '+33',
                'phone_number' => '123456789',
                'gender' => '1',
                'birth_date' => '1990-01-01',
                'address' => '123 Street',
                'zipcode' => '12345',
                'siret_number' => '12345678901234',
                'compagny' => 'Company' . $i,
            ]);

            $user->roles()->attach(4);
        }

        //Create administrator

        for ($i = 0; $i < 10; $i++) {
            $user = User::create([
                'name' => 'Frederic' . $i,
                'forname' => 'Sananes' . $i,
                'email' => 'sananes' . $i . '@example.com',
                'password' => 'motdepasse123', // Utilisation de Hash::make pour hasher le mot de passe
                'phone_country' => '+33',
                'phone_number' => '123456789',
                'gender' => '0',
                'birth_date' => '1990-01-01',
                'address' => '123 Street',
                'zipcode' => '12345',
            ]);

            $user->roles()->attach(1);
        }

        //Create volunteer

        for ($i = 0; $i < 10; $i++) {
            $user = User::create([
                'name' => 'Christophe' . $i,
                'forname' => 'Delon' . $i,
                'email' => 'delon' . $i . '@example.com',
                'password' => 'motdepasse123', // Utilisation de Hash::make pour hasher le mot de passe
                'phone_country' => '+33',
                'phone_number' => '123456789',
                'gender' => '0',
                'birth_date' => '1990-01-01',
                'address' => '123 Street',
                'zipcode' => '12345',
            ]);

            $user->roles()->attach(2);
        }

        //Create beneficiary

        for ($i = 0; $i < 10; $i++) {
            $user = User::create([
                'name' => 'Neveu' . $i,
                'forname' => 'Nicolas' . $i,
                'email' => 'neveu' . $i . '@example.com',
                'password' => 'motdepasse123', // Utilisation de Hash::make pour hasher le mot de passe
                'phone_country' => '+33',
                'phone_number' => '123456789',
                'gender' => '0',
                'birth_date' => '1990-01-01',
                'address' => '123 Street',
                'zipcode' => '12345',
            ]);

            $user->roles()->attach(3);
        }

        //Create support

        for ($i = 0; $i < 10; $i++) {
            $user = User::create([
                'name' => 'Lina' . $i,
                'forname' => 'Phe' . $i,
                'email' => 'phe' . $i . '@example.com',
                'password' => 'motdepasse123', // Utilisation de Hash::make pour hasher le mot de passe
                'phone_country' => '+33',
                'phone_number' => '123456789',
                'gender' => '1',
                'birth_date' => '1990-01-01',
                'address' => '123 Street',
                'zipcode' => '12345',
            ]);

            $user->roles()->attach(5);
        }
    }
}
