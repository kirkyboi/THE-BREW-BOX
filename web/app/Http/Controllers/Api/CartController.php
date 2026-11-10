<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Cart;
class CartController extends Controller {public function index(){return response()->json(Cart::paginate(20));}}
