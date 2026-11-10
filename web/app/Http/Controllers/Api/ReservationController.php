<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
class ReservationController extends Controller {public function index(){return response()->json(Reservation::paginate(20));}}
