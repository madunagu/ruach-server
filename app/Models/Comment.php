<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Comment extends Model
{
    use HasFactory;
    protected $fillable = [
        'commentable_id',
        'commentable_type',
        'comment',
        'parent_id',
        'user_id',
    ];

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function commentable()
    {
        return $this->morphTo();
    }

    // public function posts()
    // {
    //     return $this->morphedByMany(Post::class, 'taggable');
    // }
}
