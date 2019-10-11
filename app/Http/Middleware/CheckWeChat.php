<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckWeChat
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $openid = $request->session()->get('openid');
        if ($openid === null) {
            return StandardFailJsonResponse("你还没有openid");
        }
        return $next($request);
    }
}
