<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\Workspace;
use Illuminate\Http\Request;

class WorkspaceController extends BaseController
{
    /**
     * List all workspaces.
     * 
     * GET /api/workspaces
     */
    public function index()
    {
        $workspaces = Workspace::with('creator')->paginate(10);

        return $this->successResponse(
            $workspaces,
            'Workspaces retrieved successfully'
        );
    }

    /**
     * Create a new workspace.
     * 
     * POST /api/workspaces
     * 
     * Body: { "name": "My Workspace" }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $workspace = Workspace::create([
            'name' => $validated['name'],
            'created_by' => $request->user()->id,
        ]);

        return $this->successResponse(
            $workspace->load('creator'),
            'Workspace created successfully',
            201
        );
    }

    /**
     * Show a single workspace.
     * 
     * GET /api/workspaces/{workspace}
     */
    public function show(Workspace $workspace)
    {
        return $this->successResponse(
            $workspace->load(['creator', 'boards']),
            'Workspace retrieved successfully'
        );
    }

    /**
     * Update a workspace.
     * 
     * PUT /api/workspaces/{workspace}
     */
    public function update(Request $request, Workspace $workspace)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
        ]);

        $workspace->update($validated);

        return $this->successResponse(
            $workspace,
            'Workspace updated successfully'
        );
    }

    /**
     * Delete a workspace.
     * 
     * DELETE /api/workspaces/{workspace}
     */
    public function destroy(Workspace $workspace)
    {
        $workspace->delete();

        return $this->successResponse(
            null,
            'Workspace deleted successfully'
        );
    }
}
