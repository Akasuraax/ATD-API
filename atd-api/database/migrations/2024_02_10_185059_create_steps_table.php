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
        Schema::create('steps', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->text('address');
            $table->string('zipcode', 5);
            $table->dateTime('time');
            $table->integer('id_journey')->nullable(false);
            $table->foreign('id_journey')->references('id')->on('journeys')->onDelete('restrict');
            $table->boolean('archive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('steps');
    }
};
