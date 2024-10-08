<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Mockery\Exception;

class AuthController extends Controller
{

    public function logIn(Request $request) : JsonResponse
    {

        try {
            $fields = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string'
            ]);
        }catch (ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $request['email'] = strtolower($request->email);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {

            $user = Auth::user();

            $existingToken = $user->remember_token;
            if ($existingToken) {
                $token = $existingToken;
            } else {
                $token = TokenController::encodeToken($user->id);
                User::where('id', $user->id)->update(['remember_token' => $token]);
            }

            if($user->archive)
                return response()->json(['message' => 'Your account has been archived.'], 403);

            User::where('id', $user->id)->update(['remember_token' => $token]);
            $userWithRoles = User::with('roles')->find($user->id);

            $response = response()->json([
                'message' => 'Logged in successfully',
                'token' => $token,
                'user' => $userWithRoles
            ], 200);

            return $response;

        } else {
            return response()->json(['message' => 'Email or password is wrong'], 401);
        }
    }

    public function logOut(Request $request) : JsonResponse
    {
        $token = $request->header('Authorization');
        $id = TokenController::decodeToken($token)->id;

        User::where('id', $id)->update(['remember_token' => NULL]);

        $response = response()->json([
            'message' => 'Logged out successfully'
        ], 200);

        $response->header('Authorization', NULL);

        return $response;
    }

}

