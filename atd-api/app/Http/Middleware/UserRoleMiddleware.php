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
    public function handle(Request $request, Closure $next, string $array): Response
    {
        $roles = unserialize($array);

        $userRoles = HaveRole::where('id_user', TokenController::decodeToken($request->header('Authorization'))->id)->get();
        $access = false;
        foreach($roles as $role){
            foreach ($userRoles as $userRole) {
                if ($userRole->id_role == $role) {
                    $access = true;
                    break;
                }
            }
        }

        if($access)
            return $next($request);
        else
            abort(403, 'Unauthorized action.');
    }
}
