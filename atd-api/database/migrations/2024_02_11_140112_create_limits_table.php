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
        Schema::create('limits', function (Blueprint $table) {
            $table->integer('max');
            $table->integer('min');
            $table->integer('count');
            $table->integer('id_role');
            $table->integer('id_activity');
            $table->foreign('id_role')->references('id')->on('roles')->onDelete('restrict');
            $table->foreign('id_activity')->references('id')->on('activities')->onDelete('restrict');
            $table->primary(['id_role', 'id_activity']);
            $table->boolean('archive')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('limited_by');
    }
};
