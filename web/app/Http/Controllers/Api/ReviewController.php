<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Review;
class ReviewController extends Controller {public function index(){return response()->json(Review::paginate(20));}}
