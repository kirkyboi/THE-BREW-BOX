<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
class RoleController extends Controller {
 public function index(){return response()->json(Role::query()->latest()->paginate(20));}
 public function show(int $id){return response()->json(Role::findOrFail($id));}
}
