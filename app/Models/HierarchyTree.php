<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nicolaslopezj\Searchable\SearchableTrait;

use Illuminate\Database\Eloquent\Factories\HasFactory;

//I have deprecated this class
//Will soon be removed
class HierarchyTree extends Model
{
    use SearchableTrait, SoftDeletes, HasFactory;
    
    protected $fillable = ['name','user_id'];
}
