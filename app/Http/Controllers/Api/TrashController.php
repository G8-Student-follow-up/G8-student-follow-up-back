<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Card;
use App\Models\Workspace;
use Illuminate\Http\Request;

class TrashController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $boards = Board::onlyTrashed()
            ->with('workspace')
            ->whereIn('workspace_id', $this->accessibleWorkspaceIds($user))
            ->latest('deleted_at')
            ->get();

        $cards = Card::onlyTrashed()
            ->with(['board' => fn ($query) => $query->withTrashed(), 'column', 'student', 'trainer'])
            ->whereHas('board', function ($query) use ($user) {
                $query->withTrashed()->whereIn('workspace_id', $this->accessibleWorkspaceIds($user));
            })
            ->latest('deleted_at')
            ->get();

        $items = $boards->map(fn (Board $board) => $this->item('board', $board))
            ->concat($cards->map(fn (Card $card) => $this->item('card', $card)))
            ->sortByDesc('deleted_at')
            ->values();

        return response()->json([
            'items' => $items,
            'boards' => $boards,
            'cards' => $cards,
        ]);
    }

    public function restoreBoard(Request $request, int $board)
    {
        $board = Board::onlyTrashed()->findOrFail($board);
        $this->ensureBoardAccess($board, $request->user());
        $board->restore();

        return response()->json(['board' => $board->fresh('workspace')]);
    }

    public function forceDeleteBoard(Request $request, int $board)
    {
        $board = Board::onlyTrashed()->findOrFail($board);
        $this->ensureBoardAccess($board, $request->user());
        $board->forceDelete();

        return response()->json(null, 204);
    }

    public function restoreCard(Request $request, int $card)
    {
        $card = Card::onlyTrashed()->with('board')->findOrFail($card);
        $this->ensureBoardAccess($card->board, $request->user());

        if ($card->board->trashed()) {
            return response()->json(['message' => 'Restore the board before restoring this card.'], 422);
        }

        $card->restore();

        return response()->json(['card' => $card->fresh(['board', 'column', 'student', 'trainer'])]);
    }

    public function forceDeleteCard(Request $request, int $card)
    {
        $card = Card::onlyTrashed()->with('board')->findOrFail($card);
        $this->ensureBoardAccess($card->board, $request->user());
        $card->forceDelete();

        return response()->json(null, 204);
    }

    private function accessibleWorkspaceIds($user)
    {
        return Workspace::where('owner_id', $user->id)
            ->orWhereHas('members', fn ($query) => $query->where('user_id', $user->id))
            ->pluck('id');
    }

    private function ensureBoardAccess(Board $board, $user): void
    {
        $hasWorkspaceAccess = $board->workspace()
            ->where(function ($query) use ($user) {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('members', fn ($memberQuery) => $memberQuery->where('user_id', $user->id));
            })->exists();

        $hasBoardMembership = $board->members()->where('user_id', $user->id)->exists();

        abort_unless($hasWorkspaceAccess || $hasBoardMembership, 403, 'Forbidden');
    }

    private function item(string $type, Board|Card $model): array
    {
        return [
            'id' => $model->id,
            'type' => $type,
            'title' => $model->title,
            'deleted_at' => $model->deleted_at,
            'data' => $model,
        ];
    }
}
