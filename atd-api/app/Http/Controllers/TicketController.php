<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HaveRole;
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
            'type' => $validatedData['type'],
        ]);

        $ticket->users()->attach(TokenController::decodeToken($request->header('Authorization'))->id);

        $response = [
            'ticket' => $ticket
        ];

        return Response($response, 201);
    }

    public function getMyTickets(Request $request){
        $id_user = TokenController::decodeToken($request->header('Authorization'))->id;
        $tickets_id = Send::select('id_ticket')->where('id_user', $id_user)->get();

        $ticketIds = $tickets_id->pluck('id_ticket');

        $tickets = Ticket::whereIn('id', $ticketIds)->get();

        return response()->json(['tickets' => $tickets]);
    }
    

}
