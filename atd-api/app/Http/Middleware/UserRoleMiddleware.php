<?php

namespace App\Http\Middleware;

use App\Http\Controllers\TokenController;
use App\Models\HaveRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userRoles = HaveRole::where('id_user', TokenController::decodeToken($request->header('Authorization'))->id);

        $access = FALSE;
        dd($userRoles);
        foreach ($userRoles as $userRole) {
            if (in_array($userRole->id, 3)) {
                $access = true;
                break;
            }
        }

        if($access)
            abort(403, 'Unauthorized action.');
        else
            return $next($request);
    }
}
