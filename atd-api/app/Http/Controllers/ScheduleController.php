<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    public function createSchedule(int $id, Request $request){
        try {
            $fields = $request->validate([
                'schedule.day' => 'required|int',
                'schedule.start_hour' => 'required|date_format:H:i|required',
                'schedule.end_hour' => 'required|date_format:H:i|required'
            ]);
        }catch (ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $defaultDate = date('Y-m-d');
        $start_hour = $defaultDate . ' ' . $fields['schedule']['start_hour'];
        $end_hour = $defaultDate . ' ' . $fields['schedule']['end_hour'];

        if($fields['schedule']['day'] < 1 || $fields['schedule']['day'] > 7){
            return response()->json([
                'message' => 'chose between monday and sunday'
            ], 404);
        }

        if(!empty(Schedule::where('day', $fields['schedule']['day'])->where('user_id', $id)->first()))
            return  response()->json(['message' => 'you already created a schedule for this day'], 409);

        $schedule = Schedule::create([
            'day' => $fields['schedule']['day'],
            'start_hour' => $start_hour,
            'end_hour' => $end_hour,
            'user_id' => $id
        ]);

        switch ($fields['schedule']['day']){
            case 1:
                $fields['schedule']['day'] = "lundi";
                break;
            case 2:
                $fields['schedule']['day'] = "mardi";
                break;
            case 3:
                $fields['schedule']['day'] = "mercredi";
                break;
            case 4:
                $fields['schedule']['day'] = "jeudi";
                break;
            case 5:
                $fields['schedule']['day'] = "vendredi";
                break;
            case 6:
                $fields['schedule']['day'] = "samedi";
                break;
            case 7:
                $fields['schedule']['day'] = "dimanche";
                break;
            default:
                break;
        }

        $user = Schedule::with('user')->find($schedule->user_id)->getRelation('user');
        return response()->json([
            'schedule' => [
                'day' => $fields['schedule']['day'],
                'start_hour' => $fields['schedule']['start_hour'],
                'end_hour' => $fields['schedule']['end_hour'],
                'user' => [
                    'id' => $user->id,
                    'forname' => $user->forname,
                    'name' => $user->name,
                    'company' => $user->compagny
                ]
            ]
        ], 201);
    }

    public function updateSchedule(int $userId, Request $request, int $scheduleDay){
        try {
            $schedule = Schedule::where('user_id', $userId)->where('day', $scheduleDay)->first();

            $fields = $request->validate([
                'schedule.start_hour' => 'required|date_format:H:i|required',
                'schedule.end_hour' => 'required|date_format:H:i|required',
                'schedule.checking' => 'required|boolean'
            ]);
        }catch (ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $defaultDate = date('Y-m-d');
        $start_hour = $defaultDate . ' ' . $fields['schedule']['start_hour'];
        $end_hour = $defaultDate . ' ' . $fields['schedule']['end_hour'];

        $fields['schedule']['end_hour'] = $end_hour;
        $fields['schedule']['start_hour'] = $start_hour;

        try{
            $schedule->start_hour = $start_hour;
            $schedule->end_hour = $end_hour;
            $schedule->checking = $fields['schedule']['checking'];

            $schedule->save();
        }catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'The element you selected is not found'], 404);
        }

        switch ($scheduleDay){
            case 1:
                $scheduleDay = "lundi";
                break;
            case 2:
                $scheduleDay = "mardi";
                break;
            case 3:
                $scheduleDay = "mercredi";
                break;
            case 4:
                $scheduleDay = "jeudi";
                break;
            case 5:
                $scheduleDay = "vendredi";
                break;
            case 6:
                $scheduleDay = "samedi";
                break;
            case 7:
                $scheduleDay= "dimanche";
                break;
            default:
                break;
        }

        $user = Schedule::with('user')->find($schedule->user_id)->getRelation('user');
        return response()->json([
            'schedule' => [
                'day' =>  $scheduleDay,
                'start_hour' => $fields['schedule']['start_hour'],
                'end_hour' => $fields['schedule']['end_hour'],
                'user' => [
                    'id' => $user->id,
                    'forname' => $user->forname,
                    'name' => $user->name,
                    'company' => $user->compagny
                ]
            ]], 200);
    }
}
