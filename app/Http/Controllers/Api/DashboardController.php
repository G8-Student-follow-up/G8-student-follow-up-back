<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\Label;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();
        $months = max(1, min(24, (int) ($request->months ?? 6)));

        $isAdmin = $user->role === 'admin';
        $boardAccess = function ($q) use ($user, $isAdmin) {
            if ($isAdmin) return;
            $q->whereHas('workspace', fn($w) => $w->where('created_by', $user->id)
                ->orWhereHas('members', fn($m) => $m->where('user_id', $user->id)))
              ->orWhereHas('members', fn($b) => $b->where('user_id', $user->id));
        };

        $totalBoards = Board::where($boardAccess)->count();
        $totalCards = Card::whereHas('board', $boardAccess)->count();
        $pendingCards = Card::where('status', 'Pending')->whereHas('board', $boardAccess)->count();
        $completedCards = Card::where('status', 'Completed')->whereHas('board', $boardAccess)->count();
        $totalColumns = Column::whereHas('board', $boardAccess)->count();
        $totalLabels = Label::whereHas('board', $boardAccess)->count();
        $totalWorkspaces = $isAdmin ? Workspace::count() : Workspace::where(function ($q) use ($user) {
            $q->where('created_by', $user->id)
              ->orWhereHas('members', fn($m) => $m->where('user_id', $user->id));
        })->count();
        $myCards = $isAdmin ? $totalCards : Card::where('created_by', $user->id)->count();
        $myPendingCards = $isAdmin ? $pendingCards : Card::where('created_by', $user->id)->where('status', 'Pending')->count();

        $statuses = ['Completed', 'In Progress', 'Pending', 'Archived'];
        $statusColors = ['Completed' => '#10b981', 'In Progress' => '#2563eb', 'Pending' => '#f59e0b', 'Archived' => '#ef4444'];
        $statusBreakdown = [];
        foreach ($statuses as $s) {
            $statusBreakdown[] = [
                'label' => $s,
                'value' => Card::where('status', $s)->whereHas('board', $boardAccess)->count(),
                'color' => $statusColors[$s],
            ];
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            $trendData = Card::selectRaw("
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    DATE_FORMAT(created_at, '%b %Y') as label,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
                ")
                ->where('created_at', '>=', now()->subMonths($months))
                ->whereHas('board', $boardAccess)
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
                ->where('created_at', '>=', now()->subMonths($months))
                ->whereHas('board', $boardAccess)
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
            'total_labels' => $totalLabels,
            'total_workspaces' => $totalWorkspaces,
            'completed_follow_ups' => $completedCards,
            'total_columns' => $totalColumns,
            'my_cards' => $myCards,
            'my_pending_cards' => $myPendingCards,
            'status_breakdown' => $statusBreakdown,
            'trend_data' => $trendData,
        ]);
    }
}
