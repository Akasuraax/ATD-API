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
        Schema::create('types', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->string('name', 128);
            $table->text('description')->nullable();
            $table->string('color', 7)->nullable();
            $table->string('image')->nullable();
            $table->boolean('display');
            $table->boolean('access_to_warehouse');
            $table->boolean('access_to_journey');
            $table->boolean('archive')->default(false);;
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('types');
    }
};
