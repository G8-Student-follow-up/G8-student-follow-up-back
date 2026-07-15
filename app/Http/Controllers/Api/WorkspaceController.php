<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkspaceController extends BaseController
{
    /**
     * GET /api/workspaces
     * Returns a list of workspaces.
     */
    public function index(): JsonResponse
    {
        $workspaces = Workspace::with('owner:id,name,email')->get();

        return response()->json([
            'workspaces' => $workspaces,
        ]);
    }

    /**
     * POST /api/workspaces
     * Create a new workspace. The creator is automatically added as 'owner'.
     */
    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $workspace = DB::transaction(function () use ($request) {
            $workspace = Workspace::create([
                'name' => $request->validated('name'),
                'description' => $request->validated('description'),
                'color' => $request->validated('color', '#3b82f6'),
                'created_by' => $request->user()->id,
            ]);

            // Automatically add the creator as the workspace's owner member.
            $workspace->members()->attach($request->user()->id, ['role' => 'owner']);

            return $workspace;
        });

        return response()->json([
            'workspace' => $workspace->load('owner:id,name,email'),
        ], 201);
    }

    /**
     * GET /api/workspaces/{workspace}
     * Return a single workspace with its details.
     */
    public function show(Workspace $workspace): JsonResponse
    {
        $workspace->load('owner:id,name,email');

        return response()->json([
            'workspace' => $workspace,
        ]);
    }

    /**
     * PUT/PATCH /api/workspaces/{workspace}
     * Update an existing workspace's name/description/color.
     * Only the workspace owner (created_by) or a site Admin can update it.
     */
    public function update(UpdateWorkspaceRequest $request, Workspace $workspace): JsonResponse
    {
        $user = $request->user();

        $isOwner = $workspace->created_by === $user->id;
        $isAdmin = $user->role === 'admin';

        if (! $isOwner && ! $isAdmin) {
            return response()->json([
                'message' => 'You are not authorized to update this workspace.',
            ], 403);
        }

        $workspace->update($request->validated());

        return response()->json([
            'workspace' => $workspace->fresh()->load('owner:id,name,email'),
        ]);
    }

    /**
     * DELETE /api/workspaces/{workspace}
     * Delete a workspace.
     * Only the workspace owner (created_by) or a site Admin can delete it.
     */
    public function destroy(Request $request, Workspace $workspace): JsonResponse
    {
        $user = $request->user();

        $isOwner = $workspace->created_by === $user->id;
        $isAdmin = $user->role === 'admin';

        if (! $isOwner && ! $isAdmin) {
            return response()->json([
                'message' => 'You are not authorized to delete this workspace.',
            ], 403);
        }

        $workspace->delete();

        return response()->json([
            'message' => 'Workspace deleted successfully.',
        ]);
    }
}