<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Nicolaslopezj\Searchable\SearchableTrait;

class Society extends Model
{
    use SearchableTrait, SoftDeletes, HasFactory;

    protected $searchable = [
        /**
         * Columns and their priority in search results.
         * Columns with higher values are more important.
         * Columns with equal values have equal importance.
         *
         * @var array
         */
        'columns' => [
            'societies.name' => 10,
            'societies.description' => 5,
        ],
        'joins' => [],
    ];

    protected $fillable = [
        'name', 'parent_id', 'closed', 'user_id', 'description',
    ];

    public function images()
    {
        return $this->morphToMany(Image::class, 'imageable', 'imageables');
    }


    public function churches()
    {
        return $this->morphToMany(Church::class, 'churchable');
    }

    public function hierarchies()
    {
        return $this->morphToMany(Hierarchy::class, 'hierarchyable', 'hierarchyables');
    }

    public function addresses()
    {
        return $this->morphToMany(Address::class, 'addressable', 'addressables');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function infoCard()
    {
        return $this->morphMany(InfoCard::class, 'info_cardable');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }


    public function poster()
    {
        return $this->morphTo('poster');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
