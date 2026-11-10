<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
class ReportController extends Controller {
 public function index(){return response()->json(Product::query()->latest()->paginate(20));}
 public function show(int $id){return response()->json(Product::findOrFail($id));}
}
