<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Nicolaslopezj\Searchable\SearchableTrait;

class AudioPost extends Model
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
            'audio_posts.name' => 10,
            'audio_posts.description' => 5,
            'audio_posts.full_text' => 2,
        ],
        'joins' => [],
    ];

    protected $fillable = [
        'name',
        'src_url',
        'full_text',
        'description',
        'author_id',
        'uploader_id',
        'size',
        'length',
        'language',

    ];

    public function images()
    {
        return $this->morphToMany(Image::class, 'imageable', 'imageables');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function addresses()
    {
        return $this->morphToMany(Address::class, 'addressable', 'addressables');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function infoCards()
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
        return $this->hasMany(AudioSrc::class, 'audio_post_id');
    }
}
