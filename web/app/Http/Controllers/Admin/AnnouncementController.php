<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Http\Requests\Announcement\UpdateAnnouncementRequest;
use Illuminate\Support\Facades\Storage;
class AnnouncementController extends Controller {
 public function index(){return view('admin.announcements.index',['announcements'=>Announcement::latest()->paginate(15)]);}
 public function create(){return view('admin.announcements.create');}
 public function store(StoreAnnouncementRequest $request){$data=$request->validated();if($request->hasFile('image'))$data['image']=$request->file('image')->store('announcements','public');$data['user_id']=auth()->id();Announcement::create($data);return redirect()->route('admin.announcements.index')->with('success','Announcement created');}
 public function show(Announcement $announcement){return view('admin.announcements.show',compact('announcement'));}
 public function edit(Announcement $announcement){return view('admin.announcements.edit',compact('announcement'));}
 public function update(UpdateAnnouncementRequest $request,Announcement $announcement){$data=$request->validated();if($request->hasFile('image')){$data['image']=$request->file('image')->store('announcements','public');if($announcement->image)Storage::disk('public')->delete($announcement->image);}$announcement->update($data);return redirect()->route('admin.announcements.index')->with('success','Announcement updated');}
 public function destroy(Announcement $announcement){if($announcement->image)Storage::disk('public')->delete($announcement->image);$announcement->delete();return back()->with('success','Announcement deleted');}
 public function publish(Announcement $announcement){$announcement->update(['status'=>'published','published_at'=>now()]);return back()->with('success','Announcement published');}
}
