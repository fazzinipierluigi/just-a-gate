<?php

namespace Fazzinipierluigi\JustAGate\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AclCheck
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
        $user = Auth::user();

        $action = $request->route()->getAction('controller');
        $action = explode('\\', $action);
        $action = $action[count($action)-1];
        $action = str_replace('Controller@', '.', $action);
        $action = strtolower($action);

        if(!$user->can($action))
        {
            return abort(403);
        }

        return $next($request);
    }
}
