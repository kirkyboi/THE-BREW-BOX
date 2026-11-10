<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Recipe;
use Illuminate\Http\Request;
class RecipeController extends Controller {
 public function index(){return response()->json(Recipe::query()->latest()->paginate(20));}
 public function show(int $id){return response()->json(Recipe::findOrFail($id));}
}
