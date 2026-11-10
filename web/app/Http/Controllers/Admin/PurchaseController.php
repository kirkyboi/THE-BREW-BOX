<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Illuminate\Http\Request;
class PurchaseController extends Controller {
 public function index(){return response()->json(Purchase::query()->latest()->paginate(20));}
 public function show(int $id){return response()->json(Purchase::findOrFail($id));}
}
