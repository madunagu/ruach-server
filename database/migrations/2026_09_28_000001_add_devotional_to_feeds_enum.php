<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feeds', function (Blueprint $table) {
            $table->enum('parentable_type', ['post', 'video', 'audio', 'text', 'event', 'devotional'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('feeds', function (Blueprint $table) {
            $table->enum('parentable_type', ['post', 'video', 'audio', 'text', 'event'])->change();
        });
    }
};
