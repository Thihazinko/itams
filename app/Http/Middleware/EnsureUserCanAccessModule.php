<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanAccessModule
{
    public function handle(Request $request, Closure $next, string $module, string $action = 'view'): Response
    {
        $user = $request->user();
        $action = $action === 'edit' ? 'edit' : 'view';

        $allowed = $action === 'edit'
            ? ($user && $user->canEdit($module))
            : ($user && $user->canView($module));

        if (! $allowed) {
            $label = User::MODULES[$module] ?? $module;
            $verb = $action === 'edit' ? 'modify' : 'access';
            abort(403, "You do not have permission to {$verb} {$label}.");
        }

        return $next($request);
    }
}
