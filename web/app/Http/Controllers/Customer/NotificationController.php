<?php
namespace App\Http\Controllers\Customer;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
class NotificationController extends Controller {
 public function index(){return response()->json(Notification::query()->latest()->paginate(20));}
 public function show(int $id){return response()->json(Notification::findOrFail($id));}
}
