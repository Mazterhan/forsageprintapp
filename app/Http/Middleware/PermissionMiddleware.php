<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function __construct(private readonly PermissionService $permissions)
    {
    }

    public function handle(Request $request, Closure $next, string $required): Response
    {
        $user = $request->user();
        $permissions = array_filter(array_map('trim', explode('|', $required)));

        foreach ($permissions as $permission) {
            if (!$this->permissions->can($user, $permission)) {
                abort(403, 'У вас немає доступу до цієї сторінки. Зверніться до адміністратора для отримання відповідного рівня доступу.');
            }
        }

        return $next($request);
    }
}
