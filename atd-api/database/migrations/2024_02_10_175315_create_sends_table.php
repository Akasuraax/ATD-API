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
        Schema::create('send', function (Blueprint $table) {
            $table->integer('id_user')->nullable(false);
            $table->foreign('id_user')->references('id')->on('users')->onDelete('restrict');
            $table->integer('id_ticket')->nullable(false);
            $table->foreign('id_ticket')->references('id')->on('tickets')->onDelete('restrict');
            $table->primary(['id_user', 'id_ticket']);
            $table->boolean('archive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('send');
    }
};
