<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentHistoryController extends Controller
{
    /**
     * Get the authenticated user's comment history across accessible boards.
     *
     * @queryParam workspace_id int Filter by workspace (scopes to boards within that workspace).
     * @queryParam month string Filter by month (YYYY-MM format).
     * @queryParam search string Search within comment text, card title, student name, or board name.
     * @queryParam per_page int Results per page (1-100, default 50).
     * @queryParam page int Page number.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $month = $request->query('month');
        $search = $request->query('search');
        $workspaceId = $request->query('workspace_id');
        $perPage = min(max((int) $request->query('per_page', 50), 1), 100);
        $page = max((int) $request->query('page', 1), 1);

        $boardIds = $this->getAccessibleBoardIds($user->id, $workspaceId);

        if (empty($boardIds)) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'total' => 0,
                    'per_page' => $perPage,
                    'last_page' => 1,
                    'months_available' => [],
                ],
            ]);
        }

        $query = $this->commentHistoryQuery($user->id, $boardIds)
            ->orderBy('comments.created_at', 'desc');

        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            [$year, $monthNumber] = explode('-', $month);
            $query->whereYear('comments.created_at', (int) $year)
                ->whereMonth('comments.created_at', (int) $monthNumber);
        }

        $this->applySearch($query, $search);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $data = collect($paginator->items())->map(fn($comment) => [
            'id' => $comment->id,
            'card_id' => $comment->card_id,
            'card_title' => $comment->card_title ?: $comment->student_name,
            'board_id' => $comment->board_id,
            'board_name' => $comment->board_name,
            'workspace_id' => $comment->workspace_id,
            'user_id' => (int) $comment->user_id,
            'user_name' => $comment->user_name,
            'user_avatar' => $comment->user_avatar,
            'text' => $comment->message,
            'created_at' => $comment->created_at?->toISOString(),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $perPage,
                'last_page' => $paginator->lastPage(),
                'months_available' => $this->getMonthsAvailable($user->id, $boardIds, $search),
            ],
        ]);
    }

    private function commentHistoryQuery(int $userId, array $boardIds)
    {
        return Comment::select([
            'comments.id',
            'comments.card_id',
            'comments.user_id',
            'comments.message',
            'comments.created_at',
            'cards.title as card_title',
            'students.name as student_name',
            'boards.id as board_id',
            'boards.title as board_name',
            'boards.workspace_id',
            'users.name as user_name',
            'users.avatar as user_avatar',
        ])
            ->join('cards', 'comments.card_id', '=', 'cards.id')
            ->leftJoin('students', 'cards.student_id', '=', 'students.id')
            ->join('boards', 'cards.board_id', '=', 'boards.id')
            ->join('users', 'comments.user_id', '=', 'users.id')
            ->where('comments.user_id', $userId)
            ->whereIn('boards.id', $boardIds);
    }

    private function applySearch($query, ?string $search): void
    {
        if (!$search || trim($search) === '') {
            return;
        }

        $keyword = '%' . trim($search) . '%';

        $query->where(function ($q) use ($keyword) {
            $q->where('comments.message', 'like', $keyword)
                ->orWhere('cards.title', 'like', $keyword)
                ->orWhere('students.name', 'like', $keyword)
                ->orWhere('boards.title', 'like', $keyword);
        });
    }

    private function getAccessibleBoardIds(int $userId, ?string $workspaceId = null): array
    {
        $query = Board::query()
            ->where(function ($query) use ($userId) {
                $query->whereHas('workspace', function ($workspaceQuery) use ($userId) {
                    $workspaceQuery->where('owner_id', $userId)
                        ->orWhereHas('members', fn($memberQuery) => $memberQuery->where('user_id', $userId));
                })->orWhereHas('members', fn($boardQuery) => $boardQuery->where('user_id', $userId));
            });

        if ($workspaceId) {
            $query->where('workspace_id', (int) $workspaceId);
        }

        return $query->pluck('id')->all();
    }

    private function getMonthsAvailable(int $userId, array $boardIds, ?string $search = null): array
    {
        $query = Comment::query()
            ->selectRaw($this->monthExpression() . ' as month')
            ->join('cards', 'comments.card_id', '=', 'cards.id')
            ->leftJoin('students', 'cards.student_id', '=', 'students.id')
            ->join('boards', 'cards.board_id', '=', 'boards.id')
            ->where('comments.user_id', $userId)
            ->whereIn('boards.id', $boardIds);

        $this->applySearch($query, $search);

        return $query->groupBy('month')
            ->orderBy('month', 'desc')
            ->pluck('month')
            ->map(fn($m) => (string) $m)
            ->all();
    }

    private function monthExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', comments.created_at)",
            'pgsql' => "to_char(comments.created_at, 'YYYY-MM')",
            'sqlsrv' => "FORMAT(comments.created_at, 'yyyy-MM')",
            default => "DATE_FORMAT(comments.created_at, '%Y-%m')",
        };
    }
}
