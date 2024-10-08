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
        Schema::create('demands', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->text('description');
            $table->integer('status')->default(0);
            $table->integer('id_user');
            $table->integer('id_type');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('id_type')->references('id')->on('types')->onDelete('restrict');
            $table->boolean('archive')->default(false);;
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demands');
    }
};
