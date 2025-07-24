<?php

use App\Http\Controllers\TimerController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\IssueController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API routes - protected by web session authentication
Route::middleware(['web', 'auth'])->group(function () {
    // Timer API routes
    Route::get('/timers/active', [TimerController::class, 'active']);
    Route::get('/timers/status', [TimerController::class, 'status']);
    Route::get('/timers/recent-entries', [TimerController::class, 'recentEntries']);
    Route::post('/timers/start', [TimerController::class, 'start']);
    Route::post('/timers/pause', [TimerController::class, 'pause']);
    Route::post('/timers/resume', [TimerController::class, 'resume']);
    Route::post('/timers/stop', [TimerController::class, 'stop']);
    Route::post('/timers/sync', [TimerController::class, 'sync']);
    
    // Project API routes
    Route::apiResource('projects', ProjectController::class);
    
    // Issue API routes
    Route::apiResource('issues', IssueController::class);
});
