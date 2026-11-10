<?php
namespace App\Http\Controllers\Customer;
use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
class ReviewController extends Controller {
 public function index(){return response()->json(Review::query()->latest()->paginate(20));}
 public function show(int $id){return response()->json(Review::findOrFail($id));}
}
