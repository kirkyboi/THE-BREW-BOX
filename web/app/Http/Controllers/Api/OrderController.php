<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Order;
class OrderController extends Controller {public function index(){return response()->json(Order::paginate(20));}}
