<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Inventory;
use Illuminate\Http\Request;
class InventoryController extends Controller {
 public function index(){return response()->json(Inventory::query()->latest()->paginate(20));}
 public function show(int $id){return response()->json(Inventory::findOrFail($id));}
}
