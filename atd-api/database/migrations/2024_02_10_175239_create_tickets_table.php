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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->string('title', 255);
            $table->text('description');
            $table->integer('status')->default(0);
            $table->integer('severity')->default(0);
            $table->boolean('archive')->default(false);
            $table->timestamps();
            $table->integer('problem_id');
            $table->foreign('problem_id')->references('id')->on('problems')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
