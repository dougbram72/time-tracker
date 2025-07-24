<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\Issue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimerBrowserIntegrationTest extends TestCase
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
    public function dashboard_loads_with_timer_widget_and_required_scripts()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200)
            // Verify timer widget component is present
            ->assertSee('x-data="timerStore()"', false)
            ->assertSee('Timer Widget')
            ->assertSee('No Active Timer')
            
            // Verify timer store is loaded
            ->assertSee('timerStore()')
            ->assertSee('alpinejs')
            ->assertSee('tailwindcss')
            
            // Verify API endpoints are configured
            ->assertSee('/api/timers/active')
            ->assertSee('/api/timers/start')
            ->assertSee('/api/timers/recent-entries')
            
            // Verify mobile optimizations
            ->assertSee('viewport')
            ->assertSee('touch-manipulation')
            ->assertSee('min-h-[44px]')
            
            // Verify timer controls are present
            ->assertSee('Start Timer')
            ->assertSee('Recent Entries');
    }

    /** @test */
    public function timer_widget_displays_project_and_issue_data()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);

        // Verify the page contains the necessary data for Alpine.js
        $content = $response->getContent();
        
        // Check that the timer store initialization is present
        $this->assertStringContainsString('timerStore()', $content);
        
        // Verify CSRF token is available for API calls
        $this->assertStringContainsString('csrf-token', $content);
        
        // Verify mobile-responsive classes are applied
        $this->assertStringContainsString('fixed bottom-0 left-0 right-0', $content);
        $this->assertStringContainsString('md:relative', $content);
    }

    /** @test */
    public function start_timer_modal_contains_all_required_form_elements()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200)
            // Modal structure
            ->assertSee('Start New Timer')
            ->assertSee('showStartModal')
            
            // Form elements
            ->assertSee('What are you working on?')
            ->assertSee('Select type...')
            ->assertSee('Project')
            ->assertSee('Issue')
            ->assertSee('Description (Optional)')
            
            // Form actions
            ->assertSee('Cancel')
            ->assertSee('Start Timer')
            
            // Touch optimizations
            ->assertSee('min-h-[44px]')
            ->assertSee('touch-manipulation')
            ->assertSee('text-base'); // Prevents zoom on iOS
    }

    /** @test */
    public function timer_controls_have_proper_mobile_touch_attributes()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $content = $response->getContent();

        // Verify touch event handlers are present
        $this->assertStringContainsString('provideTouchFeedback', $content);
        $this->assertStringContainsString('handleTouchStart', $content);
        $this->assertStringContainsString('handleTouchMove', $content);
        $this->assertStringContainsString('handleTouchEnd', $content);
        
        // Verify button optimizations
        $this->assertStringContainsString('touch-manipulation', $content);
        $this->assertStringContainsString('select-none', $content);
        $this->assertStringContainsString('min-h-[44px]', $content);
        
        // Verify long press functionality
        $this->assertStringContainsString('handleLongPress', $content);
    }

    /** @test */
    public function recent_entries_section_has_proper_structure()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200)
            ->assertSee('Recent Entries')
            ->assertSee('showRecentEntries')
            ->assertSee('recentEntries')
            ->assertSee('formatDuration')
            ->assertSee('custom-scrollbar')
            ->assertSee('-webkit-overflow-scrolling: touch');
    }

    /** @test */
    public function error_handling_ui_components_are_present()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $content = $response->getContent();

        // Verify error handling UI
        $this->assertStringContainsString('syncStatus', $content);
        $this->assertStringContainsString('error', $content);
        $this->assertStringContainsString('success', $content);
        
        // Verify sync status indicators
        $this->assertStringContainsString('syncing', $content);
        $this->assertStringContainsString('idle', $content);
        $this->assertStringContainsString('offline', $content);
        
        // Verify recovery actions
        $this->assertStringContainsString('Retry Sync', $content);
        $this->assertStringContainsString('Fix State', $content);
        $this->assertStringContainsString('Reset Timer', $content);
    }

    /** @test */
    public function api_endpoints_return_proper_json_structure_for_frontend()
    {
        // Test active timer endpoint
        $activeResponse = $this->actingAs($this->user)->getJson('/api/timers/active');
        $activeResponse->assertStatus(200)
            ->assertJsonStructure([
                'timer'
            ]);

        // Test projects endpoint
        $projectsResponse = $this->actingAs($this->user)->getJson('/api/projects');
        $projectsResponse->assertStatus(200)
            ->assertJsonStructure([
                'projects' => [
                    '*' => ['id', 'name', 'color', 'created_at']
                ]
            ]);

        // Test issues endpoint
        $issuesResponse = $this->actingAs($this->user)->getJson('/api/issues');
        $issuesResponse->assertStatus(200)
            ->assertJsonStructure([
                'issues' => [
                    '*' => ['id', 'title', 'priority', 'priority_color', 'project']
                ]
            ]);

        // Test recent entries endpoint
        $entriesResponse = $this->actingAs($this->user)->getJson('/api/timers/recent-entries');
        $entriesResponse->assertStatus(200)
            ->assertJsonStructure([
                'entries' => [
                    '*' => [
                        'id', 'user_id', 'trackable_type', 'trackable_id',
                        'project_id', 'issue_id', 'description', 'duration_seconds',
                        'created_at', 'display_name', 'project', 'issue', 'trackable'
                    ]
                ]
            ]);
    }

    /** @test */
    public function timer_state_persistence_works_with_local_storage_structure()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $content = $response->getContent();

        // Verify local storage functions are present
        $this->assertStringContainsString('saveToStorage', $content);
        $this->assertStringContainsString('loadFromStorage', $content);
        $this->assertStringContainsString('localStorage.setItem', $content);
        $this->assertStringContainsString('localStorage.getItem', $content);
        
        // Verify state structure is defined
        $this->assertStringContainsString('timer:', $content);
        $this->assertStringContainsString('elapsedSeconds:', $content);
        $this->assertStringContainsString('recentEntries:', $content);
        $this->assertStringContainsString('projects:', $content);
        $this->assertStringContainsString('issues:', $content);
    }

    /** @test */
    public function synchronization_features_are_properly_integrated()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $content = $response->getContent();

        // Verify sync functionality
        $this->assertStringContainsString('syncWithServer', $content);
        $this->assertStringContainsString('syncStatus', $content);
        $this->assertStringContainsString('lastSyncTime', $content);
        $this->assertStringContainsString('networkStatus', $content);
        
        // Verify sync API endpoint
        $this->assertStringContainsString('/api/timers/sync', $content);
        
        // Verify sync intervals and monitoring
        $this->assertStringContainsString('syncInterval', $content);
        $this->assertStringContainsString('setInterval', $content);
        $this->assertStringContainsString('addEventListener', $content);
    }

    /** @test */
    public function mobile_touch_optimizations_are_properly_integrated()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $content = $response->getContent();

        // Verify touch handlers
        $this->assertStringContainsString('handleTouchStart', $content);
        $this->assertStringContainsString('handleTouchMove', $content);
        $this->assertStringContainsString('handleTouchEnd', $content);
        $this->assertStringContainsString('handleModalTouchStart', $content);
        
        // Verify touch feedback
        $this->assertStringContainsString('provideTouchFeedback', $content);
        $this->assertStringContainsString('navigator.vibrate', $content);
        
        // Verify swipe gestures
        $this->assertStringContainsString('handleSwipeDown', $content);
        $this->assertStringContainsString('minSwipeDistance', $content);
        
        // Verify long press functionality
        $this->assertStringContainsString('handleLongPress', $content);
        $this->assertStringContainsString('quickStartTimer', $content);
        $this->assertStringContainsString('emergencyReset', $content);
    }

    /** @test */
    public function css_optimizations_for_mobile_are_present()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $content = $response->getContent();

        // Verify viewport meta tags
        $this->assertStringContainsString('maximum-scale=1', $content);
        $this->assertStringContainsString('user-scalable=no', $content);
        $this->assertStringContainsString('viewport-fit=cover', $content);
        
        // Verify mobile web app meta tags
        $this->assertStringContainsString('mobile-web-app-capable', $content);
        $this->assertStringContainsString('apple-mobile-web-app-capable', $content);
        $this->assertStringContainsString('theme-color', $content);
        
        // Verify touch-specific CSS
        $this->assertStringContainsString('touch-manipulation', $content);
        $this->assertStringContainsString('-webkit-overflow-scrolling: touch', $content);
        $this->assertStringContainsString('font-size: 16px', $content); // Prevents zoom on iOS
    }

    /** @test */
    public function authentication_integration_works_properly()
    {
        // Test unauthenticated access redirects
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        // Test authenticated access works
        $authenticatedResponse = $this->actingAs($this->user)->get('/dashboard');
        $authenticatedResponse->assertStatus(200);

        // Test authenticated API access works
        $authenticatedApiResponse = $this->actingAs($this->user)->getJson('/api/timers/active');
        $authenticatedApiResponse->assertStatus(200);
    }

    /** @test */
    public function timer_widget_responsive_design_classes_are_applied()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $content = $response->getContent();

        // Verify mobile-first responsive classes
        $this->assertStringContainsString('fixed bottom-0 left-0 right-0', $content); // Mobile positioning
        $this->assertStringContainsString('md:relative', $content); // Desktop positioning
        $this->assertStringContainsString('md:border', $content); // Desktop styling
        $this->assertStringContainsString('md:rounded-lg', $content); // Desktop styling
        
        // Verify grid responsiveness
        $this->assertStringContainsString('grid-cols-2', $content); // Mobile grid
        $this->assertStringContainsString('md:grid-cols-4', $content); // Desktop grid
        
        // Verify text responsiveness
        $this->assertStringContainsString('text-3xl', $content); // Timer display
        $this->assertStringContainsString('md:hidden', $content); // Mobile-specific elements
        $this->assertStringContainsString('hidden md:inline', $content); // Desktop-specific elements
    }
}
