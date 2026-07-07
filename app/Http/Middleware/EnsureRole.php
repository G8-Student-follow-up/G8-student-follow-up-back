<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// app/Http/Middleware/EnsureRole.php
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if ($request->user()->role !== $role) {
            abort(403, 'Unauthorized');
        }
        return $next($request);
    }
}