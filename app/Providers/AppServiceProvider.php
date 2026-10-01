<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'address' => 'App\Models\Address',
            'audio' => 'App\Models\AudioPost',
            'church' => 'App\Models\Church',
            'comment' => 'App\Models\Comment',
            // Feeds store 'devotional' in parentable_type; without this alias
            // resolving that morph throws "Class devotional not found" and the
            // whole feed request fails.
            'devotional' => 'App\Models\Devotional',
            'event' => 'App\Models\Event',
            'info_card' => 'App\Models\InfoCard',
            'like' => 'App\Models\Like',
            'post' => 'App\Models\Post',
            'society' => 'App\Models\Society',
            'user' => 'App\Models\User',
            'video' => 'App\Models\VideoPost',
            'playlist' => 'App\Models\Playlist',
        ]);
        Schema::defaultStringLength(191);
    }
}
