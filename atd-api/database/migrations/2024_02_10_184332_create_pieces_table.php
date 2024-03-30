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
        Schema::create('pieces', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->dateTime('expired_date')->nullable();
            $table->double('count');
            $table->integer('location')->nullable();
            $table->integer('id_warehouse');
            $table->integer('id_product');
            $table->foreign('id_warehouse')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('id_product')->references('id')->on('products')->onDelete('restrict');
            $table->boolean('archive')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pieces');
    }
};
