<?php
namespace App\Http\Controllers\Customer;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
class PaymentController extends Controller {
 public function index(){return response()->json(Payment::query()->latest()->paginate(20));}
 public function show(int $id){return response()->json(Payment::findOrFail($id));}
}
