<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !$user->force_password_change) {
            return $next($request);
        }

        if ($request->routeIs('password.force.*', 'logout')) {
            return $next($request);
        }

        return redirect()->route('password.force.edit');
    }
}
