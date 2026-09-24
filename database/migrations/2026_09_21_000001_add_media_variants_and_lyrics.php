<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audio_srcs', function (Blueprint $table) {
            if (!Schema::hasColumn('audio_srcs', 'variant')) {
                $table->string('variant')->default('original')->after('format');
            }
            if (!Schema::hasColumn('audio_srcs', 'mime')) {
                $table->string('mime')->nullable()->after('variant');
            }
            if (!Schema::hasColumn('audio_srcs', 'status')) {
                $table->string('status')->default('ready')->after('mime');
            }
        });

        Schema::table('video_srcs', function (Blueprint $table) {
            if (!Schema::hasColumn('video_srcs', 'variant')) {
                $table->string('variant')->default('original')->after('format');
            }
            if (!Schema::hasColumn('video_srcs', 'width')) {
                $table->unsignedInteger('width')->nullable()->after('dimensions');
            }
            if (!Schema::hasColumn('video_srcs', 'height')) {
                $table->unsignedInteger('height')->nullable()->after('width');
            }
            if (!Schema::hasColumn('video_srcs', 'mime')) {
                $table->string('mime')->nullable()->after('height');
            }
            if (!Schema::hasColumn('video_srcs', 'status')) {
                $table->string('status')->default('ready')->after('mime');
            }
        });

        Schema::table('audio_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('audio_posts', 'lyrics_status')) {
                $table->string('lyrics_status')->default('pending')->after('full_text');
            }
            if (!Schema::hasColumn('audio_posts', 'media_status')) {
                $table->string('media_status')->default('ready')->after('lyrics_status');
            }
        });

        Schema::table('video_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('video_posts', 'lyrics_status')) {
                $table->string('lyrics_status')->default('pending')->after('full_text');
            }
            if (!Schema::hasColumn('video_posts', 'media_status')) {
                $table->string('media_status')->default('ready')->after('lyrics_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audio_srcs', function (Blueprint $table) {
            $table->dropColumn(['variant', 'mime', 'status']);
        });
        Schema::table('video_srcs', function (Blueprint $table) {
            $table->dropColumn(['variant', 'width', 'height', 'mime', 'status']);
        });
        Schema::table('audio_posts', function (Blueprint $table) {
            $table->dropColumn(['lyrics_status', 'media_status']);
        });
        Schema::table('video_posts', function (Blueprint $table) {
            $table->dropColumn(['lyrics_status', 'media_status']);
        });
    }
};
