<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Nicolaslopezj\Searchable\SearchableTrait;

class Church extends Model
{
    use SearchableTrait, SoftDeletes, HasFactory;

    /**
     * Searchable rules.
     *
     * @var array
     */
    protected $searchable = [
        /**
         * Columns and their priority in search results.
         * Columns with higher values are more important.
         * Columns with equal values have equal importance.
         *
         * @var array
         */
        'columns' => [
            'churches.name' => 10,
            'churches.alternate_name' => 5,
            'churches.slogan' => 2,
            'churches.description' => 1,
            'churches.parent_id' => 1,
            'churches.leader_id' => 1,
        ],
        // 'joins' => [
        //     'addresses' => ['churches.address_id', 'addresses.id'],
        //     'users' => ['churches.user_id', 'users.id'],
        // ],
    ];

    protected $fillable = [
        'name', 'alternate_name', 'parent_id', 'leader_id', 'user_id', 'slogan', 'description',

    ];

    public function addresses()
    {
        return $this->morphToMany(Address::class, 'addressable');
    }

    public function hierarchies()
    {
        return $this->morphToMany(Hierarchy::class, 'hierarchyable');
    }

    public function images()
    {
        return $this->morphToMany(Image::class, 'imageable', 'imageables');
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function infoCard()
    {
        return $this->morphMany(InfoCard::class, 'info_cardable');
    }

    public function churchable()
    {
        return $this->morphTo();
    }

    public function views()
    {
        return $this->morphMany(View::class, 'viewable');
    }
}
