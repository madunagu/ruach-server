<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'description',
        'avatar',
        'google_id',
        'is_admin',
        'is_minister',
        'is_verified',
        'is_editor',
        'is_public_profile',
        'created_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function following()
    {
        return $this->belongsToMany(User::class, 'user_followers', 'follower_id', 'user_id');
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'user_followers', 'user_id', 'follower_id');
    }

    public  function feeds()
    {
        return $this->hasMany(Feed::class, 'poster_id');
    }

    /** Threads this user takes part in. */
    public  function conversations()
    {
        return $this->belongsToMany(
            Conversation::class,
            'conversation_user',
            'user_id',
            'conversation_id'
        )->withPivot('last_read_message_id');
    }

    /** Messages this user has sent. */
    public  function messages()
    {
        return $this->hasMany(Message::class, 'user_id');
    }

    public function infoCard()
    {
        return $this->morphMany(InfoCard::class, 'info_cardable');
    }

    public function images()
    {
        return $this->morphToMany(Image::class, 'imageable', 'imageables');
    }

    public function likes()
    {
        return $this->hasMany(Like::class,'user_id');
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_user');
    }

    /**
     * Notifications addressed to this user.
     *
     * @see \App\Models\Notification
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'notifiable_id')
            ->where('notifiable_type', 'user');
    }

    /** The subset the client has not opened yet. */
    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }
}
