<?php
namespace App\Http\Requests\Announcement;
use Illuminate\Foundation\Http\FormRequest;
class UpdateAnnouncementRequest extends FormRequest {
 public function authorize():bool{return $this->user()?->role === 'admin';}
 public function rules():array{return ['title'=>['required','string','max:255'],'content'=>['required','string'],'image'=>['nullable','image','max:4096'],'type'=>['required','in:general,promotion,event,maintenance,important'],'target_audience'=>['required','in:all,customers,staff'],'status'=>['required','in:draft,scheduled,published,archived'],'is_pinned'=>['sometimes','boolean'],'published_at'=>['nullable','date'],'expires_at'=>['nullable','date','after:published_at']];}
}
