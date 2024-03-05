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
            $table->string('name', 255);
            $table->string('forname', 255);
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->string('phone_country', 4)->nullable();
            $table->string('phone_number', 15)->nullable();
            $table->integer('gender')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address');
            $table->string('zipcode', 5);
            $table->string('siret_number', 14)->nullable();
            $table->string('compagny')->nullable();
            $table->integer('status')->default(0);
            $table->boolean('ban')->default(false);
            $table->boolean('notification')->default(false);
            $table->boolean('archive')->default(false);
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
