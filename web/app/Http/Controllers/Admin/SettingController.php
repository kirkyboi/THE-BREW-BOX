<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
class SettingController extends Controller {
 public function index(){return response()->json(Setting::query()->latest()->paginate(20));}
 public function show(int $id){return response()->json(Setting::findOrFail($id));}
}
