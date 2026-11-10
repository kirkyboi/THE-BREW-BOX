<?php
namespace App\Http\Controllers\Customer;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;
class CartController extends Controller {
 public function index(){return response()->json(Cart::query()->latest()->paginate(20));}
 public function show(int $id){return response()->json(Cart::findOrFail($id));}
}
