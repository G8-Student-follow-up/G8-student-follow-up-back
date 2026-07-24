<?php

namespace App\Http\Controllers\Api\Traits;

use App\Models\Board;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;

trait ChecksBoardAccess
{
    /**
     * Ensure the user has access to the given board.
     * Access is granted via workspace ownership, workspace membership, or direct board membership.
     */
    protected function ensureBoardAccess(Board $board, Model|int $user): void
    {
        $userId = is_int($user) ? $user : $user->id;

        $hasWorkspaceAccess = $board->workspace()
            ->where(function ($q) use ($userId) {
                $q->where('owner_id', $userId)
                  ->orWhereHas('members', fn($m) => $m->where('user_id', $userId));
            })->exists();

        $hasBoardMembership = $board->members()->where('user_id', $userId)->exists();

        abort_unless($hasWorkspaceAccess || $hasBoardMembership, 403, 'Forbidden');
    }

    /**
     * Ensure the user has access to the given workspace.
     * Access is granted via workspace ownership or membership.
     */
    protected function ensureWorkspaceAccess(int $workspaceId, Model|int $user): void
    {
        $userId = is_int($user) ? $user : $user->id;

        $hasAccess = Workspace::where('id', $workspaceId)
            ->where(function ($q) use ($userId) {
                $q->where('owner_id', $userId)
                  ->orWhereHas('members', fn($m) => $m->where('user_id', $userId));
            })->exists();

        abort_unless($hasAccess, 403, 'Forbidden');
    }
}
