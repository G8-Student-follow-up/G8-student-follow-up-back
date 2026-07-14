<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats()
    {
        $totalStudents = Student::count();
        $pendingFollowUps = Student::where('status', 'Pending')->count();
        $completedFollowUps = Student::where('status', 'Completed')->count();
        $activeTrainers = User::where('role', 'trainer')->count();

        $statusBreakdown = [
            ['label' => 'Completed', 'value' => Student::where('status', 'Completed')->count(), 'color' => '#10b981'],
            ['label' => 'In Progress', 'value' => Student::where('status', 'In Progress')->count(), 'color' => '#2563eb'],
            ['label' => 'Pending', 'value' => Student::where('status', 'Pending')->count(), 'color' => '#f59e0b'],
            ['label' => 'Archived', 'value' => Student::where('status', 'Archived')->count(), 'color' => '#ef4444'],
        ];

        $trendData = Student::selectRaw("DATE_FORMAT(created_at, '%b') as month, COUNT(*) as total")
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'total_students' => $totalStudents,
            'pending_follow_ups' => $pendingFollowUps,
            'completed_follow_ups' => $completedFollowUps,
            'active_trainers' => $activeTrainers,
            'status_breakdown' => $statusBreakdown,
            'trend_data' => $trendData,
        ]);
    }
}
