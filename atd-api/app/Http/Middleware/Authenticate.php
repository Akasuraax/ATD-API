<?php
namespace App\Http\Middleware;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TokenController;
use App\Models\HaveRole;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization');

        if (!isset($token)) {
            return response()->json(["You're not connected"], 401);
        }

        $time = TokenController::decodeToken($token)->time;
        if ($time > time()) {
            (new AuthController())->logOut($request);
            return response()->json(["Token expired"], 401);
        }

        if (!User::where('remember_token', $token)->first()) {
            return response()->json(["You can't use an old token"], 401);
        }

        // Si la validation du token passe, on poursuit le traitement de la requête
        return $next($request);
    }

    protected function accessAuthorization(Request $request, int $role) : JsonResponse
    {
        $userRoles = HaveRole::where('id_user', TokenController::decodeToken($request->header('Authorization'))->id);

        foreach ($userRoles as $userRole) {
            if ($userRole->role == $role) {
                return response()->json([
                    'message' => 'You\'re allowed to access to this page',
                    'code' => 200
                ]);
            }
        }

        return response()->json([
            'message' => 'You\'re not allowed to access to this page',
            'code' => 403,
        ]);
    }
}
