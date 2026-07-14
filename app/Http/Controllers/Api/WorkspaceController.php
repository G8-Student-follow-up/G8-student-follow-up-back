<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WorkspaceController extends BaseController
{
    /**
     * POST /api/workspaces
     * Create a new workspace. The creator is automatically added as 'owner'.
     */
    public function index(){
        $workspaces = Workspace::paginate(10);

        if($workspaces -> IsEmpty()) {
            return $this -> errorReponse(
                'Workspace Not Found',
                404
            );
        }

        return $this -> successResponse(
            $workspaces,
            'Get Workspaces Successfully',
            200
        );
    }
    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $workspace = DB::transaction(function () use ($request) {
            $workspace = Workspace::create([
                'name' => $request->validated('name'),
                'description' => $request->validated('description'),
                'created_by' => $request->user()->id,
            ]);

            // Automatically add the creator as the workspace's owner member.
            $workspace->members()->attach($request->user()->id, ['role' => 'owner']);

            return $workspace;
        });

        return response()->json(
            $workspace->load('owner:id,name,email'),
            201
        );
    }

    /**
     * PUT/PATCH /api/workspaces/{workspace}
     * Update an existing workspace's name/description.
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

        return response()->json($workspace->fresh());
    }
}