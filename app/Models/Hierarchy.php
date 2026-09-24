<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Searchable as SearchableTrait;

class Hierarchy extends Model
{
   use SearchableTrait, HasFactory;

    protected $searchable = [
        'columns' => [
            'hierarchies.name' => 10,
        ],
        'joins' => [],
    ];

    protected $fillable = ['rank', 'name', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
