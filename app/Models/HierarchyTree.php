<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nicolaslopezj\Searchable\SearchableTrait;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class HierarchyTree extends Model
{
    use SearchableTrait, SoftDeletes, HasFactory;
    
    protected $fillable = ['name','user_id'];
}
