<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi route untuk satu role. Dipakai di route: ->middleware('role:admin') atau 'role:tenant'.
 */
class EnsureRole
{
    /**
     * @param  Closure(Request): (Response)  $next
     * @param  string  $role  nilai UserRole dari parameter middleware ("admin" / "tenant")
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        abort_unless($request->user()?->role === UserRole::from($role), 403);

        return $next($request);
    }
}
