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
        Schema::create('gives', function (Blueprint $table) {
            $table->integer('count');
            $table->integer('id_product')->nullable(false);
            $table->integer('id_activity')->nullable(false);
            $table->foreign('id_product')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('id_activity')->references('id')->on('activities')->onDelete('restrict');
            $table->primary(['id_product', 'id_activity']);
            $table->boolean('archive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('give_to');
    }
};
