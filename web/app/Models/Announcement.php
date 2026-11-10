<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Announcement extends Model { protected $table = 'announcements'; protected $guarded = ['id']; protected $casts = ['is_pinned'=>'boolean','published_at'=>'datetime','expires_at'=>'datetime']; }
