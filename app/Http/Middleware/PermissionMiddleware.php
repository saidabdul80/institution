<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Exceptions\UnauthorizedException;

class PermissionMiddleware
{
    public function handle($request, Closure $next, $permission, $guard = null)
    {
        // $authGuard = $request->user();

        // if (auth()->guard($guard)->guest()) {
        //     throw UnauthorizedException::notLoggedIn();
        // }
        Log::error($permission);
        $permissions = is_array($permission)
            ? $permission
            : explode('|', $permission);

        foreach ($permissions as $permission) {
            if ($request->user()->hasPermissionTo($permission)) {
                return $next($request);
            }
        }

        throw UnauthorizedException::forPermissions($permissions);
    }
}
