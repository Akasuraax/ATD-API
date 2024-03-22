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
        Schema::create('activities', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->string('title', 255);
            $table->string('description', 255);
            $table->text('address')->nullable();
            $table->string('zipcode',5)->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->integer('donation')->nullable();
            $table->boolean('public')->default(false);
            $table->integer('id_type');
            $table->foreign('id_type')->references('id')->on('types')->onDelete('restrict');
            $table->boolean('archive')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
