<?php

namespace App\Http\Middleware;

use Closure;
use App\Traits\DomainAuthenticable;

class AuthMiddleware
{
    use DomainAuthenticable;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $header = $request->header();
        if ( !isset($header['token'][0]) || !isset($header['secret'][0]) ) {
            return response()->json(config('replycodes.not_allowed'), 401);
        }

        if ( $this->authDomain($header) ) {
            return $next($request);
        }
        return response()->json(config('replycodes.not_allowed'), 401);
    }
}
