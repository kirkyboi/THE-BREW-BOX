<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Promotion;
class PromotionController extends Controller {public function index(){return response()->json(Promotion::paginate(20));}}
