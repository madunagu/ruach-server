<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

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
            'event_invite' => 'App\Models\Event',
            'info_card' => 'App\Models\InfoCard',
            'like' => 'App\Models\Like',
            'post' => 'App\Models\Post',
            'society' => 'App\Models\Society',
            'user' => 'App\Models\User',
            'video' => 'App\Models\VideoPost',
        ]);
    }
}
