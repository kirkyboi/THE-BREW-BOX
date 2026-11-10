<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AnnouncementController;
Route::get('/announcements',[AnnouncementController::class,'index']);
Route::get('/announcements/{announcement}',[AnnouncementController::class,'show']);
Route::middleware('auth:sanctum')->post('/announcements/{announcement}/read',[AnnouncementController::class,'markRead']);
