<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\Issue;
use App\Models\Timer;
use App\Models\TimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimerIntegrationTest extends TestCase
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
    public function complete_timer_workflow_with_project_creates_time_entry()
    {
        // Start timer with project
        $response = $this->actingAs($this->user)->postJson('/api/timers/start', [
            'trackable_type' => 'App\\Models\\Project',
            'trackable_id' => $this->project->id,
            'description' => 'Working on project integration'
        ]);

        $response->assertStatus(201);
        $timerId = $response->json('timer.id');

        // Verify timer is active
        $activeResponse = $this->actingAs($this->user)->getJson('/api/timers/active');
        $activeResponse->assertStatus(200)
            ->assertJson([
                'timer' => [
                    'id' => $timerId,
                    'status' => 'running',
                    'trackable_type' => 'App\\Models\\Project',
                    'trackable_id' => $this->project->id,
                    'description' => 'Working on project integration'
                ]
            ]);

        // Simulate some work time
        sleep(2);

        // Stop timer
        $stopResponse = $this->actingAs($this->user)->postJson('/api/timers/stop');
        $stopResponse->assertStatus(200);

        // Verify time entry was created
        $timeEntry = TimeEntry::where('user_id', $this->user->id)->first();
        $this->assertNotNull($timeEntry);
        $this->assertEquals('App\\Models\\Project', $timeEntry->trackable_type);
        $this->assertEquals($this->project->id, $timeEntry->trackable_id);
        $this->assertEquals($this->project->id, $timeEntry->project_id);
        $this->assertEquals('Working on project integration', $timeEntry->description);
        $this->assertGreaterThanOrEqual(2, $timeEntry->duration_seconds);

        // Verify timer is stopped
        $finalResponse = $this->actingAs($this->user)->getJson('/api/timers/active');
        $finalResponse->assertStatus(200)
            ->assertJson(['timer' => null]);
    }

    /** @test */
    public function complete_timer_workflow_with_issue_creates_time_entry()
    {
        // Start timer with issue
        $response = $this->actingAs($this->user)->postJson('/api/timers/start', [
            'trackable_type' => 'App\\Models\\Issue',
            'trackable_id' => $this->issue->id,
            'description' => 'Fixing bug in authentication'
        ]);

        $response->assertStatus(201);
        $timerId = $response->json('timer.id');

        // Verify timer includes project relationship
        $activeResponse = $this->actingAs($this->user)->getJson('/api/timers/active');
        $activeResponse->assertStatus(200)
            ->assertJson([
                'timer' => [
                    'id' => $timerId,
                    'status' => 'running',
                    'trackable_type' => 'App\\Models\\Issue',
                    'trackable_id' => $this->issue->id,
                    'project_id' => $this->project->id
                ]
            ]);

        // Simulate work time
        sleep(2);

        // Stop timer
        $stopResponse = $this->actingAs($this->user)->postJson('/api/timers/stop');
        $stopResponse->assertStatus(200);

        // Verify time entry was created with proper relationships
        $timeEntry = TimeEntry::where('user_id', $this->user->id)->first();
        $this->assertNotNull($timeEntry);
        $this->assertEquals('App\\Models\\Issue', $timeEntry->trackable_type);
        $this->assertEquals($this->issue->id, $timeEntry->trackable_id);
        $this->assertEquals($this->project->id, $timeEntry->project_id);
        $this->assertEquals($this->issue->id, $timeEntry->issue_id);
        $this->assertEquals('Fixing bug in authentication', $timeEntry->description);
    }

    /** @test */
    public function timer_pause_resume_workflow_maintains_accurate_duration()
    {
        // Start timer
        $startResponse = $this->actingAs($this->user)->postJson('/api/timers/start', [
            'trackable_type' => 'App\\Models\\Project',
            'trackable_id' => $this->project->id,
            'description' => 'Testing pause/resume functionality'
        ]);

        $startResponse->assertStatus(201);
        $startTime = now();

        // Work for 2 seconds
        sleep(2);

        // Pause timer
        $pauseResponse = $this->actingAs($this->user)->postJson('/api/timers/pause');
        $pauseResponse->assertStatus(200);
        $pauseTime = now();

        // Verify timer is paused
        $statusResponse = $this->actingAs($this->user)->getJson('/api/timers/status');
        $statusResponse->assertStatus(200)
            ->assertJson([
                'status' => 'paused'
            ]);

        // Wait during pause (this time shouldn't count)
        sleep(1);

        // Resume timer
        $resumeResponse = $this->actingAs($this->user)->postJson('/api/timers/resume');
        $resumeResponse->assertStatus(200);
        $resumeTime = now();

        // Work for 2 more seconds
        sleep(2);

        // Stop timer
        $stopResponse = $this->actingAs($this->user)->postJson('/api/timers/stop');
        $stopResponse->assertStatus(200);

        // Verify time entry duration excludes pause time
        $timeEntry = TimeEntry::where('user_id', $this->user->id)->first();
        $this->assertNotNull($timeEntry);
        
        // Duration should be approximately 4 seconds (2 + 2), not 5 seconds
        $this->assertGreaterThanOrEqual(3, $timeEntry->duration_seconds);
        $this->assertLessThan(6, $timeEntry->duration_seconds); // Allow some variance
    }

    /** @test */
    public function starting_new_timer_automatically_stops_previous_timer()
    {
        // Start first timer
        $firstResponse = $this->actingAs($this->user)->postJson('/api/timers/start', [
            'trackable_type' => 'App\\Models\\Project',
            'trackable_id' => $this->project->id,
            'description' => 'First task'
        ]);

        $firstResponse->assertStatus(201);
        $firstTimerId = $firstResponse->json('timer.id');

        // Work for 2 seconds
        sleep(2);

        // Start second timer (should auto-stop first)
        $secondResponse = $this->actingAs($this->user)->postJson('/api/timers/start', [
            'trackable_type' => 'App\\Models\\Issue',
            'trackable_id' => $this->issue->id,
            'description' => 'Second task'
        ]);

        $secondResponse->assertStatus(201);
        $secondTimerId = $secondResponse->json('timer.id');

        // Verify different timer IDs
        $this->assertNotEquals($firstTimerId, $secondTimerId);

        // Verify only second timer is active
        $activeResponse = $this->actingAs($this->user)->getJson('/api/timers/active');
        $activeResponse->assertStatus(200)
            ->assertJson([
                'timer' => [
                    'id' => $secondTimerId,
                    'status' => 'running',
                    'description' => 'Second task'
                ]
            ]);

        // Verify first timer created a time entry
        $firstTimeEntry = TimeEntry::where('user_id', $this->user->id)
            ->where('description', 'First task')
            ->first();
        $this->assertNotNull($firstTimeEntry);
        $this->assertGreaterThanOrEqual(2, $firstTimeEntry->duration_seconds);

        // Stop second timer
        $this->actingAs($this->user)->postJson('/api/timers/stop');

        // Verify both time entries exist
        $this->assertEquals(2, TimeEntry::where('user_id', $this->user->id)->count());
    }

    /** @test */
    public function timer_synchronization_resolves_conflicts_correctly()
    {
        // Start timer
        $startResponse = $this->actingAs($this->user)->postJson('/api/timers/start', [
            'trackable_type' => 'App\\Models\\Project',
            'trackable_id' => $this->project->id,
            'description' => 'Sync test'
        ]);

        $timerId = $startResponse->json('timer.id');

        // Simulate client-side elapsed time
        sleep(3);

        // Sync with server (simulating client reporting 5 seconds elapsed)
        $syncResponse = $this->actingAs($this->user)->postJson('/api/timers/sync', [
            'timer_id' => $timerId,
            'status' => 'running',
            'elapsed_seconds' => 5
        ]);

        $syncResponse->assertStatus(200);

        // Verify server accepted the sync
        $statusResponse = $this->actingAs($this->user)->getJson('/api/timers/status');
        $elapsedSeconds = $statusResponse->json('elapsed_seconds');
        
        // Should be close to 5 seconds (client reported time)
        $this->assertGreaterThanOrEqual(4, $elapsedSeconds);
        $this->assertLessThan(7, $elapsedSeconds);

        // Stop timer and verify duration
        $this->actingAs($this->user)->postJson('/api/timers/stop');
        
        $timeEntry = TimeEntry::where('user_id', $this->user->id)->first();
        $this->assertGreaterThanOrEqual(4, $timeEntry->duration_seconds);
    }

    /** @test */
    public function recent_entries_api_returns_proper_project_issue_relationships()
    {
        // Create multiple time entries with different trackable types
        $projectEntry = TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'trackable_type' => 'App\\Models\\Project',
            'trackable_id' => $this->project->id,
            'project_id' => $this->project->id,
            'issue_id' => null,
            'description' => 'Project work',
            'duration_seconds' => 1800
        ]);

        $issueEntry = TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'trackable_type' => 'App\\Models\\Issue',
            'trackable_id' => $this->issue->id,
            'project_id' => $this->project->id,
            'issue_id' => $this->issue->id,
            'description' => 'Issue work',
            'duration_seconds' => 3600
        ]);

        // Get recent entries
        $response = $this->actingAs($this->user)->getJson('/api/timers/recent-entries');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'entries' => [
                    '*' => [
                        'id',
                        'user_id',
                        'trackable_type',
                        'trackable_id',
                        'project_id',
                        'issue_id',
                        'description',
                        'duration_seconds',
                        'created_at',
                        'display_name',
                        'project',
                        'issue',
                        'trackable'
                    ]
                ]
            ]);

        $entries = $response->json('entries');
        $this->assertCount(2, $entries);

        // Find project entry
        $projectEntryResponse = collect($entries)->firstWhere('description', 'Project work');
        $this->assertNotNull($projectEntryResponse);
        $this->assertEquals('App\\Models\\Project', $projectEntryResponse['trackable_type']);
        $this->assertEquals($this->project->id, $projectEntryResponse['project_id']);
        $this->assertNull($projectEntryResponse['issue_id']);
        $this->assertNotNull($projectEntryResponse['project']);
        $this->assertNull($projectEntryResponse['issue']);

        // Find issue entry
        $issueEntryResponse = collect($entries)->firstWhere('description', 'Issue work');
        $this->assertNotNull($issueEntryResponse);
        $this->assertEquals('App\\Models\\Issue', $issueEntryResponse['trackable_type']);
        $this->assertEquals($this->project->id, $issueEntryResponse['project_id']);
        $this->assertEquals($this->issue->id, $issueEntryResponse['issue_id']);
        $this->assertNotNull($issueEntryResponse['project']);
        $this->assertNotNull($issueEntryResponse['issue']);
    }

    /** @test */
    public function timer_widget_dashboard_integration_displays_all_components()
    {
        // Create some test data
        TimeEntry::factory()->count(3)->create(['user_id' => $this->user->id]);

        // Visit dashboard
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200)
            ->assertSee('Timer Widget')
            ->assertSee('Start Timer')
            ->assertSee('Recent Entries')
            ->assertSee('timerStore()')
            ->assertSee('/api/timers/active')
            ->assertSee('/api/timers/recent-entries');

        // Verify Alpine.js timer store is loaded
        $response->assertSee('x-data="timerStore()"', false);
        
        // Verify timer store JavaScript is loaded
        $response->assertSee('timerStore()');
    }

    /** @test */
    public function error_handling_workflow_recovers_gracefully()
    {
        // Start timer
        $startResponse = $this->actingAs($this->user)->postJson('/api/timers/start', [
            'trackable_type' => 'App\\Models\\Project',
            'trackable_id' => $this->project->id,
            'description' => 'Error handling test'
        ]);

        $timerId = $startResponse->json('timer.id');

        // Simulate error condition by trying to sync with invalid data
        $errorResponse = $this->actingAs($this->user)->postJson('/api/timers/sync', [
            'timer_id' => $timerId,
            'status' => 'invalid_status',
            'elapsed_seconds' => -10
        ]);

        $errorResponse->assertStatus(422); // Validation error

        // Verify timer is still running despite error
        $statusResponse = $this->actingAs($this->user)->getJson('/api/timers/status');
        $statusResponse->assertStatus(200)
            ->assertJson([
                'timer_id' => $timerId,
                'status' => 'running'
            ]);

        // Verify we can still stop the timer normally
        $stopResponse = $this->actingAs($this->user)->postJson('/api/timers/stop');
        $stopResponse->assertStatus(200);

        // Verify time entry was created
        $timeEntry = TimeEntry::where('user_id', $this->user->id)->first();
        $this->assertNotNull($timeEntry);
    }

    /** @test */
    public function concurrent_timer_operations_maintain_data_integrity()
    {
        // Start timer
        $startResponse = $this->actingAs($this->user)->postJson('/api/timers/start', [
            'trackable_type' => 'App\\Models\\Project',
            'trackable_id' => $this->project->id,
            'description' => 'Concurrent test'
        ]);

        $timerId = $startResponse->json('timer.id');

        // Simulate concurrent operations
        $responses = [];
        
        // Multiple pause requests
        $responses[] = $this->actingAs($this->user)->postJson('/api/timers/pause');
        $responses[] = $this->actingAs($this->user)->postJson('/api/timers/pause');
        
        // Resume after pause
        $responses[] = $this->actingAs($this->user)->postJson('/api/timers/resume');
        
        // Multiple stop requests
        $responses[] = $this->actingAs($this->user)->postJson('/api/timers/stop');
        $responses[] = $this->actingAs($this->user)->postJson('/api/timers/stop');

        // At least one operation should succeed
        $successCount = collect($responses)->filter(fn($r) => $r->status() === 200)->count();
        $this->assertGreaterThan(0, $successCount);

        // Verify only one time entry was created
        $this->assertEquals(1, TimeEntry::where('user_id', $this->user->id)->count());

        // Verify no active timer remains
        $activeResponse = $this->actingAs($this->user)->getJson('/api/timers/active');
        $activeResponse->assertJson(['timer' => null]);
    }

    /** @test */
    public function full_workflow_with_authentication_and_authorization()
    {
        // Create another user
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->create(['user_id' => $otherUser->id]);

        // Try to start timer with other user's project (should fail)
        $unauthorizedResponse = $this->actingAs($this->user)->postJson('/api/timers/start', [
            'trackable_type' => 'App\\Models\\Project',
            'trackable_id' => $otherProject->id,
            'description' => 'Unauthorized access attempt'
        ]);

        $unauthorizedResponse->assertStatus(404); // Project not found

        // Start timer with own project (should succeed)
        $authorizedResponse = $this->actingAs($this->user)->postJson('/api/timers/start', [
            'trackable_type' => 'App\\Models\\Project',
            'trackable_id' => $this->project->id,
            'description' => 'Authorized access'
        ]);

        $authorizedResponse->assertStatus(201);

        // Other user shouldn't see this timer
        $otherUserResponse = $this->actingAs($otherUser)->getJson('/api/timers/active');
        $otherUserResponse->assertJson(['timer' => null]);

        // Original user should see their timer
        $originalUserResponse = $this->actingAs($this->user)->getJson('/api/timers/active');
        $originalUserResponse->assertJsonFragment(['description' => 'Authorized access']);

        // Clean up
        $this->actingAs($this->user)->postJson('/api/timers/stop');
    }
}
