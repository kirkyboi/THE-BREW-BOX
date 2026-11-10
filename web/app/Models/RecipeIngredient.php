<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RecipeIngredient extends Model { protected $table = 'recipe_ingredients'; protected $guarded = ['id']; protected $casts = []; }
