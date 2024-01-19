<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nicolaslopezj\Searchable\SearchableTrait;

class VideoPost extends Model
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
            'video_posts.name' => 10,
            'video_posts.description' => 5,
            'video_posts.full_text' => 2,
        ],
        'joins' => [
           
        ],
    ];


    protected $fillable = [
        'name',
        'src_url',
        'full_text',
        'description',
        'author_id',
        'user_id',
        'poster_id',
        'poster_type',
        'size',
        'length',
        'language',
    ];

    public function images()
    {
        return $this->morphToMany(Image::class, 'imageable', 'imageables');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class,'taggable');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'uploader_id');
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

    public function views()
    {
        return $this->morphMany(View::class, 'viewable');
    }

    public function churches()
    {
        return $this->morphToMany(Church::class, 'churchable');
    }

    public function srcs()
    {
        return $this->hasMany(VideoSrc::class, 'video_post_id');
    }


    public function poster()
    {
        return $this->morphTo('poster');
    }

    public function hierarchies()
    {
        return $this->morphToMany(Hierarchy::class, 'hierarchyable', 'hierarchyables');
    }
}
