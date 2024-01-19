<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Nicolaslopezj\Searchable\SearchableTrait;

class Devotional extends Model
{
    use SearchableTrait, SoftDeletes, HasFactory;

    protected $fillable = ['title', 'opening_prayer', 'closing_prayer', 'body', 'memory_verse', 'day', 'poster_id', 'poster_type', 'user_id'];

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function devotees()
    {
        return $this->belongsToMany(User::class, 'devotional_user');
    }


    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function hierarchies()
    {
        return $this->morphToMany(Hierarchy::class, 'hierarchyable', 'hierarchyables');
    }

    public function poster()
    {
        return $this->morphTo('poster');
    }

    public function views()
    {
        return $this->morphMany(View::class, 'viewable');
    }

    public function churches()
    {
        return $this->morphToMany(Church::class, 'churchable', 'churchables');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
