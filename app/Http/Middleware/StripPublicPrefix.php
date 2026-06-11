<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StripPublicPrefix
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        if ($path === 'public' || str_starts_with($path, 'public/')) {
            $target = '/'.ltrim(substr($path, 7), '/');
            $query = $request->getQueryString();

            if ($query) {
                $target .= '?'.$query;
            }

            return redirect()->to($target, 301);
        }

        return $next($request);
    }
}
