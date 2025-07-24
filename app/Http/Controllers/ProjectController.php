<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    /**
     * Display a listing of the user's projects.
     */
    public function index(Request $request): JsonResponse
    {
        $projects = $request->user()->projects()
            ->active()
            ->orderBy('name')
            ->get();

        return response()->json([
            'projects' => $projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'color' => $project->color,
                    'is_active' => $project->is_active,
                    'total_time' => $project->formatted_total_time,
                    'active_issues_count' => $project->active_issues_count,
                    'created_at' => $project->created_at,
                    'updated_at' => $project->updated_at,
                ];
            })
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            ]);

            $project = $request->user()->projects()->create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'color' => $validated['color'] ?? '#3B82F6',
                'is_active' => true,
            ]);

            return response()->json([
                'message' => 'Project created successfully',
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'color' => $project->color,
                    'is_active' => $project->is_active,
                    'total_time' => $project->formatted_total_time,
                    'active_issues_count' => $project->active_issues_count,
                    'created_at' => $project->created_at,
                    'updated_at' => $project->updated_at,
                ]
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified project.
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        // Ensure user owns the project
        if ($project->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Project not found'
            ], 404);
        }

        return response()->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'color' => $project->color,
                'is_active' => $project->is_active,
                'total_time' => $project->formatted_total_time,
                'active_issues_count' => $project->active_issues_count,
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at,
            ]
        ]);
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        // Ensure user owns the project
        if ($project->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Project not found'
            ], 404);
        }

        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'is_active' => 'sometimes|boolean',
            ]);

            $project->update($validated);

            return response()->json([
                'message' => 'Project updated successfully',
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'color' => $project->color,
                    'is_active' => $project->is_active,
                    'total_time' => $project->formatted_total_time,
                    'active_issues_count' => $project->active_issues_count,
                    'created_at' => $project->created_at,
                    'updated_at' => $project->updated_at,
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        // Ensure user owns the project
        if ($project->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Project not found'
            ], 404);
        }

        try {
            // Soft delete by setting is_active to false
            $project->update(['is_active' => false]);

            return response()->json([
                'message' => 'Project deactivated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to deactivate project',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
