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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('description')->nullable();

            $table->string('avatar')->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->string('phone')->nullable();
            $table->enum('gender', ['M', 'F'])->nullable();
            $table->boolean('is_minister')->default(0);
            $table->boolean('is_verified')->default(0);
            $table->boolean('is_editor')->default(0);

            $table->rememberToken();
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
