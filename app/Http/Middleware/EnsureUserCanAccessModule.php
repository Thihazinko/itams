<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanAccessModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canAccess($module)) {
            $label = User::MODULES[$module] ?? $module;
            abort(403, "You do not have access to {$label}.");
        }

        return $next($request);
    }
}
