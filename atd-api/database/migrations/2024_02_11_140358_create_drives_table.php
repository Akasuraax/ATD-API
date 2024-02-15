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
        Schema::create('drives', function (Blueprint $table) {
            $table->integer('id_journey');
            $table->integer('id_vehicle');
            $table->foreign('id_journey')->references('id')->on('journeys')->onDelete('restrict');
            $table->foreign('id_vehicle')->references('id')->on('vehicles')->onDelete('restrict');
            $table->primary(['id_journey', 'id_vehicle']);
            $table->boolean('archive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('need_to_drive_with');
    }
};
