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
            $table->timestamps();
        });

        DB::table('roles')->insert([
            ['name' => 'admin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'volunteer', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'beneficiary', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'partner', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'support', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]

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
