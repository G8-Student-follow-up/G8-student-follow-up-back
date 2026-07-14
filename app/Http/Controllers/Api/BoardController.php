<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\Board;
use App\Models\Workspace;
use App\Services\BoardService;
use Illuminate\Http\Request;

class BoardController extends BaseController
{
    public function __construct(
        private BoardService $boardService
    ) {}

    /**
     * List all boards under a specific workspace.
     * 
     * GET /api/workspaces/{workspace}/boards
     */
    public function index(Workspace $workspace)
    {
        $boards = $workspace->boards()->paginate(10);

        return $this->successResponse(
            $boards,
            'Boards retrieved successfully'
        );
    }

    /**
     * Create a new board under a specific workspace.
     * 
     * POST /api/workspaces/{workspace}/boards
     * 
     * Body: { "title": "My Board", "is_favorite": false }
     */
    public function store(Request $request, Workspace $workspace)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'color' => 'sometimes|nullable|string|max:32',
            'description' => 'sometimes|nullable|string|max:1000',
            'is_favorite' => 'sometimes|boolean',
            'is_archived' => 'sometimes|boolean',
        ]);

        // Log payload for debugging frontend/backend integration
        logger()->info('Board create payload', [
            'workspace_id' => $workspace->id,
            'payload' => $validated,
            'user_id' => optional($request->user())->id,
        ]);

        try {
            $board = $this->boardService->createBoard($workspace, $validated);

            return $this->successResponse(
                $board,
                'Board created successfully',
                201
            );
        } catch (\Throwable $e) {
            logger()->error('Board create failed', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return $this->errorReponse('Failed to create board', 500);
        }
    }

    /**
     * Show a single board (must belong to the specified workspace).
     * 
     * GET /api/workspaces/{workspace}/boards/{board}
     */
    public function show(Workspace $workspace, Board $board)
    {
        if ($board->workspace_id !== $workspace->id) {
            return $this->errorReponse(
                'Board does not belong to this workspace',
                404
            );
        }

        return $this->successResponse(
            $board->load('classes'),
            'Board retrieved successfully'
        );
    }

    /**
     * Update a board.
     * 
     * PUT /api/workspaces/{workspace}/boards/{board}
     */
    public function update(Request $request, Workspace $workspace, Board $board)
    {
        if ($board->workspace_id !== $workspace->id) {
            return $this->errorReponse(
                'Board does not belong to this workspace',
                404
            );
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'color' => 'sometimes|nullable|string|max:32',
            'description' => 'sometimes|nullable|string|max:1000',
            'is_favorite' => 'sometimes|boolean',
            'is_archived' => 'sometimes|boolean',
        ]);

        $board = $this->boardService->updateBoard($board, $validated);

        return $this->successResponse(
            $board,
            'Board updated successfully'
        );
    }

    /**
     * Delete a board.
     * 
     * DELETE /api/workspaces/{workspace}/boards/{board}
     */
    public function destroy(Workspace $workspace, Board $board)
    {
        if ($board->workspace_id !== $workspace->id) {
            return $this->errorReponse(
                'Board does not belong to this workspace',
                404
            );
        }

        $this->boardService->deleteBoard($board);

        return $this->successResponse(
            null,
            'Board deleted successfully'
        );
    }
}
