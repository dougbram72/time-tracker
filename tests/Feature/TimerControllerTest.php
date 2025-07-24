<?php

use App\Models\Timer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('user can get active timer when none exists', function () {
    $user = User::factory()->create();

    
    $response = $this->actingAs($user)->getJson('/api/timers/active');
    
    $response->assertStatus(200)
             ->assertJson(['timer' => null]);
});

test('user can get active timer when one exists', function () {
    $user = User::factory()->create();

    
    $timer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => 1,
        'status' => Timer::STATUS_RUNNING,
        'started_at' => now()
    ]);
    
    $response = $this->actingAs($user)->getJson('/api/timers/active');
    
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'timer' => ['id', 'user_id', 'trackable_type', 'trackable_id', 'status'],
                 'elapsed_seconds'
             ]);
});

test('user can start a new timer', function () {
    $user = User::factory()->create();
    $project = \App\Models\Project::create([
        'user_id' => $user->id,
        'name' => 'Test Project',
        'description' => 'A test project',
        'color' => '#3B82F6',
        'is_active' => true,
    ]);

    
    $response = $this->actingAs($user)->postJson('/api/timers/start', [
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => $project->id,
        'description' => 'Working on feature'
    ]);
    
    $response->assertStatus(201)
             ->assertJsonStructure([
                 'message',
                 'timer' => [
                     'id', 'status', 'description', 'started_at', 'elapsed_seconds',
                     'trackable' => ['type', 'id', 'name'],
                     'project' => ['id', 'name', 'color'],
                     'issue'
                 ]
             ]);
    
    $this->assertDatabaseHas('timers', [
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => $project->id,
        'project_id' => $project->id,
        'status' => Timer::STATUS_RUNNING
    ]);
});

test('starting new timer stops existing running timer', function () {
    $user = User::factory()->create();
    
    // Create project and issue for testing
    $project = \App\Models\Project::create([
        'user_id' => $user->id,
        'name' => 'Test Project',
        'description' => 'A test project',
        'color' => '#3B82F6',
        'is_active' => true,
    ]);
    
    $issue = \App\Models\Issue::create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Test Issue',
        'description' => 'A test issue',
        'priority' => 'medium',
        'status' => 'open',
        'is_active' => true,
    ]);
    

    
    // Create existing running timer
    $existingTimer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => $project->id,
        'project_id' => $project->id,
        'status' => Timer::STATUS_RUNNING,
        'started_at' => now()->subMinutes(5)
    ]);
    
    // Start new timer
    $response = $this->actingAs($user)->postJson('/api/timers/start', [
        'trackable_type' => 'App\\Models\\Issue',
        'trackable_id' => $issue->id
    ]);
    
    $response->assertStatus(201);
    
    // Check that existing timer was stopped and time entry created
    $existingTimer->refresh();
    expect($existingTimer->status)->toBe(Timer::STATUS_STOPPED);
    
    $this->assertDatabaseHas('time_entries', [
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => 1
    ]);
});

test('user can pause running timer', function () {
    $user = User::factory()->create();

    
    $timer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => 1,
        'status' => Timer::STATUS_RUNNING,
        'started_at' => now()
    ]);
    
    $response = $this->actingAs($user)->postJson('/api/timers/pause');
    
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'message',
                 'timer' => ['id', 'status'],
                 'elapsed_seconds'
             ]);
    
    $timer->refresh();
    expect($timer->status)->toBe(Timer::STATUS_PAUSED);
});

test('pause returns 404 when no running timer exists', function () {
    $user = User::factory()->create();

    
    $response = $this->actingAs($user)->postJson('/api/timers/pause');
    
    $response->assertStatus(404)
             ->assertJson(['message' => 'No running timer found']);
});

test('user can resume paused timer', function () {
    $user = User::factory()->create();

    
    $timer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => 1,
        'status' => Timer::STATUS_PAUSED,
        'paused_at' => now()
    ]);
    
    $response = $this->actingAs($user)->postJson('/api/timers/resume');
    
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'message',
                 'timer' => ['id', 'status'],
                 'elapsed_seconds'
             ]);
    
    $timer->refresh();
    expect($timer->status)->toBe(Timer::STATUS_RUNNING);
});

test('resume returns 404 when no paused timer exists', function () {
    $user = User::factory()->create();

    
    $response = $this->actingAs($user)->postJson('/api/timers/resume');
    
    $response->assertStatus(404)
             ->assertJson(['message' => 'No paused timer found']);
});

test('user can stop active timer', function () {
    $user = User::factory()->create();

    
    $timer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => 1,
        'status' => Timer::STATUS_RUNNING,
        'started_at' => now()->subMinutes(5)
    ]);
    
    $response = $this->actingAs($user)->postJson('/api/timers/stop');
    
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'message',
                 'time_entry' => ['id', 'user_id', 'trackable_type', 'trackable_id', 'duration_seconds']
             ]);
    
    $timer->refresh();
    expect($timer->status)->toBe(Timer::STATUS_STOPPED);
    
    $this->assertDatabaseHas('time_entries', [
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => 1
    ]);
});

test('stop returns 404 when no active timer exists', function () {
    $user = User::factory()->create();

    
    $response = $this->actingAs($user)->postJson('/api/timers/stop');
    
    $response->assertStatus(404)
             ->assertJson(['message' => 'No active timer found']);
});

test('user can get timer status', function () {
    $user = User::factory()->create();

    
    // Test with no timer
    $response = $this->actingAs($user)->getJson('/api/timers/status');
    $response->assertStatus(200)
             ->assertJson([
                 'status' => 'none',
                 'elapsed_seconds' => 0
             ]);
    
    // Test with active timer
    $timer = Timer::create([
        'user_id' => $user->id,
        'trackable_type' => 'App\\Models\\Project',
        'trackable_id' => 1,
        'status' => Timer::STATUS_RUNNING,
        'started_at' => now()
    ]);
    
    $response = $this->actingAs($user)->getJson('/api/timers/status');
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'status',
                 'elapsed_seconds',
                 'timer_id'
             ]);
});

test('user can get recent time entries', function () {
    $user = User::factory()->create();
    
    // Create some time entries
    $entries = collect(range(1, 3))->map(function ($i) use ($user) {
        return \App\Models\TimeEntry::create([
            'user_id' => $user->id,
            'trackable_type' => 'App\\Models\\Project',
            'trackable_id' => $i,
            'started_at' => now()->subHours($i),
            'ended_at' => now()->subHours($i)->addMinutes(30),
            'duration_seconds' => 1800
        ]);
    });
    
    $response = $this->actingAs($user)->getJson('/api/timers/recent-entries');
    
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'entries' => [
                     '*' => ['id', 'user_id', 'trackable_type', 'trackable_id', 'duration_seconds']
                 ]
             ]);
    
    expect($response->json('entries'))->toHaveCount(3);
});

test('start timer validation fails with invalid data', function () {
    $user = User::factory()->create();

    
    $response = $this->actingAs($user)->postJson('/api/timers/start', [
        'trackable_type' => 'InvalidType',
        'trackable_id' => 'invalid'
    ]);
    
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['trackable_type', 'trackable_id']);
});

test('timer endpoints require authentication', function () {
    $response = $this->getJson('/api/timers/active');
    $response->assertStatus(401);
    
    $response = $this->postJson('/api/timers/start', []);
    $response->assertStatus(401);
    
    $response = $this->postJson('/api/timers/pause');
    $response->assertStatus(401);
    
    $response = $this->postJson('/api/timers/resume');
    $response->assertStatus(401);
    
    $response = $this->postJson('/api/timers/stop');
    $response->assertStatus(401);
});
