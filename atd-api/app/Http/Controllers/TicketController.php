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

        $problem = Problem::findOrfail($validatedData['ticket']['type']);
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
                'problem' => $problem->name,
                'created_at' => $ticket->created_at
            ]
        ];

        return Response($response, 201);
    }

    public function getMyTickets(Request $request){
        $id_user = $request->route('id');
        $tickets_id = Send::select('id_ticket')->where('id_user', $id_user)->get();

        $ticketIds = $tickets_id->pluck('id_ticket');

        $tickets = Ticket::select('id', 'title', 'description', 'type', 'created_at')->whereIn('id', $ticketIds)->get();

        return response()->json([
            'ticket' => $tickets,
        ]);
    }

    public function getTicket(int $id_ticket, Request $request)
    {
        $ticket = Ticket::findOrFail($id_ticket);
        $messages = Message::where('id_ticket', $ticket->id)->get();

        $admin = $request->attributes->parameters['admin'];
        $support = $request->attributes->parameters['support'];
        $demand_user = $request->attributes->parameters['demand_user'];

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

        $user = User::where('id', $demand_user)->first();

        if(isset($admin) || isset($support)){
            return response()->json([
                'ticket' => [
                    'id' => $ticket->id,
                    'title' => $ticket->title,
                    'description' => $ticket->description,
                    'type' => $ticket->type,
                    'status' => $ticket->status,
                    'severity' => $ticket->severity,
                    'archive' => $ticket->archive,
                    'created_at' => $ticket->created_at,
                    'updated_at' => $ticket->updated_at,
                    'user' => [
                        'name' => $user->name,
                        'forname' => $user->forname
                    ]
                ],
                'messages' => $messagesData
            ]);
        }

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

    public function getTickets(Request $request){
        $perPage = $request->input('pageSize', 10);
        if($perPage > 50){
            $perPage = 50;
        }
        $page = $request->input('page', 1);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $tickets = Ticket::select('*')
            ->where(function ($query) use ($fieldFilter, $operator, $value) {
                if ($fieldFilter && $operator && $value !== '*') {
                    switch ($operator) {
                        case 'contains':
                            $query->where($fieldFilter, 'LIKE', '%' . $value . '%');
                            break;
                        case 'equals':
                            $query->where($fieldFilter, '=', $value);
                            break;
                        case 'startsWith':
                            $query->where($fieldFilter, 'LIKE', $value . '%');
                            break;
                        case 'endsWith':
                            $query->where($fieldFilter, 'LIKE', '%' . $value);
                            break;
                        case 'isEmpty':
                            $query->whereNull($fieldFilter);
                            break;
                        case 'isNotEmpty':
                            $query->whereNotNull($fieldFilter);
                            break;
                        case 'isAnyOf':
                            $values = explode(',', $value);
                            $query->whereIn($fieldFilter, $values);
                            break;
                    }
                }
            } )
            ->orderBy($field, $sort)
            ->paginate($perPage, ['*'], 'page', $page + 1);

        return response()->json([
            'tickets' => $tickets
        ]);
    }

    public function patchTicket(int $id_ticket, Request $request){
        try{
            $validatedData = $request->validate([
                'ticket.title' => 'string',
                'ticket.description' => 'string',
                'ticket.type' => 'integer',
                'ticket.status' => 'integer',
                'ticket.severity' => 'integer',
                'ticket.archive' => 'boolean'
            ]);
        }catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $ticket = Ticket::findOrFail($id_ticket);
        $problem = Problem::findOrFail($validatedData['ticket']['type']);

        if(isset($validatedData['ticket']['title']))
            $ticket->title = $validatedData['ticket']['title'];
        if(isset($validatedData['ticket']['description']))
            $ticket->description = $validatedData['ticket']['description'];
        if(isset($validatedData['ticket']['type']))
            $ticket->problem_id = $validatedData['ticket']['type'];
        if(isset($validatedData['ticket']['status']))
            $ticket->status = $validatedData['ticket']['status'];
        if(isset($validatedData['ticket']['severity']))
            $ticket->severity = $validatedData['ticket']['severity'];
        if(isset($validatedData['ticket']['archive']))
            $ticket->archive = $validatedData['ticket']['archive'];

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
            'ticket' => [
                'id' => $ticket->id,
                'title' => $ticket->title,
                'description' => $ticket->description,
                'severity' => $ticket->severity,
                'status' => $ticket->status,
                'problem' => $problem->name,
                'archive' => $ticket->archive,
                'created_at' => $ticket->created_at,
                'updated_at' => $ticket->updated_at
            ],
            'messages' => $messages
        ]);
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
