<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        $allowed = array_filter(array_map('trim', explode('|', $roles)));

        if (!in_array($user->role, $allowed, true)) {
            abort(403);
        }

        return $next($request);
    }
}
