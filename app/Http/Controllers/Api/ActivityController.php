<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('user');

        if ($type = $request->type) {
            $query->where('action', $type);
        }

        if ($userId = $request->user_id) {
            $query->where('user_id', $userId);
        }

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('subject_type', 'like', "%{$search}%");
            });
        }

        $perPage = $request->per_page ?? 20;

        $activities = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'activities' => $activities->items(),
            'total' => $activities->total(),
            'last_page' => $activities->lastPage(),
        ]);
    }
}
