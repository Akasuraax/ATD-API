<?php

namespace App\Http\Middleware;

use App\Http\Controllers\TokenController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateUserId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $id_user = $request->route('id');
        $user_id = TokenController::decodeToken($request->header('Authorization'))->id;
        if($id_user != $user_id){
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
