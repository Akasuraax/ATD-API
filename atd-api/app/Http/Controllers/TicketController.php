<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HaveRole;
use App\Models\Message;
use App\Models\Send;
use App\Models\Type;
use App\Models\User;
use Illuminate\Http\Response;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    public function createTicket(Request $request)
    {

    try {
        $validatedData = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'type' => 'required|int',
        ]);
    } catch (ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
    }

        $ticket = Ticket::create([
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'type' => $validatedData['type']
        ]);

        $ticket->users()->attach(TokenController::decodeToken($request->header('Authorization'))->id);

        $response = [
            'ticket' => [
                'id' => $ticket->id,
                'description' => $ticket->description,
                'type' => $ticket->type,
                'created_at' => $ticket->created_at
            ]
        ];

        return Response($response, 201);
    }

    public function getMyTickets(Request $request){
        $id_user = TokenController::decodeToken($request->header('Authorization'))->id;

        $tickets_id = Send::select('id_ticket')->where('id_user', $id_user)->get();

        $ticketIds = $tickets_id->pluck('id_ticket');

        $tickets = Ticket::select('id', 'title', 'description', 'type', 'created_at')->whereIn('id', $ticketIds)->get();

        return response()->json([
            'ticket' => $tickets,
        ]);
    }

    public function getTicket(int $id_ticket, Request $request)
    {
        $ticket = Ticket::select('id', 'title', 'description', 'type', 'created_at')->where('id', $id_ticket)->first();
        $messages = Message::where('id_ticket', $ticket->id)->get();

        $messagesData = [];
        foreach ($messages as $message) {
            $user = User::where('id', $message->id_user)->get()->first();
            $messagesData[] = [
                'description' => $message->description,
                'created_at' => $message->created_at,
                'user' => [
                    'name' => $user->name,
                    'forname' => $user->forname
                ]
            ];
        }
        $user = User::where('id', TokenController::decodeToken($request->header('Authorization'))->id)->first();
        return response()->json([
            'ticket' => [
                'title' => $ticket->title,
                'description' => $ticket->description,
                'type' => $ticket->type,
                'created_at' => $ticket->created_at,
                'user' => [
                    'name' => $user->name,
                    'forname' => $user->forname
                ]
            ],
            'messages' => $messagesData
        ]);
    }

    public function patchTicket(int $id_ticket, Request $request){
        try{
            $validatedData = $request->validate([
                'title' => 'string',
                'description' => 'string',
                'type' => 'integer',
                'status' => 'integer',
                'severity' => 'integer',
                'archive' => 'boolean'
            ]);
        }catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $ticket = Ticket::findOrFail($id_ticket);
        if(isset($validatedData['title']))
            $ticket->title = $validatedData['title'];
        if(isset($validatedData['description']))
            $ticket->description = $validatedData['description'];
        if(isset($validatedData['type']))
            $ticket->type = $validatedData['type'];
        if(isset($validatedData['status']))
            $ticket->status = $validatedData['status'];
        if(isset($validatedData['severity']))
            $ticket->severity = $validatedData['severity'];
        if(isset($validatedData['archive']))
            $ticket->archive = $validatedData['archive'];

        if(!$ticket->archive){
            $messages = Message::where('id_ticket', $id_ticket)->get();

            foreach($messages as $message){
                $message->archive = true;
            }
        }else{
            $messages = Message::where('id_ticket', $id_ticket)->get();

            foreach($messages as $message){
                $message->archive = false;
            }
        }
        $ticket->save();
        $ticket->touch();

        $messages = Message::where('id_ticket', $id_ticket)->get();

        return response()->json([
            'ticket' => $ticket,
            'messages' => $messages
        ]);
    }

    public function deleteTicket(int $id_ticket){
        $ticket = Ticket::findOrFail($id_ticket);
        $ticket->archive = true;

        $messages = Message::where('id_ticket', $id_ticket)->get();

        foreach($messages as $message){
            $message->archive = true;
        }

        return response()->json([
            'ticket' => $ticket,
            'messages' => $messages
        ], 200);
    }


}
