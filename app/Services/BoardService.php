<?php

namespace App\Services;

use App\Models\Board;
use App\Models\Workspace;

class BoardService
{
    /**
     * Create a new board under a specific workspace.
     */
    public function createBoard(Workspace $workspace, array $data): Board
    {
        $data['workspace_id'] = $workspace->id;

        return Board::create($data);
    }

    /**
     * Update an existing board.
     */
    public function updateBoard(Board $board, array $data): Board
    {
        $board->update($data);

        return $board;
    }

    /**
     * Delete a board.
     */
    public function deleteBoard(Board $board): void
    {
        $board->delete();
    }
}
