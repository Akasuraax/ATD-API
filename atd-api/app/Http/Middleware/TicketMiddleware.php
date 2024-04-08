<?php

namespace App\Http\Middleware;

use App\Http\Controllers\TokenController;
use App\Models\HaveRole;
use App\Models\Send;
use App\Models\Ticket;
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
        $ticket = Ticket::findOrFail($id_ticket);

        $id_user = TokenController::decodeToken($request->header('Authorization'))->id;
        $send = Send::select('id_user')->where('id_ticket', $id_ticket)->where('id_user', $id_user)->get()->first();
        $admin = HaveRole::where('id_user', $id_user)->where('id_role', 1)->get()->first();
        $support = HaveRole::where('id_user', $id_user)->where('id_role', 5)->get()->first();

        if(!isset($send) && !isset($admin) && !isset($support)){
            abort(404, 'Resource not found');
        }

        if(HaveRole::where('id_user', $id_user)->whereNotIn('id_role', [1, 5])->get()->first() && $ticket->archive){
            abort(404, 'Resource not found');
        }

        $user_ids = Send::where('id_ticket', $id_ticket)->get();
        $user = null;

        foreach ($user_ids as $user_id) {
            $user = HaveRole::select('id_user')->where('id_user', $user_id->id_user)
                ->orderBy('created_at')
                ->first();
            if ($user)
                break;
        }

        if(isset($admin))
            $admin = $admin->id_user;
        if(isset($support))
            $support = $support->id_user;

        $data = new \stdClass();
        $data->admin = $admin;
        $data->support = $support;
        $data->demand_user = $user->id_user;

        \App::instance('data', $data);

        return $next($request);
    }
}
