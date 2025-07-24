<?php

namespace App\Http\Controllers;

use App\Models\Timer;
use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TimerController extends Controller
{
    /**
     * Get active timer for the authenticated user
     */
    public function active(): JsonResponse
    {
        $timer = Timer::activeForUser(Auth::id())->first();
        
        if ($timer) {
            return response()->json([
                'timer' => $timer,
                'elapsed_seconds' => $timer->getCurrentElapsedSeconds()
            ]);
        }
        
        return response()->json(['timer' => null]);
    }

    /**
     * Start a new timer
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'trackable_type' => 'required|string|in:App\\Models\\Project,App\\Models\\Issue',
            'trackable_id' => 'required|integer',
            'description' => 'nullable|string|max:1000'
        ]);

        try {
            // Validate that the trackable entity exists and belongs to the user
            $trackableClass = $request->trackable_type;
            $trackable = $trackableClass::where('id', $request->trackable_id)
                ->where('user_id', Auth::id())
                ->first();
                
            if (!$trackable) {
                return response()->json([
                    'message' => 'Project or issue not found'
                ], 404);
            }
            
            // Stop any currently running timer
            $activeTimer = Timer::runningForUser(Auth::id())->first();
            if ($activeTimer) {
                $activeTimer->stop();
            }
            
            // Determine project_id and issue_id based on trackable type
            $projectId = null;
            $issueId = null;
            
            if ($request->trackable_type === 'App\\Models\\Project') {
                $projectId = $request->trackable_id;
            } elseif ($request->trackable_type === 'App\\Models\\Issue') {
                $issueId = $request->trackable_id;
                $projectId = $trackable->project_id; // Get project from issue
            }
            
            // Create and start new timer
            $timer = Timer::create([
                'user_id' => Auth::id(),
                'trackable_type' => $request->trackable_type,
                'trackable_id' => $request->trackable_id,
                'project_id' => $projectId,
                'issue_id' => $issueId,
                'description' => $request->description,
                'status' => Timer::STATUS_STOPPED
            ]);
            
            $timer->start();
            $timer->load(['trackable', 'project', 'issue']);
            
            return response()->json([
                'message' => 'Timer started successfully',
                'timer' => [
                    'id' => $timer->id,
                    'status' => $timer->status,
                    'description' => $timer->description,
                    'started_at' => $timer->started_at,
                    'elapsed_seconds' => $timer->getCurrentElapsedSeconds(),
                    'trackable' => [
                        'type' => $timer->trackable_type,
                        'id' => $timer->trackable_id,
                        'name' => $timer->trackable->name ?? $timer->trackable->title,
                    ],
                    'project' => $timer->project ? [
                        'id' => $timer->project->id,
                        'name' => $timer->project->name,
                        'color' => $timer->project->color,
                    ] : null,
                    'issue' => $timer->issue ? [
                        'id' => $timer->issue->id,
                        'title' => $timer->issue->title,
                        'priority' => $timer->issue->priority,
                    ] : null,
                ]
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to start timer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pause the active timer
     */
    public function pause(): JsonResponse
    {
        $timer = Timer::runningForUser(Auth::id())->first();
        
        if (!$timer) {
            return response()->json([
                'message' => 'No running timer found'
            ], 404);
        }
        
        $timer->pause();
        
        return response()->json([
            'message' => 'Timer paused successfully',
            'timer' => $timer,
            'elapsed_seconds' => $timer->getCurrentElapsedSeconds()
        ]);
    }

    /**
     * Resume a paused timer
     */
    public function resume(): JsonResponse
    {
        $timer = Timer::where('user_id', Auth::id())
                     ->where('status', Timer::STATUS_PAUSED)
                     ->first();
        
        if (!$timer) {
            return response()->json([
                'message' => 'No paused timer found'
            ], 404);
        }
        
        $timer->resume();
        
        return response()->json([
            'message' => 'Timer resumed successfully',
            'timer' => $timer,
            'elapsed_seconds' => $timer->getCurrentElapsedSeconds()
        ]);
    }

    /**
     * Stop the active timer and create time entry
     */
    public function stop(): JsonResponse
    {
        $timer = Timer::activeForUser(Auth::id())->first();
        
        if (!$timer) {
            return response()->json([
                'message' => 'No active timer found'
            ], 404);
        }
        
        try {
            $timeEntry = $timer->stop();
            
            return response()->json([
                'message' => 'Timer stopped successfully',
                'time_entry' => $timeEntry
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to stop timer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get timer status and elapsed time
     */
    public function status(): JsonResponse
    {
        $timer = Timer::activeForUser(Auth::id())->first();
        
        if (!$timer) {
            return response()->json([
                'status' => 'none',
                'elapsed_seconds' => 0
            ]);
        }
        
        return response()->json([
            'status' => $timer->status,
            'elapsed_seconds' => $timer->getCurrentElapsedSeconds(),
            'timer_id' => $timer->id
        ]);
    }

    /**
     * Get recent time entries
     */
    public function entries(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        
        $entries = TimeEntry::forUser(Auth::id())
                           ->orderBy('created_at', 'desc')
                           ->limit($limit)
                           ->get();
        
        return response()->json([
            'entries' => $entries
        ]);
    }
    
    /**
     * Get recent time entries for the authenticated user
     */
    public function recentEntries(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        
        $entries = TimeEntry::forUser(Auth::id())
                           ->with(['trackable', 'project', 'issue'])
                           ->orderBy('created_at', 'desc')
                           ->limit($limit)
                           ->get()
                           ->map(function ($entry) {
                               // Add display name for better UI
                               $displayName = 'Unknown';
                               
                               if ($entry->issue) {
                                   $displayName = $entry->issue->title;
                               } elseif ($entry->project) {
                                   $displayName = $entry->project->name;
                               } elseif ($entry->trackable) {
                                   // Fallback to trackable name if available
                                   $displayName = $entry->trackable->name ?? $entry->trackable->title ?? 'Unknown';
                               }
                               
                               return [
                                   'id' => $entry->id,
                                   'user_id' => $entry->user_id,
                                   'trackable_type' => $entry->trackable_type,
                                   'trackable_id' => $entry->trackable_id,
                                   'project_id' => $entry->project_id,
                                   'issue_id' => $entry->issue_id,
                                   'description' => $entry->description,
                                   'duration_seconds' => $entry->duration_seconds,
                                   'created_at' => $entry->created_at,
                                   'display_name' => $displayName,
                                   'project' => $entry->project,
                                   'issue' => $entry->issue,
                                   'trackable' => $entry->trackable
                               ];
                           });
        
        return response()->json([
            'entries' => $entries
        ]);
    }
    
    /**
     * Sync timer state from client to server
     * Used for resolving state conflicts during synchronization
     */
    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'timer_id' => 'required|integer',
            'status' => 'required|string|in:running,paused,stopped',
            'elapsed_seconds' => 'nullable|integer|min:0'
        ]);
        
        try {
            // Find the timer and ensure it belongs to the authenticated user
            $timer = Timer::where('id', $request->timer_id)
                          ->where('user_id', Auth::id())
                          ->first();
                          
            if (!$timer) {
                return response()->json([
                    'error' => 'Timer not found or access denied'
                ], 404);
            }
            
            // Update timer status based on client state
            switch ($request->status) {
                case 'running':
                    if ($timer->status !== 'running') {
                        // Resume or restart the timer
                        $timer->resume();
                    }
                    break;
                    
                case 'paused':
                    if ($timer->status === 'running') {
                        $timer->pause();
                    }
                    break;
                    
                case 'stopped':
                    if ($timer->status !== 'stopped') {
                        $timer->stop();
                    }
                    break;
            }
            
            // If elapsed seconds provided, update the timer's calculated time
            if ($request->has('elapsed_seconds') && $timer->status === 'running') {
                // Adjust the started_at time to match the elapsed seconds
                $targetStartTime = now()->subSeconds($request->elapsed_seconds);
                $timer->update(['started_at' => $targetStartTime]);
            }
            
            // Refresh the timer to get updated state
            $timer->refresh();
            
            return response()->json([
                'success' => true,
                'timer' => $timer,
                'elapsed_seconds' => $timer->getCurrentElapsedSeconds(),
                'message' => 'Timer state synchronized successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to sync timer state: ' . $e->getMessage()
            ], 500);
        }
    }
}
