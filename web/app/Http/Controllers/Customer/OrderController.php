<?php
namespace App\Http\Controllers\Customer;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
class OrderController extends Controller {
 public function index(){return response()->json(Order::query()->latest()->paginate(20));}
 public function show(int $id){return response()->json(Order::findOrFail($id));}
}
