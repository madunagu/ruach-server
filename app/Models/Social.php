<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Social extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = ['facebook_url','twitter_url','instagram_url','youtube_url','rss_url','snapchat_url','website_url'];
}
