<?php
namespace App\Http\Controllers\Customer;
use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;
class PromotionController extends Controller {
 public function index(){return response()->json(Promotion::query()->latest()->paginate(20));}
 public function show(int $id){return response()->json(Promotion::findOrFail($id));}
}
