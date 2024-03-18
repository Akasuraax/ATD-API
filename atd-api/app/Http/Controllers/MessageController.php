<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Send;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $user_id = TokenController::decodeToken($request->header('Authorization'))->id;
        $message = Message::create([
            'description' => $validatedData['description'],
            'id_user' => $user_id,
            'id_ticket' => $ticket_id
        ]);

        if(!Send::where('id_user', $user_id)->where('id_ticket', $ticket_id)->get()->first())
            DB::table('sends')->insert([
                'id_user' => $user_id,
                'id_ticket' => $ticket_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

        return response()->json([
            'message' => [
                'description' => $message->description,
                'created_at' => $message->created_at,
                'user' => [
                    'id' => $message->userWhoSendTheMessage->id,
                    'name' => $message->userWhoSendTheMessage->name,
                    'forname' => $message->userWhoSendTheMessage->forname
                ]

            ]
        ], 201);
    }
}
