<?php

use App\Models\Timer;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('timer can be created with required fields', function () {
    $user = User::factory()->create();
    
    $timer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => 1,
        'status' => Timer::STATUS_STOPPED
    ]);
    
    expect($timer)->toBeInstanceOf(Timer::class)
        ->and($timer->user_id)->toBe($user->id)
        ->and($timer->trackable_type)->toBe('App\\Models\\Project')
        ->and($timer->trackable_id)->toBe(1)
        ->and($timer->status)->toBe(Timer::STATUS_STOPPED)
        ->and($timer->elapsed_seconds)->toBe(0);
});

test('timer can be started', function () {
    $user = User::factory()->create();
    $timer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => 1,
        'status' => Timer::STATUS_STOPPED
    ]);
    
    $timer->start();
    
    expect($timer->status)->toBe(Timer::STATUS_RUNNING)
        ->and($timer->started_at)->not->toBeNull()
        ->and($timer->paused_at)->toBeNull()
        ->and($timer->stopped_at)->toBeNull();
});

test('timer can be paused', function () {
    $user = User::factory()->create();
    $timer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => 1,
        'status' => Timer::STATUS_STOPPED
    ]);
    
    $timer->start();
    sleep(1); // Wait 1 second
    $timer->pause();
    
    expect($timer->status)->toBe(Timer::STATUS_PAUSED)
        ->and($timer->paused_at)->not->toBeNull()
        ->and($timer->elapsed_seconds)->toBeGreaterThan(0);
});

test('timer can be resumed from paused state', function () {
    $user = User::factory()->create();
    $timer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => 1,
        'status' => Timer::STATUS_STOPPED
    ]);
    
    $timer->start();
    $timer->pause();
    $timer->resume();
    
    expect($timer->status)->toBe(Timer::STATUS_RUNNING)
        ->and($timer->paused_at)->toBeNull();
});

test('timer can be stopped and creates time entry', function () {
    $user = User::factory()->create();
    $timer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => 1,
        'status' => Timer::STATUS_STOPPED
    ]);
    
    $timer->start();
    sleep(1); // Wait 1 second
    $timeEntry = $timer->stop();
    
    expect($timer->status)->toBe(Timer::STATUS_STOPPED)
        ->and($timer->stopped_at)->not->toBeNull()
        ->and($timeEntry)->toBeInstanceOf(TimeEntry::class)
        ->and($timeEntry->user_id)->toBe($user->id)
        ->and($timeEntry->trackable_type)->toBe('App\\Models\\Project')
        ->and($timeEntry->trackable_id)->toBe(1)
        ->and($timeEntry->duration_seconds)->toBeGreaterThan(0);
});

test('timer calculates current elapsed seconds correctly', function () {
    $user = User::factory()->create();
    $timer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => 1,
        'status' => Timer::STATUS_STOPPED
    ]);
    
    $timer->start();
    sleep(1); // Wait 1 second
    
    $elapsed = $timer->getCurrentElapsedSeconds();
    expect($elapsed)->toBeGreaterThan(0);
});

test('timer is active when running or paused', function () {
    $user = User::factory()->create();
    $timer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => 1,
        'status' => Timer::STATUS_STOPPED
    ]);
    
    expect($timer->isActive())->toBeFalse();
    
    $timer->start();
    expect($timer->isActive())->toBeTrue();
    
    $timer->pause();
    expect($timer->isActive())->toBeTrue();
    
    $timer->stop();
    expect($timer->isActive())->toBeFalse();
});

test('active timer scope returns only active timers', function () {
    $user = User::factory()->create();
    
    $runningTimer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => 1,
        'status' => Timer::STATUS_RUNNING
    ]);
    
    $pausedTimer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => 2,
        'status' => Timer::STATUS_PAUSED
    ]);
    
    $stoppedTimer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => 3,
        'status' => Timer::STATUS_STOPPED
    ]);
    
    $activeTimers = Timer::activeForUser($user->id)->get();
    
    expect($activeTimers)->toHaveCount(2)
        ->and($activeTimers->pluck('id'))->toContain($runningTimer->id, $pausedTimer->id)
        ->and($activeTimers->pluck('id'))->not->toContain($stoppedTimer->id);
});

test('running timer scope returns only running timers', function () {
    $user = User::factory()->create();
    
    $runningTimer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => 1,
        'status' => Timer::STATUS_RUNNING
    ]);
    
    $pausedTimer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => 2,
        'status' => Timer::STATUS_PAUSED
    ]);
    
    $runningTimers = Timer::runningForUser($user->id)->get();
    
    expect($runningTimers)->toHaveCount(1)
        ->and($runningTimers->first()->id)->toBe($runningTimer->id);
});
