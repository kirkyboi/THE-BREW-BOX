<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Product;
class AuthController extends Controller {public function index(){return response()->json(Product::paginate(20));}}
