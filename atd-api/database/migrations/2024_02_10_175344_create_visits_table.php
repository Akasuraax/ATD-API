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
        Schema::create('visits', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->char('checking');
            $table->boolean('archive');
            $table->timestamps();
            $table->integer('id_volunteer')->nullable(false);
            $table->integer('id_beneficiary')->nullable(false);
            $table->foreign('id_volunteer')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('id_beneficiary')->references('id')->on('users')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
