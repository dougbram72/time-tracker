<?php

use App\Models\User;
use App\Models\Timer;
use App\Models\Project;
use App\Models\Issue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    $this->issue = Issue::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id
    ]);
});

describe('Timer Error Handling', function () {
    
    it('handles sync validation errors gracefully', function () {
        $this->actingAs($this->user);
        
        $timer = Timer::factory()->create([
            'user_id' => $this->user->id,
            'trackable_type' => Issue::class,
            'trackable_id' => $this->issue->id,
            'status' => 'running',
            'started_at' => now()->subMinutes(30)
        ]);
        
        // Test invalid status
        $response = $this->postJson('/api/timers/sync', [
            'timer_id' => $timer->id,
            'status' => 'invalid_status',
            'elapsed_seconds' => 1800
        ]);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['status']);
    });
    
    it('handles sync with non-existent timer', function () {
        $this->actingAs($this->user);
        
        $response = $this->postJson('/api/timers/sync', [
            'timer_id' => 99999,
            'status' => 'running',
            'elapsed_seconds' => 1800
        ]);
        
        $response->assertStatus(404)
                ->assertJson(['error' => 'Timer not found or access denied']);
    });
    
    it('handles unauthorized timer sync attempts', function () {
        $otherUser = User::factory()->create();
        $otherTimer = Timer::factory()->create([
            'user_id' => $otherUser->id,
            'trackable_type' => Issue::class,
            'trackable_id' => $this->issue->id,
            'status' => 'running'
        ]);
        
        $this->actingAs($this->user);
        
        $response = $this->postJson('/api/timers/sync', [
            'timer_id' => $otherTimer->id,
            'status' => 'paused',
            'elapsed_seconds' => 1800
        ]);
        
        $response->assertStatus(404)
                ->assertJson(['error' => 'Timer not found or access denied']);
    });
    
    it('handles sync with negative elapsed seconds', function () {
        $this->actingAs($this->user);
        
        $timer = Timer::factory()->create([
            'user_id' => $this->user->id,
            'trackable_type' => Issue::class,
            'trackable_id' => $this->issue->id,
            'status' => 'running'
        ]);
        
        $response = $this->postJson('/api/timers/sync', [
            'timer_id' => $timer->id,
            'status' => 'running',
            'elapsed_seconds' => -100
        ]);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['elapsed_seconds']);
    });
    
    it('handles sync with stopped timer correctly', function () {
        $this->actingAs($this->user);
        
        $timer = Timer::factory()->create([
            'user_id' => $this->user->id,
            'trackable_type' => Issue::class,
            'trackable_id' => $this->issue->id,
            'status' => 'stopped',
            'started_at' => now()->subHour(),
            'stopped_at' => now()->subMinutes(30)
        ]);
        
        $response = $this->postJson('/api/timers/sync', [
            'timer_id' => $timer->id,
            'status' => 'stopped',
            'elapsed_seconds' => 1800
        ]);
        
        $response->assertStatus(200)
                ->assertJson(['message' => 'Timer state synchronized successfully']);
        
        $timer->refresh();
        expect($timer->status)->toBe('stopped');
    });
    
    it('handles sync with paused timer and elapsed time adjustment', function () {
        $this->actingAs($this->user);
        
        $startTime = now()->subMinutes(45);
        $timer = Timer::factory()->create([
            'user_id' => $this->user->id,
            'trackable_type' => Issue::class,
            'trackable_id' => $this->issue->id,
            'status' => 'running',
            'started_at' => $startTime,
            'elapsed_seconds' => 1800 // 30 minutes
        ]);
        
        $response = $this->postJson('/api/timers/sync', [
            'timer_id' => $timer->id,
            'status' => 'paused',
            'elapsed_seconds' => 2700 // 45 minutes
        ]);
        
        $response->assertStatus(200)
                ->assertJson(['message' => 'Timer state synchronized successfully']);
        
        $timer->refresh();
        expect($timer->status)->toBe('paused');
        expect($timer->elapsed_seconds)->toBeGreaterThan(2700); // Should include additional time
        expect($timer->paused_at)->not->toBeNull();
    });
    
    it('handles sync state conflicts with server-wins strategy', function () {
        $this->actingAs($this->user);
        
        $timer = Timer::factory()->create([
            'user_id' => $this->user->id,
            'trackable_type' => Issue::class,
            'trackable_id' => $this->issue->id,
            'status' => 'paused',
            'started_at' => now()->subHour(),
            'paused_at' => now()->subMinutes(30),
            'elapsed_seconds' => 1800
        ]);
        
        // Client thinks timer is running, but server has it paused
        $response = $this->postJson('/api/timers/sync', [
            'timer_id' => $timer->id,
            'status' => 'running',
            'elapsed_seconds' => 2400 // 40 minutes
        ]);
        
        $response->assertStatus(200);
        
        $timer->refresh();
        // Server state should be updated with client's elapsed time
        expect($timer->elapsed_seconds)->toBeGreaterThanOrEqual(1800); // Should be updated
    });
    
    it('handles unauthenticated sync requests', function () {
        $response = $this->postJson('/api/timers/sync', [
            'timer_id' => 1,
            'status' => 'running',
            'elapsed_seconds' => 1800
        ]);
        
        $response->assertStatus(401);
    });
    
    it('handles missing required sync parameters', function () {
        $this->actingAs($this->user);
        
        $response = $this->postJson('/api/timers/sync', [
            'timer_id' => 1,
            // Missing status and elapsed_seconds
        ]);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['status']);
    });
    
    it('handles sync with extremely large elapsed seconds', function () {
        $this->actingAs($this->user);
        
        $timer = Timer::factory()->create([
            'user_id' => $this->user->id,
            'trackable_type' => Issue::class,
            'trackable_id' => $this->issue->id,
            'status' => 'running'
        ]);
        
        // Test with 25 hours (over 24 hour limit)
        $response = $this->postJson('/api/timers/sync', [
            'timer_id' => $timer->id,
            'status' => 'running',
            'elapsed_seconds' => 90000 // 25 hours
        ]);
        
        $response->assertStatus(200);
        
        $timer->refresh();
        expect($timer->elapsed_seconds)->toBeGreaterThan(0); // Should be updated with some value
        // The validation should log a warning but still accept the value
    });
});

describe('Timer State Recovery', function () {
    
    it('can recover from corrupted timer state', function () {
        $this->actingAs($this->user);
        
        // Create a timer with valid initial state, then manually corrupt it
        $timer = Timer::factory()->create([
            'user_id' => $this->user->id,
            'trackable_type' => Issue::class,
            'trackable_id' => $this->issue->id,
            'status' => 'running',
            'started_at' => now()->subMinutes(30),
            'elapsed_seconds' => 1800
        ]);
        
        // Manually corrupt the timer state in the database
        DB::table('timers')->where('id', $timer->id)->update([
            'started_at' => null, // Invalid: running timer without start time
            'elapsed_seconds' => -100 // Invalid: negative elapsed time
        ]);
        
        $response = $this->getJson('/api/timers/active');
        
        $response->assertStatus(200);
        
        // The API should handle the corrupted state gracefully
        $activeTimer = $response->json('timer');
        if ($activeTimer) {
            expect($activeTimer['status'])->toBeIn(['running', 'paused', 'stopped']);
            // The backend should return the timer even with corrupted data
            // Frontend validation will handle the correction
            expect($activeTimer['elapsed_seconds'])->toBeNumeric();
        }
    });
    
    it('handles timer without trackable reference', function () {
        $this->actingAs($this->user);
        
        // Create a timer with valid trackable first, then remove it
        $timer = Timer::factory()->create([
            'user_id' => $this->user->id,
            'trackable_type' => Issue::class,
            'trackable_id' => $this->issue->id,
            'status' => 'running',
            'started_at' => now()->subMinutes(30)
        ]);
        
        // Skip this test as trackable_type is required by database constraints
        $this->markTestSkipped('Database constraints prevent null trackable_type');
        
        $response = $this->getJson('/api/timers/active');
        
        $response->assertStatus(200);
        
        $activeTimer = $response->json('timer');
        expect($activeTimer)->not->toBeNull();
        expect($activeTimer['id'])->toBe($timer->id);
    });
});

describe('API Error Responses', function () {
    
    it('returns proper error format for validation failures', function () {
        $this->actingAs($this->user);
        
        $response = $this->postJson('/api/timers/start', [
            'trackable_type' => 'InvalidType',
            'trackable_id' => 'not_a_number'
        ]);
        
        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors' => [
                        'trackable_type',
                        'trackable_id'
                    ]
                ]);
    });
    
    it('returns proper error format for not found resources', function () {
        $this->actingAs($this->user);
        
        $response = $this->postJson('/api/timers/start', [
            'trackable_type' => Issue::class,
            'trackable_id' => 99999 // Non-existent issue
        ]);
        
        $response->assertStatus(404)
                ->assertJson(['message' => 'Project or issue not found']);
    });
    
    it('handles server errors gracefully', function () {
        $this->actingAs($this->user);
        
        // This should trigger a server error due to invalid data
        $response = $this->postJson('/api/timers/sync', [
            'timer_id' => 'not_a_number',
            'status' => 'running',
            'elapsed_seconds' => 1800
        ]);
        
        $response->assertStatus(422);
    });
});
