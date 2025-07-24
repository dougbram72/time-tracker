<?php

use App\Models\User;
use App\Models\Project;
use App\Models\Timer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can sync timer state from client to server', function () {
    $user = User::factory()->create();
    
    // Create project manually
    $project = Project::create([
        'user_id' => $user->id,
        'name' => 'Test Project',
        'description' => 'Test project description',
        'color' => '#3B82F6'
    ]);
    
    // Create a running timer
    $timer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => $project->id,
        'status' => 'running',
        'started_at' => now()->subMinutes(5)
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/timers/sync', [
            'timer_id' => $timer->id,
            'status' => 'paused',
            'elapsed_seconds' => 300 // 5 minutes
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Timer state synchronized successfully'
        ]);

    // Verify timer was paused
    $timer->refresh();
    expect($timer->status)->toBe('paused');
});

test('user can sync running timer with elapsed time', function () {
    $user = User::factory()->create();
    
    $project = Project::create([
        'user_id' => $user->id,
        'name' => 'Test Project',
        'description' => 'Test project description',
        'color' => '#3B82F6'
    ]);
    
    $timer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => $project->id,
        'status' => 'paused'
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/timers/sync', [
            'timer_id' => $timer->id,
            'status' => 'running',
            'elapsed_seconds' => 600 // 10 minutes
        ]);

    $response->assertStatus(200);

    // Verify timer is running and started_at was adjusted
    $timer->refresh();
    expect($timer->status)->toBe('running');
    
    // The started_at should be approximately 10 minutes ago
    $expectedStartTime = now()->subSeconds(600);
    expect($timer->started_at->timestamp)
        ->toBeGreaterThan($expectedStartTime->timestamp - 5)
        ->toBeLessThan($expectedStartTime->timestamp + 5);
});

test('user can stop timer via sync', function () {
    $user = User::factory()->create();
    
    $project = Project::create([
        'user_id' => $user->id,
        'name' => 'Test Project',
        'description' => 'Test project description',
        'color' => '#3B82F6'
    ]);
    
    $timer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => $project->id,
        'status' => 'running',
        'started_at' => now()->subMinutes(10)
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/timers/sync', [
            'timer_id' => $timer->id,
            'status' => 'stopped'
        ]);

    $response->assertStatus(200);

    // Verify timer was stopped and time entry was created
    $timer->refresh();
    expect($timer->status)->toBe('stopped');
    
    // Should have created a time entry
    $this->assertDatabaseHas('time_entries', [
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => $project->id
    ]);
});

test('sync validates request data', function () {
    $user = User::factory()->create();
    
    $project = Project::create([
        'user_id' => $user->id,
        'name' => 'Test Project',
        'description' => 'Test project description',
        'color' => '#3B82F6'
    ]);
    
    $timer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => $project->id,
        'status' => 'running'
    ]);

    // Missing timer_id
    $response = $this->actingAs($user)
        ->postJson('/api/timers/sync', [
            'status' => 'paused'
        ]);
    $response->assertStatus(422);

    // Invalid status
    $response = $this->actingAs($user)
        ->postJson('/api/timers/sync', [
            'timer_id' => $timer->id,
            'status' => 'invalid_status'
        ]);
    $response->assertStatus(422);

    // Negative elapsed seconds
    $response = $this->actingAs($user)
        ->postJson('/api/timers/sync', [
            'timer_id' => $timer->id,
            'status' => 'running',
            'elapsed_seconds' => -100
        ]);
    $response->assertStatus(422);
});

test('sync prevents accessing other users timers', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    
    $otherProject = Project::create([
        'user_id' => $otherUser->id,
        'name' => 'Other Project',
        'description' => 'Other user project',
        'color' => '#EF4444'
    ]);
    
    $otherTimer = Timer::create([
        'user_id' => $otherUser->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => $otherProject->id,
        'status' => 'running'
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/timers/sync', [
            'timer_id' => $otherTimer->id,
            'status' => 'paused'
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'error' => 'Timer not found or access denied'
        ]);
});

test('sync handles non existent timer', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->postJson('/api/timers/sync', [
            'timer_id' => 99999,
            'status' => 'paused'
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'error' => 'Timer not found or access denied'
        ]);
});

test('unauthenticated user cannot sync timer', function () {
    $user = User::factory()->create();
    
    $project = Project::create([
        'user_id' => $user->id,
        'name' => 'Test Project',
        'description' => 'Test project description',
        'color' => '#3B82F6'
    ]);
    
    $timer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => $project->id,
        'status' => 'running'
    ]);

    $response = $this->postJson('/api/timers/sync', [
        'timer_id' => $timer->id,
        'status' => 'paused'
    ]);

    $response->assertStatus(401);
});
