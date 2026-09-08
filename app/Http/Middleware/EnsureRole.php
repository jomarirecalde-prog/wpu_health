<?php

namespace App\Http\Middleware;

use App\Services\AuthRoleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function __construct(private readonly AuthRoleService $roles)
    {
    }

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $this->roles->hasRole(...$roles)) {
            abort(403, __('You do not have permission to access this area.'));
        }

        return $next($request);
    }
}
