<?php

namespace App\Http\Middleware;

use App\Http\Controllers\TokenController;
use App\Models\HaveRole;
use App\Models\User;
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
        User::findOrFail($id_user);

        $user_id = TokenController::decodeToken($request->header('Authorization'))->id;
        $admin = HaveRole::where('id_user', $user_id)->where('id_role', 1)->get()->first();
        if($id_user != $user_id && !$admin){
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
