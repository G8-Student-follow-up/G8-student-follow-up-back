<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrainerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->isTrainer()) {
            return response()->json([
                'message' => 'Forbidden. Trainer access required.',
                'your_role' => $user->role,
            ], 403);
        }

        return $next($request);
    }
}
