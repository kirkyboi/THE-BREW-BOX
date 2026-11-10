<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
class CustomerController extends Controller {
 public function index(){return response()->json(Customer::query()->latest()->paginate(20));}
 public function show(int $id){return response()->json(Customer::findOrFail($id));}
}
