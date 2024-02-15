<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function createTicket(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'type' => 'required|int',
        ]);

            $ticket = Ticket::create([
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'type' => $validatedData['type'],
                'status' => '0',
                'severity' => '1',
                'archive' => false,
            ]);

            $response = [
                'ticket' => $ticket
            ];

            return Response($response, 201);
    }
}
