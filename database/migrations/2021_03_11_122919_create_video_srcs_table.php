<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVideoSrcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('video_srcs', function (Blueprint $table) {
            $table->id();
            $table->string('src');
            $table->integer('quality');
            $table->integer('size');
            $table->integer('length');
            $table->string('format');
            $table->string('dimensions')->nullable();
            $table->integer('video_post_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('video_srcs');
    }
}
