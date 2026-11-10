<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
class SupplierController extends Controller {
 public function index(){return response()->json(Supplier::query()->latest()->paginate(20));}
 public function show(int $id){return response()->json(Supplier::findOrFail($id));}
}
