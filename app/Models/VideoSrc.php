<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoSrc extends Model
{
    protected $fillable = ['src', 'length', 'quality', 'format','dimensions', 'size', 'video_post_id',];
}
