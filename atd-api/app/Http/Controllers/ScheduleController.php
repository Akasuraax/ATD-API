<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
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
}
