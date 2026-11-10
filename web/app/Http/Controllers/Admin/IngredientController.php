<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use Illuminate\Http\Request;
class IngredientController extends Controller {
 public function index(){return response()->json(Ingredient::query()->latest()->paginate(20));}
 public function show(int $id){return response()->json(Ingredient::findOrFail($id));}
}
