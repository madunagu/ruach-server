<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Nicolaslopezj\Searchable\SearchableTrait;

class Address extends Model
{
    use SearchableTrait, SoftDeletes,  HasFactory;
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
            'addresses.address1' => 10,
            'addresses.address2' => 5,
            'addresses.city' => 2,
            'addresses.postal_code' => 2,
            'addresses.name' => 1,
        ],

    ];
    protected $fillable = [
        'address1',
        'address2',
        'country',
        'state',
        'city',
        'postal_code',
        'name',
        'longitude',
        'latitude',
        'user_id'
    ];

    public function churches()
    {
        return $this->morphToMany(Church::class, 'churchable');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function toString()
    {
        return $this->address1 . $this->address2 . $this->city . $this->state;
    }
}
