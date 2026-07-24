<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();
        $months = max(1, min(24, (int) ($request->months ?? 6)));

        $isAdmin = $user->role === 'admin';

        // Build a list of accessible board IDs once, then reuse everywhere.
        // This avoids ~11 separate WHERE HAS subqueries and cuts DB calls to 3-4.
        if ($isAdmin) {
            $boardIds = Board::pluck('id');
        } else {
            $boardIds = Board::where(function ($q) use ($user) {
                    $q->whereHas('workspace', fn($w) => $w->where('owner_id', $user->id)
                        ->orWhereHas('members', fn($m) => $m->where('user_id', $user->id)))
                      ->orWhereHas('members', fn($b) => $b->where('user_id', $user->id));
                })
                ->pluck('id');
        }

        if ($boardIds->isEmpty()) {
            return response()->json([
                'total_boards' => 0,
                'total_cards' => 0,
                'total_labels' => 0,
                'total_workspaces' => 0,
                'completed_follow_ups' => 0,
                'total_columns' => 0,
                'my_cards' => 0,
                'my_pending_cards' => 0,
                'status_breakdown' => [
                    ['label' => 'Out of follow-ups', 'value' => 0, 'color' => '#2563eb'],
                    ['label' => 'In follow-ups', 'value' => 0, 'color' => '#ec4899'],
                ],
                'trend_data' => [],
            ]);
        }

        $totalBoards = $boardIds->count();
        $totalColumns = Column::whereIn('board_id', $boardIds)->count();

        // Single aggregate query for all card stats
        $cardTotals = Card::whereIn('board_id', $boardIds)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'Archived' THEN 1 ELSE 0 END) as archived
            ")
            ->first();

        $totalCards = (int) ($cardTotals->total ?? 0);
        $pendingCards = (int) ($cardTotals->pending ?? 0);
        $inProgressCards = (int) ($cardTotals->in_progress ?? 0);
        $completedCards = (int) ($cardTotals->completed ?? 0);

        $myCards = $isAdmin
            ? $totalCards
            : Card::whereIn('board_id', $boardIds)->where('created_by', $user->id)->count();

        $myPendingCards = $isAdmin
            ? $pendingCards
            : Card::whereIn('board_id', $boardIds)->where('created_by', $user->id)->where('status', 'Pending')->count();

        $totalInFollowUp = $pendingCards + $inProgressCards;
        $totalOutOfFollowUp = $completedCards;

        $statusBreakdown = [
            ['label' => 'Out of follow-ups', 'value' => $completedCards, 'color' => '#2563eb'],
            ['label' => 'In follow-ups', 'value' => $pendingCards + $inProgressCards, 'color' => '#ec4899'],
        ];

        // Trend data — single query with driver-specific date formatting
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            $trendData = Card::selectRaw("
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    DATE_FORMAT(created_at, '%b %Y') as label,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
                ")
                ->whereIn('board_id', $boardIds)
                ->where('created_at', '>=', now()->subMonths($months))
                ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"), DB::raw("DATE_FORMAT(created_at, '%b %Y')"))
                ->orderBy(DB::raw("MIN(created_at)"))
                ->get();
        } else {
            $trendData = Card::selectRaw("
                    strftime('%Y-%m', created_at) as month,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
                ")
                ->whereIn('board_id', $boardIds)
                ->where('created_at', '>=', now()->subMonths($months))
                ->groupBy(DB::raw("strftime('%Y-%m', created_at)"))
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    $ts = \DateTime::createFromFormat('!Y-m', $item->month);
                    $item->label = $ts ? $ts->format('M Y') : $item->month;
                    return $item;
                });
        }

        return response()->json([
            'total_boards' => $totalBoards,
            'total_cards' => $totalCards,
            'total_labels' => $totalInFollowUp,
            'total_workspaces' => $totalOutOfFollowUp,
            'completed_follow_ups' => $completedCards,
            'total_columns' => $totalColumns,
            'my_cards' => $myCards,
            'my_pending_cards' => $myPendingCards,
            'status_breakdown' => $statusBreakdown,
            'trend_data' => $trendData,
        ]);
    }
}
