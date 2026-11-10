<?php
namespace App\Http\Controllers\Customer;
use App\Http\Controllers\Controller;
use App\Services\AnnouncementService;
use App\Models\Announcement;
class AnnouncementController extends Controller {
 public function index(AnnouncementService $service){return view('customer.announcements.index',['announcements'=>$service->visible()->paginate(12)]);}
 public function show(Announcement $announcement,AnnouncementService $service){abort_unless($service->visible()->whereKey($announcement->id)->exists(),404);return view('customer.announcements.show',compact('announcement'));}
}
