<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HaveRole;
use App\Models\Message;
use App\Models\Problem;
use App\Models\Role;
use App\Models\Send;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
            'ticket.title' => 'required|string|max:255',
            'ticket.description' => 'required|string',
            'ticket.type' => 'required|int',
        ]);
    } catch (ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
    }

        Problem::findOrfail($validatedData['ticket']['type']);
        $ticket = Ticket::create([
            'title' => $validatedData['ticket']['title'],
            'description' => $validatedData['ticket']['description'],
            'problem_id' => $validatedData['ticket']['type']
        ]);

        $ticket->users()->attach(TokenController::decodeToken($request->header('Authorization'))->id);

        $response = [
            'ticket' => [
                'id' => $ticket->id,
                'description' => $ticket->description,
                'problem' => $ticket->problem->name,
                'created_at' => $ticket->created_at
            ]
        ];

        return Response($response, 201);
    }

    public function getMyTickets(Request $request){
        $id_user = $request->route('id');

        $tickets_id = Send::select('id_ticket')->where('id_user', $id_user)->get();

        $ticketIds = $tickets_id->pluck('id_ticket');

        $tickets = Ticket::select('tickets.id', 'tickets.title', 'tickets.description', 'tickets.created_at', 'problems.name')
            ->join('problems', 'tickets.problem_id', '=', 'problems.id')
            ->whereIn('tickets.id', $ticketIds)
            ->get();

        $tickets = $tickets->map(function ($ticket) {
            $ticket['problem'] = $ticket['name'];
            unset($ticket['name']);
            return $ticket;
        });

        return response()->json([
            'tickets' => $tickets,
        ]);
    }

    public function getTicket(int $id_ticket, Request $request)
    {
        $ticket = Ticket::findOrFail($id_ticket);

        $data = \App::make('data');
        $admin = $data->admin;
        $support = $data->support;
        $demand_user = $data->demand_user;

        $user = User::where('id', $demand_user)->first();
        $messages = Message::with('userWhoSendTheMessage')
            ->select( 'description', 'created_at', 'id_user')
            ->where('id_ticket', $id_ticket)
            ->get();

        if(isset($admin) || isset($support)){
            return response()->json([
                'ticket' => [
                    'id' => $ticket->id,
                    'title' => $ticket->title,
                    'description' => $ticket->description,
                    'problem' => $ticket->problem->name,
                    'status' => $ticket->status,
                    'severity' => $ticket->severity,
                    'archive' => $ticket->archive,
                    'created_at' => $ticket->created_at,
                    'updated_at' => $ticket->updated_at,
                    'user' => [
                        'name' => $user->name,
                        'forname' => $user->forname
                    ],
                    'messages' => $messages->map(function ($message) {
                        return [
                            'description' => $message->description,
                            'created_at' => $message->created_at,
                            'user' => [
                                'id' => $message->userWhoSendTheMessage->id,
                                'name' => $message->userWhoSendTheMessage->name,
                                'forname' => $message->userWhoSendTheMessage->forname
                            ]
                        ];
                    })
            ]]);
        }

        return response()->json([
            'ticket' => [
                'title' => $ticket->title,
                'description' => $ticket->description,
                'problem' => $ticket->problem->name ,
                'created_at' => $ticket->created_at,
                'user' => [
                    'name' => $user->name,
                    'forname' => $user->forname
                ],
                'messages' => $messages->map(function ($message) {
                    return [
                        'description' => $message->description,
                        'created_at' => $message->created_at,
                        'user' => [
                            'id' => $message->userWhoSendTheMessage->id,
                            'name' => $message->userWhoSendTheMessage->name,
                            'forname' => $message->userWhoSendTheMessage->forname
                        ]
                    ];
                })
            ]
        ]);
    }

    public function getTickets(Request $request){

// Récupérez tous les tickets avec leurs supports
        $tickets = Ticket::where('archive', false)
            ->with('problem')
            ->with('support')
            ->get();

        $transformedTickets = $tickets->map(function ($ticket) {
            // Identifiez l'utilisateur qui a créé le ticket
            $creatorId = $ticket->support->sortBy('created_at')->first()->id;

            // Filtrez les supports pour exclure l'utilisateur qui a créé le ticket
            $filteredSupports = $ticket->support->reject(function ($support) use ($creatorId) {
                return $support->id == $creatorId;
            });

            return [
                'id' => $ticket->id,
                'title' => $ticket->title,
                'description' => $ticket->description,
                'status' => $ticket->status,
                'severity' => $ticket->severity,
                'archive' => $ticket->archive,
                'created_at' => $ticket->created_at,
                'updated_at' => $ticket->updated_at,
                'problem_id' => $ticket->problem_id,
                'problem' => $ticket->problem->name,
                'support' => $ticket->support->whereNull('created_at')->first() ? [
                    'id' => $ticket->support->whereNull('created_at')->first()->id,
                    'name' => $ticket->support->whereNull('created_at')->first()->name,
                    'forname' => $ticket->support->whereNull('created_at')->first()->forname,
                ] : null
            ];



        });

        // Renvoyer la collection transformée
        return response()->json(['tickets' => $transformedTickets]);
    }

    public function patchTicket(int $id_ticket, Request $request){
        //return $request;
        try{
            $validatedData = $request->validate([
                'title' => 'required:string',
                'description' => 'required:string',
                'status' => 'required:integer',
                'severity' => 'required:integer',
                'archive' => 'required:boolean',
                'problem' => 'required:string'
            ]);
        }catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $ticket = Ticket::findOrFail($id_ticket);
        //$problem = Problem::findOrFail($validatedData['ticket']['type']);

        if(isset($validatedData['title']))
            $ticket->title = $validatedData['title'];
        if(isset($validatedData['description']))
            $ticket->description = $validatedData['description'];

        if(isset($validatedData['status']))
            $ticket->status = $validatedData['status'];
        if(isset($validatedData['severity']))
            $ticket->severity = $validatedData['severity'];
        if(isset($validatedData['archive']))
            $ticket->archive = $validatedData['archive'];

        if($ticket->archive){
            $messages = Message::where('id_ticket', $id_ticket)->get();

            foreach($messages as $message){
                $message->archive = true;
            }

            try{
                $ticket->update($validatedData);
                $ticket->save();
                $messages = $ticket->messages()->get();
            }catch (ModelNotFoundException $e) {
                return response()->json(['error' => 'The element you selected is not found'], 404);
            }
        }

        $ticket->save();
        $ticket->touch();

        $messages = Message::where('id_ticket', $id_ticket)->get();

        return response()->json([
            'ticket' => [
                'id' => $ticket->id,
                'title' => $ticket->title,
                'description' => $ticket->description,
                'severity' => $ticket->severity,
                'status' => $ticket->status,
                'problem' => $validatedData['problem'],
                'archive' => $ticket->archive,
                'created_at' => $ticket->created_at,
                'updated_at' => $ticket->updated_at
            ],
            'messages' => $messages
        ]);
    }

    public function assignedTicket(int $id_ticket, Request $request){
        try{
            $validatedData = $request->validate([
                'id' => 'int',
            ]);
        }catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $ticket = Ticket::findOrFail($id_ticket);
        $newSupport = User::findOrFail($validatedData['id']);

        // Trouver le support actuellement assigné au ticket
        // Supposons que la relation entre Ticket et User est nommée 'sends'
        $currentSupport = $ticket->support()->whereNull('sends.created_at')->orWhere('sends.created_at', '<>', null)->first();

        // Vérifier si le ticket a plus de 2 utilisateurs attachés
        if ($ticket->support()->count() > 1) {
            if ($currentSupport) {
                $ticket->support()->detach($currentSupport->id);
            }
        }
        $ticket->support()->attach($newSupport->id);

        return response()->json(['message' => 'Support assigned successfully']);
    }

    public function deleteTicket(int $id){
        try{
            $ticket = Ticket::findOrFail($id);
            if($ticket->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);

            $ticket->archive();
            $ticket = Ticket::findOrFail($id);

            return response()->json(['role' => $ticket,  'message' => "Deleted !"], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }


}
