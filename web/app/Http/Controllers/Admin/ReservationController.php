<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
class ReservationController extends Controller {
 public function index(){return response()->json(Reservation::query()->latest()->paginate(20));}
 public function show(int $id){return response()->json(Reservation::findOrFail($id));}
}
