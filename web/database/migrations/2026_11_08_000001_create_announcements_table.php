<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void{Schema::create('announcements',function(Blueprint $table){$table->id();$table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();$table->string('title');$table->longText('content');$table->string('image')->nullable();$table->enum('type',['general','promotion','event','maintenance','important'])->default('general');$table->enum('target_audience',['all','customers','staff'])->default('all');$table->enum('status',['draft','scheduled','published','archived'])->default('draft');$table->boolean('is_pinned')->default(false);$table->timestamp('published_at')->nullable();$table->timestamp('expires_at')->nullable();$table->timestamps();});}public function down():void{Schema::dropIfExists('announcements');}};
