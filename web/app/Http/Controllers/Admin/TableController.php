<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\Request;
class TableController extends Controller {
 public function index(){return response()->json(Table::query()->latest()->paginate(20));}
 public function show(int $id){return response()->json(Table::findOrFail($id));}
}
