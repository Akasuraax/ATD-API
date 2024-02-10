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
        Schema::create('users', function(Blueprint $table){
            $table->id()->autoIncrement();
            $table->string('mail');
            $table->string('password', 255);
            $table->string('phone_country', 4);
            $table->string('phone_number', 15);
            $table->char('gender');
            $table->date('birth_date');
            $table->text('address');
            $table->string('zipcode', 5);
            $table->boolean('ban');
            $table->boolean('notification');
            $table->boolean('archive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
