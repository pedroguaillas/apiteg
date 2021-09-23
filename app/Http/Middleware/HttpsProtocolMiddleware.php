<?php

namespace App\Http\Middleware;

use Closure;

class HttpsProtocolMiddleware
{
    public function handle($request, Closure $next)
    {
        var_dump('Probando Middleware');
        if (!$request->secure() && app()->environment('production')) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
