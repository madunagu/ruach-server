<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AudioSrc extends Model
{
    protected $fillable = [
        'refresh_rate',
        'length',
        'bitrate', 'src', 'size', 'format', 'variant', 'mime', 'status', 'audio_post_id',
    ];
}
