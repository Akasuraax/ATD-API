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
        Schema::create('participates', function (Blueprint $table) {
            $table->integer('count');
            $table->integer('id_user');
            $table->integer('id_activity');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('id_activity')->references('id')->on('activities')->onDelete('restrict');
            $table->primary(['id_user', 'id_activity']);
            $table->boolean('archive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participate');
    }
};
