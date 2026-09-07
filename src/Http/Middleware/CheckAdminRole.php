<?php

namespace HolartWeb\AxoraCMS\Http\Middleware;

use Closure;
use HolartWeb\AxoraCMS\Enums\AdminRole;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $admin = $request->user('admin');

        if (! $admin) {
            abort(403, 'Unauthorized');
        }

        // Convert string roles to AdminRole enums
        $allowedRoles = array_map(fn ($role) => AdminRole::from($role), $roles);

        // Check if admin has one of the allowed roles
        if (! in_array($admin->role, $allowedRoles)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
