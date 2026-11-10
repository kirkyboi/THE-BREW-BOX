<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
class UserController extends Controller {
 public function index(){return response()->json(User::query()->latest()->paginate(20));}
 public function show(int $id){return response()->json(User::findOrFail($id));}
}
