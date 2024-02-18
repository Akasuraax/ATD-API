<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    public function createTicket(Request $request)
    {

    try {
        $validatedData = $request->validate([
            'ticket.title' => 'required|string',
            'ticket.description' => 'required|string',
            'ticket.type' => 'required|int',
            'userId' => 'required|int',
        ]);
    } catch (ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
    }

            $ticket = Ticket::create([
                'title' => $validatedData['ticket']['title'],
                'description' => $validatedData['ticket']['description'],
                'type' => $validatedData['ticket']['type'],
            ]);
            $ticket->users()->attach($validatedData['userId']);

            $response = [
                'ticket' => $ticket
            ];

            return Response($response, 201);
    }

    public function getTickets(Request $request) {

        $ticket = Ticket::where('archive', '=', 'false')->get();

        return Response($ticket, 200);
    }
}
