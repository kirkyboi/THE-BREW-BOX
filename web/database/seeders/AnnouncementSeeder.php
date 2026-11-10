<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Announcement;
class AnnouncementSeeder extends Seeder {public function run():void{Announcement::firstOrCreate(['title'=>'Welcome to THE BREW BOX'],['content'=>'Check back here for events, promotions, and coffee updates.','type'=>'general','target_audience'=>'all','status'=>'published','published_at'=>now()]);}}
