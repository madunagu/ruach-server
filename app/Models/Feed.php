<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Feed extends Model
{
    protected $fillable = ['parentable_type', 'postable_type', 'postable_id', 'parentable_id'];

    public function parentable()
    {
        return $this->morphTo('parentable');
    }

    public function poster()
    {
        return $this->morphTo('postable');
    }
}
