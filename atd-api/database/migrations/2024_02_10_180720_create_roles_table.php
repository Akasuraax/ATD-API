<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->string('name', 255);
            $table->boolean('archive')->default(false);
            $table->integer('role_id')->nullable();
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->timestamps();
        });

        DB::table('roles')->insert([
            ['name' => 'admin', 'role_id' => null , 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()], //1
            ['name' => 'volunteer', 'role_id' => null , 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()], //2
            ['name' => 'beneficiary', 'role_id' => null , 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()], //3
            ['name' => 'partner', 'role_id' => null , 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()], //4
            ['name' => 'support', 'role_id' => null , 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()], //5
            ['name' => 'super_support', 'role_id' => null, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()], //6
            ['name' => 'driver', 'role_id' => 2, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()], //7
            ['name' => 'teacher', 'role_id' => 2, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()], //8
        ]);


        DB::table('users')->insert([
            'name' => 'anonymous',
            'forname' => 'anonymous',
            'email' => 'autempsdonne@example.com',
            'password' => 'motdepasse123',
            'phone_country' => '+33',
            'phone_number' => '123456789',
            'address' => 'Rue de Guise',
            'zipcode' => '02100',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
