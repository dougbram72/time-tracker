<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class IssueController extends Controller
{
    /**
     * Display a listing of the user's issues.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->issues()
            ->active()
            ->with('project')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by project if specified
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by status if specified
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $issues = $query->get();

        return response()->json([
            'issues' => $issues->map(function ($issue) {
                return [
                    'id' => $issue->id,
                    'title' => $issue->title,
                    'description' => $issue->description,
                    'priority' => $issue->priority,
                    'priority_color' => $issue->priority_color,
                    'status' => $issue->status,
                    'status_color' => $issue->status_color,
                    'external_id' => $issue->external_id,
                    'is_active' => $issue->is_active,
                    'total_time' => $issue->formatted_total_time,
                    'project' => $issue->project ? [
                        'id' => $issue->project->id,
                        'name' => $issue->project->name,
                        'color' => $issue->project->color,
                    ] : null,
                    'created_at' => $issue->created_at,
                    'updated_at' => $issue->updated_at,
                ];
            })
        ]);
    }

    /**
     * Store a newly created issue.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:5000',
                'priority' => 'sometimes|in:low,medium,high,urgent',
                'status' => 'sometimes|in:open,in_progress,resolved,closed',
                'project_id' => 'nullable|exists:projects,id',
                'external_id' => 'nullable|string|max:255',
            ]);

            // Ensure project belongs to user if specified
            if (isset($validated['project_id'])) {
                $project = Project::where('id', $validated['project_id'])
                    ->where('user_id', $request->user()->id)
                    ->first();
                    
                if (!$project) {
                    return response()->json([
                        'message' => 'Project not found'
                    ], 404);
                }
            }

            $issue = $request->user()->issues()->create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'priority' => $validated['priority'] ?? 'medium',
                'status' => $validated['status'] ?? 'open',
                'project_id' => $validated['project_id'] ?? null,
                'external_id' => $validated['external_id'] ?? null,
                'is_active' => true,
            ]);

            $issue->load('project');

            return response()->json([
                'message' => 'Issue created successfully',
                'issue' => [
                    'id' => $issue->id,
                    'title' => $issue->title,
                    'description' => $issue->description,
                    'priority' => $issue->priority,
                    'priority_color' => $issue->priority_color,
                    'status' => $issue->status,
                    'status_color' => $issue->status_color,
                    'external_id' => $issue->external_id,
                    'is_active' => $issue->is_active,
                    'total_time' => $issue->formatted_total_time,
                    'project' => $issue->project ? [
                        'id' => $issue->project->id,
                        'name' => $issue->project->name,
                        'color' => $issue->project->color,
                    ] : null,
                    'created_at' => $issue->created_at,
                    'updated_at' => $issue->updated_at,
                ]
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create issue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified issue.
     */
    public function show(Request $request, Issue $issue): JsonResponse
    {
        // Ensure user owns the issue
        if ($issue->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Issue not found'
            ], 404);
        }

        $issue->load('project');

        return response()->json([
            'issue' => [
                'id' => $issue->id,
                'title' => $issue->title,
                'description' => $issue->description,
                'priority' => $issue->priority,
                'priority_color' => $issue->priority_color,
                'status' => $issue->status,
                'status_color' => $issue->status_color,
                'external_id' => $issue->external_id,
                'is_active' => $issue->is_active,
                'total_time' => $issue->formatted_total_time,
                'project' => $issue->project ? [
                    'id' => $issue->project->id,
                    'name' => $issue->project->name,
                    'color' => $issue->project->color,
                ] : null,
                'created_at' => $issue->created_at,
                'updated_at' => $issue->updated_at,
            ]
        ]);
    }

    /**
     * Update the specified issue.
     */
    public function update(Request $request, Issue $issue): JsonResponse
    {
        // Ensure user owns the issue
        if ($issue->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Issue not found'
            ], 404);
        }

        try {
            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string|max:5000',
                'priority' => 'sometimes|in:low,medium,high,urgent',
                'status' => 'sometimes|in:open,in_progress,resolved,closed',
                'project_id' => 'nullable|exists:projects,id',
                'external_id' => 'nullable|string|max:255',
                'is_active' => 'sometimes|boolean',
            ]);

            // Ensure project belongs to user if specified
            if (isset($validated['project_id'])) {
                $project = Project::where('id', $validated['project_id'])
                    ->where('user_id', $request->user()->id)
                    ->first();
                    
                if (!$project) {
                    return response()->json([
                        'message' => 'Project not found'
                    ], 404);
                }
            }

            $issue->update($validated);
            $issue->load('project');

            return response()->json([
                'message' => 'Issue updated successfully',
                'issue' => [
                    'id' => $issue->id,
                    'title' => $issue->title,
                    'description' => $issue->description,
                    'priority' => $issue->priority,
                    'priority_color' => $issue->priority_color,
                    'status' => $issue->status,
                    'status_color' => $issue->status_color,
                    'external_id' => $issue->external_id,
                    'is_active' => $issue->is_active,
                    'total_time' => $issue->formatted_total_time,
                    'project' => $issue->project ? [
                        'id' => $issue->project->id,
                        'name' => $issue->project->name,
                        'color' => $issue->project->color,
                    ] : null,
                    'created_at' => $issue->created_at,
                    'updated_at' => $issue->updated_at,
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update issue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified issue.
     */
    public function destroy(Request $request, Issue $issue): JsonResponse
    {
        // Ensure user owns the issue
        if ($issue->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Issue not found'
            ], 404);
        }

        try {
            // Soft delete by setting is_active to false
            $issue->update(['is_active' => false]);

            return response()->json([
                'message' => 'Issue deactivated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to deactivate issue',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
