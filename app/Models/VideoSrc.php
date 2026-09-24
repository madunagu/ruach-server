<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoSrc extends Model
{
    protected $fillable = ['src', 'length', 'quality', 'format','dimensions', 'width', 'height', 'variant', 'mime', 'status', 'size', 'video_post_id',];
}
