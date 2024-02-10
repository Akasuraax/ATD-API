<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('have_roles', function (Blueprint $table) {
            $table->integer('id_user')->nullable(false);
            $table->integer('id_role')->nullable(false);
            $table->foreign('id_user')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('id_role')->references('id')->on('roles')->onDelete('restrict');
            $table->primary(['id_user', 'id_role']);
            $table->boolean('archive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('have_have_roles');
    }
};
