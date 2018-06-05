<?php

namespace App\Http\Middleware;

use Closure;

class SAKIDGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ( 
             $request->header('token')  != env('COMPANION_TOKEN') ||
             $request->header('secret') != env('COMPANION_SECRET')
           ) {
            return response()->json(config('replycodes.not_allowed'), 401);
        }

        return $next($request);
    }
}
