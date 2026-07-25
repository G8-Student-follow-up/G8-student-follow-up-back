<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Comment;
use Illuminate\Http\Request;
use App\Models\CommentActivity;

class CommentHistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $month = $request->query('month');       // e.g. "2026-07"
        $day = $request->query('day');            // e.g. "2026-07-24"
        $search = $request->query('search');
        $workspaceId = $request->query('workspace_id');
        $cardId = $request->query('card_id');
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

        if ($day && preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
            // Day filter takes priority over month filter when both are given.
            $query->where('comment_activities.activity_date', $day);
        } elseif ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $query->where('comment_activities.activity_month', $month);
        }

        if ($cardId) {
            // All comments for a single card within the selected month/day,
            // so a card's whole history for that period comes back together.
            $query->where('comments.card_id', (int) $cardId);
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
            'activity_date' => $comment->activity_date,
            'activity_month' => $comment->activity_month,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $perPage,
                'last_page' => $paginator->lastPage(),
                'months_available' => $this->getMonthsAvailable($user->id, $boardIds, $search, $cardId),
            ],
        ]);
    }

    /**
     * Per-day comment counts for a given month, e.g. for a calendar heatmap.
     * GET /comment-history/activity?month=2026-07&workspace_id=...
     */
    public function activity(Request $request)
    {
        $user = $request->user();
        $month = $request->query('month');
        $workspaceId = $request->query('workspace_id');
        $cardId = $request->query('card_id');

        if (!$month || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            return response()->json(['message' => 'A valid month (YYYY-MM) is required.'], 422);
        }

        $boardIds = $this->getAccessibleBoardIds($user->id, $workspaceId);

        if (empty($boardIds)) {
            return response()->json(['data' => []]);
        }

        $query = CommentActivity::query()
            ->selectRaw('activity_date, count(*) as comment_count')
            ->where('user_id', $user->id)
            ->whereIn('board_id', $boardIds)
            ->where('activity_month', $month);

        if ($cardId) {
            $query->where('card_id', (int) $cardId);
        }

        $byDay = $query->groupBy('activity_date')
            ->orderBy('activity_date')
            ->get()
            ->map(fn($row) => [
                'date' => $row->activity_date instanceof \DateTimeInterface
                    ? $row->activity_date->format('Y-m-d')
                    : (string) $row->activity_date,
                'comment_count' => (int) $row->comment_count,
            ]);

        return response()->json(['data' => $byDay]);
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
            'comment_activities.activity_date',
            'comment_activities.activity_month',
        ])
            ->join('comment_activities', 'comment_activities.comment_id', '=', 'comments.id')
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

    private function getMonthsAvailable(int $userId, array $boardIds, ?string $search = null, $cardId = null): array
    {
        $query = Comment::query()
            ->join('comment_activities', 'comment_activities.comment_id', '=', 'comments.id')
            ->join('cards', 'comments.card_id', '=', 'cards.id')
            ->leftJoin('students', 'cards.student_id', '=', 'students.id')
            ->join('boards', 'cards.board_id', '=', 'boards.id')
            ->where('comments.user_id', $userId)
            ->whereIn('boards.id', $boardIds);

        if ($cardId) {
            $query->where('comments.card_id', (int) $cardId);
        }

        $this->applySearch($query, $search);

        return $query->select('comment_activities.activity_month as month')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->pluck('month')
            ->map(fn($m) => (string) $m)
            ->all();
    }
}