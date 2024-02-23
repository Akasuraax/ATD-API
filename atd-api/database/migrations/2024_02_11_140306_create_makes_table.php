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
        Schema::create('makes', function (Blueprint $table) {
            $table->integer('id_recipe');
            $table->integer('id_product');
            $table->foreign('id_recipe')->references('id')->on('recipes')->onDelete('restrict');
            $table->foreign('id_product')->references('id')->on('products')->onDelete('restrict');
            $table->primary(['id_recipe', 'id_product']);
            $table->double('count');
            $table->string('measure')->nullable();
            $table->boolean('archive')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('made_of');
    }
};
