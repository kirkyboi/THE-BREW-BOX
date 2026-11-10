<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Services\AnnouncementService;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use Illuminate\Http\Request;
class AnnouncementController extends Controller {
 public function index(AnnouncementService $service){return response()->json($service->visible()->paginate(12));}
 public function show(Announcement $announcement,AnnouncementService $service){abort_unless($service->visible()->whereKey($announcement->id)->exists(),404);return response()->json($announcement);}
 public function markRead(Request $request,Announcement $announcement,AnnouncementService $service){abort_unless($service->visible()->whereKey($announcement->id)->exists(),404);AnnouncementRead::firstOrCreate(['user_id'=>$request->user()->id,'announcement_id'=>$announcement->id],['read_at'=>now()]);return response()->json(['message'=>'Marked as read']);}
}
