<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hierarchy extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = ['rank', 'position_name', 'position_slang','hierarchy_tree_id']; //,'person_name','user_id'];
}
