<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmployeeSelfServiceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role === 'employee') {
            if ($request->routeIs('dashboard')) {
                return redirect()->route('self-service.index');
            }

            // Protect management endpoints, including the legacy /person aliases.
            abort_if($request->is('hr', 'hr/*', 'person', 'person/*'), 403);
        }

        return $next($request);
    }
}
