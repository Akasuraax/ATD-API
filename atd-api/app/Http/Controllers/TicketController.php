<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function createTicket(Request $request): string
    {
        $validatedData = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'type' => 'required|int',
        ]);
            $validatedData['status'] = 0;
            $validatedData['severity'] = 1;
            $validatedData['archive'] = false;

            $ticket = Ticket::create($validatedData);

            $response = [
                'ticket' => $ticket,
            ];

            return Response($response, 201);
    }
}
