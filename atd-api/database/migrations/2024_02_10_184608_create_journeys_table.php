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
        Schema::create('journeys', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->string('name', 255);
            $table->integer('duration')->default(0);
            $table->integer('distance')->default(0);
            $table->integer('cost')->default(0);
            $table->integer('fuel_cost')->default(0);
            $table->integer('id_activity')->nullable();
            $table->foreign('id_activity')->references('id')->on('activities')->onDelete('restrict');
            $table->boolean('archive')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journeys');
    }
};
