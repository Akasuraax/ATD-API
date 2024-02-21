<?php

namespace App\Http\Middleware;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TokenController;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidationTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization');

        if (!isset($token)) {
            return response()->json(['message' => 'You\'re not connected'], 401);
        }

        $time = TokenController::decodeToken($token)->time;
        if ($time > time()) {
            (new AuthController())->logOut($request);
            return response()->json(["Token expired"], 401);
        }

        if (!User::where('remember_token', $token)->first()) {
            return response()->json(['message' => 'You can\'t use an old token'], 401);
        }

        return $next($request);
    }
}
