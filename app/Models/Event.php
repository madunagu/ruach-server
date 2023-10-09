<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Nicolaslopezj\Searchable\SearchableTrait;

class Event extends Model
{
    use SearchableTrait, SoftDeletes, HasFactory;

    /**
     * Searchable rules.
     *
     * @var array
     */
    protected $searchable = [
        /**
         ** Columns and their priority in search results.
         * Columns with higher values are more portant.
         * Columns with equal values have equal importance.
         *
         * @var array
         */
        'columns' => [
            'events.name' => 10,
            'events.description' => 5,
        ],
        'joins' => [],
    ];

    protected $fillable = [
        'name', 'starting_at', 'ending_at', 'description', 'user_id',
    ];

    public function addresses()
    {
        return $this->morphToMany(Address::class, 'addressable', 'addressables');
    }

    public function hierarchies()
    {
        return $this->morphToMany(Hierarchy::class, 'hierarchyable');
    }

    public function poster()
    {
        return $this->morphTo('poster');
    }

    public function attendees()
    {
        return $this->belongsToMany(User::class, 'event_user');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function images()
    {
        return $this->morphToMany(Image::class, 'imageable', 'imageables');
    }

    public function church()
    {
        return $this->belongsTo(Church::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function churches()
    {
        return $this->morphToMany(Church::class, 'churchable', 'churchables');
    }

    public function views()
    {
        return $this->morphMany(View::class, 'viewable');
    }
}
