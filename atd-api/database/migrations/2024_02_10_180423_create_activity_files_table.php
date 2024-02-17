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
        Schema::create('activity_files', function (Blueprint $table) {
            $table->integer('id_file');
            $table->integer('id_activity');
            $table->foreign('id_file')->references('id')->on('files')->onDelete('restrict');
            $table->foreign('id_activity')->references('id')->on('activities')->onDelete('restrict');
            $table->primary(['id_file', 'id_activity']);
            $table->boolean('archive')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_files');
    }
};
