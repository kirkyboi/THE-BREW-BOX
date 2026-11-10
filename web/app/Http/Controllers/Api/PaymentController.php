<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Payment;
class PaymentController extends Controller {public function index(){return response()->json(Payment::paginate(20));}}
