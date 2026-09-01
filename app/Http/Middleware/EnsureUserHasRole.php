<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle incoming request — validasi user punya role tertentu.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user() || !$request->user()->hasAny($roles)) {
            abort(403, 'Unauthorized. Role tidak memiliki akses.');
        }

        return $next($request);
    }
}
