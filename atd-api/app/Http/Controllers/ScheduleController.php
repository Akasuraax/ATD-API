<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Annexe;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Carbon\Carbon;
use http\Env\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        $day = $this->getDayFromId($fields['schedule']['day']);

        return response()->json([
            'schedule' => [
                'day' => $day,
                'start_hour' => $fields['schedule']['start_hour'],
                'end_hour' => $fields['schedule']['end_hour'],
                'user' => $schedule->user
            ]
        ], 201);
    }

    public function updateSchedule(int $userId, Request $request, int $scheduleDay)
    {
        try {
            $schedule = Schedule::where('user_id', $userId)->where('day', $scheduleDay)->first();

            if (empty($schedule))
                return response()->json(['message' => 'Not found'], 404);

            $fields = $request->validate([
                'schedule.start_hour' => 'required|date_format:H:i|required',
                'schedule.end_hour' => 'required|date_format:H:i|required',
                'schedule.checking' => 'required|boolean'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $defaultDate = date('Y-m-d');
        $start_hour = $defaultDate . ' ' . $fields['schedule']['start_hour'];
        $end_hour = $defaultDate . ' ' . $fields['schedule']['end_hour'];

        $fields['schedule']['end_hour'] = $end_hour;
        $fields['schedule']['start_hour'] = $start_hour;

        try {
            $schedule->start_hour = $start_hour;
            $schedule->end_hour = $end_hour;
            $schedule->checking = $fields['schedule']['checking'];

            $schedule->save();
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'The element you selected is not found'], 404);
        }

        $day = $this->getDayFromId($scheduleDay);

        return response()->json([
            'schedule' => [
                'day' => $day,
                'start_hour' => $fields['schedule']['start_hour'],
                'end_hour' => $fields['schedule']['end_hour'],
                'user' => $schedule->user
            ]], 200);
    }

    public function getDayFromId($dayId){
        switch ($dayId){
            case 1:
                $day = "lundi";
                break;
            case 2:
                $day = "mardi";
                break;
            case 3:
                $day = "mercredi";
                break;
            case 4:
                $day = "jeudi";
                break;
            case 5:
                $day = "vendredi";
                break;
            case 6:
                $day = "samedi";
                break;
            case 7:
                $day= "dimanche";
                break;
            default:
                break;
        }

        return $day;
    }


}
