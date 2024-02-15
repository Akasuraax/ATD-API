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
        Schema::create('can_have_circuits', function (Blueprint $table) {
            $table->integer('id_journey');
            $table->integer('id_activity');
            $table->foreign('id_journey')->references('id')->on('journeys')->onDelete('restrict');
            $table->foreign('id_activity')->references('id')->on('activities')->onDelete('restrict');
            $table->primary(['id_journey', 'id_activity']);
            $table->boolean('archive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('can_have_circuits');
    }
};
