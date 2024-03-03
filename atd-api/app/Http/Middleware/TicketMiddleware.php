<?php

namespace App\Http\Middleware;

use App\Http\Controllers\TokenController;
use App\Models\HaveRole;
use App\Models\Send;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TicketMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $id_ticket = $request->route('id_ticket');
        $id_user = TokenController::decodeToken($request->header('Authorization'))->id;
        $send = Send::select('id_user')->where('id_ticket', $id_ticket)->where('id_user', $id_user)->get()->first();
        $admin = HaveRole::where('id_user', $id_user)->get()->first();
        $support = HaveRole::where('id_user', $id_user)->get()->first();

        if(!isset($send) && (!isset($admin) || !isset($support))){
            return response()->json([
                'message' => 'Resource not found'
            ], 404);
        }

        return $next($request);
    }
}
