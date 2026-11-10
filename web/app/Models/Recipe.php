<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Recipe extends Model { protected $table = 'recipes'; protected $guarded = ['id']; protected $casts = []; }
