<?php
namespace App\Services;
use App\Models\Announcement;
use Illuminate\Database\Eloquent\Builder;
class AnnouncementService {
 public function visible(): Builder { return Announcement::query()->where('status','published')->where(function($q){$q->whereNull('published_at')->orWhere('published_at','<=',now());})->where(function($q){$q->whereNull('expires_at')->orWhere('expires_at','>',now());})->whereIn('target_audience',['all','customers'])->orderByDesc('is_pinned')->latest(); }
}
