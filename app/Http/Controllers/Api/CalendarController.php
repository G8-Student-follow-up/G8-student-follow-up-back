<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Student;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function events(Request $request)
    {
        $month = $request->month;
        $year = $request->year;

        $query = Card::whereNotNull('follow_up_date')
            ->select('id', 'title', 'follow_up_date', 'priority', 'column_id')
            ->with('column:id,title');

        if ($month && $year) {
            $query->whereYear('follow_up_date', $year)
                ->whereMonth('follow_up_date', $month);
        }

        $cards = $query->get()->map(fn($card) => [
            'id' => $card->id,
            'title' => $card->title,
            'date' => $card->follow_up_date?->format('Y-m-d'),
            'type' => 'Follow-up',
            'color' => match ($card->priority) {
                'High' => '#ef4444',
                'Medium' => '#f59e0b',
                default => '#2563eb',
            },
            'time' => 'All day',
            'source' => 'card',
        ]);

        $students = Student::whereNotNull('follow_up_date')
            ->select('id', 'name', 'follow_up_date', 'priority')
            ->get()->map(fn($student) => [
                'id' => $student->id,
                'title' => $student->name,
                'date' => $student->follow_up_date?->format('Y-m-d'),
                'type' => 'Student Follow-up',
                'color' => match ($student->priority) {
                    'High' => '#ef4444',
                    'Medium' => '#f59e0b',
                    default => '#10b981',
                },
                'time' => 'All day',
                'source' => 'student',
            ]);

        $events = $cards->concat($students)->values();

        return response()->json(['events' => $events]);
    }
}
