<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class InfoCard extends Model
{
    public function infoCardable()
    {
        return $this->morphTo();
    }
}
