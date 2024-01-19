<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'body',
        'user_id',
        'poster_id',
        'poster_type',
    ];

    public function images()
    {
        return $this->morphToMany(Image::class, 'imageable', 'imageables');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function poster()
    {
        return $this->morphTo('poster');
    }

    public function hierarchies()
    {
        return $this->morphToMany(Hierarchy::class, 'hierarchyable', 'hierarchyables');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function addresses()
    {
        return $this->morphToMany(Address::class, 'addressable', 'addressables');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }
    public function views()
    {
        return $this->morphMany(View::class, 'viewable');
    }

    public function churches()
    {
        return $this->morphToMany(Church::class, 'churchable');
    }
}
