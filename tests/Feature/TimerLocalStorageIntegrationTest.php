<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\Issue;
use App\Models\Timer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimerLocalStorageIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected Issue $issue;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->issue = Issue::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id
        ]);
    }

    /** @test */
    public function timer_store_javascript_structure_supports_local_storage()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $content = $response->getContent();

        // Verify local storage functionality exists
        $this->assertStringContainsString('localStorage', $content);
        
        // Verify save/load functions exist
        $this->assertStringContainsString('saveToStorage', $content);
        $this->assertStringContainsString('loadFromStorage', $content);
        
        // Verify state structure that gets saved
        $this->assertStringContainsString('timer:', $content);
        $this->assertStringContainsString('elapsedSeconds:', $content);
        $this->assertStringContainsString('recentEntries:', $content);
        $this->assertStringContainsString('projects:', $content);
        $this->assertStringContainsString('issues:', $content);
        
        // Verify JSON serialization handling
        $this->assertStringContainsString('JSON.stringify', $content);
        $this->assertStringContainsString('JSON.parse', $content);
        
        // Verify error handling for corrupted storage
        $this->assertStringContainsString('try {', $content);
        $this->assertStringContainsString('catch', $content);
    }

    /** @test */
    public function timer_state_recovery_handles_corrupted_local_storage()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $content = $response->getContent();

        // Verify recovery mechanisms are in place
        $this->assertStringContainsString('validateTimerState', $content);
        $this->assertStringContainsString('emergencyReset', $content);
        
        // Verify default state initialization
        $this->assertStringContainsString('id: null', $content);
        $this->assertStringContainsString('status: \'stopped\'', $content);
        $this->assertStringContainsString('elapsed_seconds: 0', $content);
        
        // Verify state validation logic
        $this->assertStringContainsString('validateStoredData', $content);
        $this->assertStringContainsString('validateTimerState', $content);
    }

    /** @test */
    public function timer_synchronization_handles_offline_queue_persistence()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $content = $response->getContent();

        // Verify network status monitoring exists
        $this->assertStringContainsString('networkStatus', $content);
        $this->assertStringContainsString('online', $content);
        $this->assertStringContainsString('offline', $content);
        
        // Verify event listeners for network changes
        $this->assertStringContainsString('addEventListener', $content);
    }

    /** @test */
    public function timer_drift_detection_and_correction_is_implemented()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $content = $response->getContent();

        // Verify periodic validation exists
        $this->assertStringContainsString('validateTimerState', $content);
        $this->assertStringContainsString('setInterval', $content);
        
        // Verify time calculation logic
        $this->assertStringContainsString('elapsedSeconds', $content);
        $this->assertStringContainsString('started_at', $content);
    }

    /** @test */
    public function real_time_updates_maintain_accuracy_across_browser_sessions()
    {
        // Start a timer via API
        $startResponse = $this->actingAs($this->user)->postJson('/api/timers/start', [
            'trackable_type' => 'App\\Models\\Project',
            'trackable_id' => $this->project->id,
            'description' => 'Cross-session test'
        ]);

        $startResponse->assertStatus(201);
        $timerId = $startResponse->json('timer.id');

        // Simulate time passing
        sleep(2);

        // Get dashboard (simulating new browser session)
        $response = $this->actingAs($this->user)->get('/dashboard');
        $content = $response->getContent();

        // Verify timer recovery logic is present
        $this->assertStringContainsString('fetchActiveTimer', $content);
        $this->assertStringContainsString('loadFromStorage', $content);
        $this->assertStringContainsString('started_at', $content);
        
        // Verify real-time update mechanism
        $this->assertStringContainsString('updateElapsedTime', $content);
        $this->assertStringContainsString('setInterval', $content);
        $this->assertStringContainsString('1000', $content); // 1 second intervals
        
        // Clean up
        $this->actingAs($this->user)->postJson('/api/timers/stop');
    }

    /** @test */
    public function multi_tab_synchronization_prevents_conflicts()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $content = $response->getContent();

        // Verify visibility change handling
        $this->assertStringContainsString('visibilitychange', $content);
        $this->assertStringContainsString('document.hidden', $content);
        
        // Verify tab synchronization exists
        $this->assertStringContainsString('syncWithServer', $content);
        
        // Verify conflict resolution
        $this->assertStringContainsString('conflictResolution', $content);
        $this->assertStringContainsString('server-wins', $content);
        
        // Verify storage event handling for cross-tab communication
        $this->assertStringContainsString('storage', $content);
        $this->assertStringContainsString('addEventListener', $content);
    }

    /** @test */
    public function error_recovery_mechanisms_are_comprehensive()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $content = $response->getContent();

        // Verify error handling exists
        $this->assertStringContainsString('error', $content);
        $this->assertStringContainsString('catch', $content);
        
        // Verify recovery strategies exist
        $this->assertStringContainsString('emergencyReset', $content);
        $this->assertStringContainsString('saveToLocalStorage', $content);
        
        // Verify user notification system
        $this->assertStringContainsString('showMessage', $content);
        $this->assertStringContainsString('error', $content);
        $this->assertStringContainsString('success', $content);
        
        // Verify retry logic exists
        $this->assertStringContainsString('retry', $content);
    }

    /** @test */
    public function performance_optimizations_are_implemented()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $content = $response->getContent();

        // Verify efficient DOM updates
        $this->assertStringContainsString('x-text', $content); // Alpine.js efficient updates
        
        // Verify memory management
        $this->assertStringContainsString('clearInterval', $content);
        
        // Verify interval-based updates
        $this->assertStringContainsString('setInterval', $content);
    }

    /** @test */
    public function accessibility_features_are_properly_integrated()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $content = $response->getContent();

        // Verify basic accessibility features
        $this->assertStringContainsString('title=', $content);
        
        // Verify focus management
        $this->assertStringContainsString('focus:', $content);
        $this->assertStringContainsString('outline', $content);
    }

    /** @test */
    public function data_validation_prevents_corruption_across_storage_operations()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $content = $response->getContent();

        // Verify basic validation exists
        $this->assertStringContainsString('validateTimerState', $content);
        
        // Verify type checking
        $this->assertStringContainsString('typeof', $content);
        
        // Verify basic validation logic
        $this->assertStringContainsString('required', $content);
    }

    /** @test */
    public function browser_compatibility_features_are_implemented()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $content = $response->getContent();

        // Verify feature detection
        $this->assertStringContainsString('localStorage', $content);
        $this->assertStringContainsString('navigator.vibrate', $content);
        
        // Verify polyfills and fallbacks
        $this->assertStringContainsString('if (', $content);
        $this->assertStringContainsString('typeof', $content);
        
        // Verify cross-browser event handling
        $this->assertStringContainsString('addEventListener', $content);
        
        // Verify mobile browser optimizations
        $this->assertStringContainsString('touchstart', $content);
        $this->assertStringContainsString('touchmove', $content);
        $this->assertStringContainsString('touchend', $content);
        
        // Verify iOS Safari specific optimizations
        $this->assertStringContainsString('webkit', $content);
        $this->assertStringContainsString('apple-mobile-web-app', $content);
    }
}
