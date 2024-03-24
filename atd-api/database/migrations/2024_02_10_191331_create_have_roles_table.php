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
        Schema::create('have_roles', function (Blueprint $table) {
            $table->integer('id_user');
            $table->integer('id_role');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('id_role')->references('id')->on('roles')->onDelete('restrict');
            $table->primary(['id_user', 'id_role']);
            $table->boolean('archive')->default(false);
            $table->timestamps();
        });

        DB::table('have_roles')->insert([
            'id_user' => 1,
            'id_role' => 2,
            'archive' => false,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('have_have_roles');
    }
};
