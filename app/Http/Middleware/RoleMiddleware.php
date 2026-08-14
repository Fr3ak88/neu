<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            abort(403, 'Kein Zugriff.');
        }

        $user = auth()->user();

        if (!in_array($user->role, $roles)) {
            abort(403, 'Kein Zugriff.');
        }

        return $next($request);
    }
}
