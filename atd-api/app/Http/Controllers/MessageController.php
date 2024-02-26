<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Send;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    public function createMessage(int $ticket_id, Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'description' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $message = Message::create([
            'description' => $validatedData['description'],
            'id_user' => TokenController::decodeToken($request->header('Authorization'))->id,
            'id_ticket' => $ticket_id
        ]);

        return response()->json([
            'message' => [
                'description' => $message->description
            ]
        ], 201);
    }
}
