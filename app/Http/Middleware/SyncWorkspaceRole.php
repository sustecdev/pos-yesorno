<?php

namespace App\Http\Middleware;

use App\Support\WorkspaceRoles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncWorkspaceRole
{
    private const PREFIX_MAP = [
        'admin' => 'admin',
        'waiter' => 'waiter',
        'kitchen' => 'kitchen',
        'cashier' => 'cashier',
        'host' => 'host',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->canSwitchWorkspace()) {
            $prefix = $request->segment(1);

            if (isset(self::PREFIX_MAP[$prefix])) {
                WorkspaceRoles::set(self::PREFIX_MAP[$prefix]);
            }
        }

        return $next($request);
    }
}
