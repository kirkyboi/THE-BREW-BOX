<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
class ExpenseController extends Controller {
 public function index(){return response()->json(Expense::query()->latest()->paginate(20));}
 public function show(int $id){return response()->json(Expense::findOrFail($id));}
}
